<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliateReferral;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PayPalWebhookEvent;
use App\Models\ProductDesignRequest;
use App\Services\Cart;
use App\Services\CryptomusService;
use App\Services\DiscountException;
use App\Services\DiscountService;
use App\Services\PayPalService;
use App\Services\ShippingService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Throwable;

class CheckoutController extends Controller
{
    private const CHECKOUT_TOKEN_PREFIX = 'checkout:';

    public function __construct(
        protected ShippingService $shipping,
    ) {}

    public function show(Request $request, Cart $cart)
    {
        if ($cart->count() === 0) {
            return redirect()->route('shop.cart');
        }

        $pendingOrder = $this->preparePendingCheckoutOrder($request, $cart);
        $customerEmail = (string) $request->user()->email;
        $quote = $this->pendingCheckoutQuote($cart, $customerEmail, $pendingOrder, false);
        $defaultShippingMethod = (string) $pendingOrder->shipping_method;
        $defaultCountry = (string) $pendingOrder->shipping_country;
        $shippingFee = $this->shipping->fee($defaultShippingMethod, $defaultCountry);

        return Inertia::render('shop/checkout', [
            'cart' => $cart->all(),
            'subtotal' => $quote['subtotal'],
            'discountAmount' => $quote['discount'],
            'itemsTotal' => $quote['total'],
            'total' => round($quote['total'] + $shippingFee, 2),
            'shippingFee' => $shippingFee,
            'shippingMethods' => $this->shipping->methods($defaultCountry),
            'shippingRateBasisWeightKg' => $this->shipping->rateBasisWeightKg(),
            'defaultShippingCountry' => $defaultCountry,
            'defaultShippingMethod' => $defaultShippingMethod,
            'discountCode' => $quote['code'],
            'pendingOrder' => [
                'shipping_address' => $pendingOrder->shipping_address,
                'shipping_city' => $pendingOrder->shipping_city,
                'shipping_state' => $pendingOrder->shipping_state,
                'shipping_zip' => $pendingOrder->shipping_zip,
                'shipping_country' => $defaultCountry,
                'shipping_method' => $defaultShippingMethod,
                'notes' => $pendingOrder->notes,
            ],
            'paypal' => [
                'client_id' => config('services.paypal.client_id'),
                'mode' => config('services.paypal.mode', 'sandbox'),
                'currency' => config('services.paypal.currency', 'USD'),
            ],
            'cryptomus' => [
                'configured' => app(CryptomusService::class)->isConfigured(),
                'currency' => app(CryptomusService::class)->currency(),
                'test' => app(CryptomusService::class)->isTest(),
            ],
        ]);
    }

    public function store(Request $request, Cart $cart)
    {
        if ($cart->count() === 0) {
            return back()->withErrors(['cart' => 'Your cart is empty.']);
        }

        $validated = $this->validateCheckout($request);

        try {
            $order = $this->preparePendingCheckoutOrder(
                $request,
                $cart,
                $validated,
                'manual',
                true,
            );
        } catch (DiscountException $exception) {
            return back()->withErrors(['discount_code' => $exception->getMessage()])->withInput();
        }

        $cart->clear();
        $order->update(['checkout_token' => null]);

        return redirect()->route('shop.orders.show', $order->id)
            ->with('success', 'Order placed successfully!');
    }

    /**
     * Create a pending local order and its corresponding PayPal order.
     */
    public function paypalCreate(Request $request, Cart $cart, PayPalService $paypal): JsonResponse
    {
        if ($cart->count() === 0) {
            return response()->json(['error' => 'Your cart is empty.'], 422);
        }

        $validated = $this->validateCheckout($request);

        try {
            $order = $this->preparePendingCheckoutOrder(
                $request,
                $cart,
                $validated,
                'paypal',
                true,
            );
            $total = (float) $order->total;
            $reference = 'checkout-'.$request->user()->getAuthIdentifier().'-'.now()->timestamp;
            $result = $paypal->createOrder($total, $reference);
            $paypalOrderId = $result['id'] ?? null;

            if (! is_string($paypalOrderId) || $paypalOrderId === '') {
                throw new \RuntimeException('PayPal did not return an order ID.');
            }

            $order->update(['paypal_order_id' => $paypalOrderId]);

            return response()->json([
                'id' => $paypalOrderId,
                'order_id' => $order->id,
            ]);
        } catch (DiscountException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('PayPal create order failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Unable to create PayPal order.'], 500);
        }
    }

    /**
     * Capture an approved PayPal order and settle its pending local Order.
     */
    public function paypalCapture(Request $request, Cart $cart, PayPalService $paypal): JsonResponse
    {
        $paypalOrderId = $request->validate([
            'paypal_order_id' => 'required|string',
        ])['paypal_order_id'];

        $order = $this->findPayPalOrder($paypalOrderId, $request->user()?->id);

        if (! $order) {
            return response()->json(['error' => 'PayPal order was not found.'], 404);
        }

        if ($order->payment_status === 'paid') {
            $cart->clear();
            $order->update(['checkout_token' => null]);

            return response()->json([
                'redirect' => route('shop.checkout.thank-you', $order->id),
            ]);
        }

        if (in_array($order->payment_status, ['failed', 'refunded', 'reversed'], true)) {
            return response()->json(['error' => 'This PayPal order is no longer payable.'], 422);
        }

        try {
            $capture = $this->captureApprovedPayPalOrder($paypal, $paypalOrderId);
        } catch (Throwable $e) {
            Log::error('PayPal capture failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Unable to capture PayPal payment.'], 500);
        }

        $captureStatus = $capture['status'] ?? null;
        if ($captureStatus !== 'COMPLETED') {
            return response()->json([
                'error' => 'PayPal payment was not completed.',
                'status' => $captureStatus,
            ], 422);
        }

        $order = $this->completePayPalOrder($order, $paypal->captureId($capture));

        $cart->clear();

        return response()->json([
            'redirect' => route('shop.checkout.thank-you', $order->id),
        ]);
    }

    /**
     * Receive, verify, and idempotently process PayPal webhook events.
     */
    public function paypalWebhook(Request $request, PayPalService $paypal): JsonResponse
    {
        try {
            $event = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return response()->json(['error' => 'Invalid PayPal webhook payload.'], 422);
        }

        if (! is_array($event)) {
            return response()->json(['error' => 'Invalid PayPal webhook payload.'], 422);
        }

        $eventId = $event['id'] ?? null;
        $eventType = $event['event_type'] ?? null;

        if (! is_string($eventId) || $eventId === '' || ! is_string($eventType) || $eventType === '') {
            return response()->json(['error' => 'Invalid PayPal webhook payload.'], 422);
        }

        $webhookId = config('services.paypal.webhook_id');
        if (! is_string($webhookId) || $webhookId === '') {
            Log::error('PayPal webhook ID is not configured.');

            return response()->json(['error' => 'PayPal webhook is not configured.'], 503);
        }

        $headers = [
            'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
            'cert_url' => $request->header('PAYPAL-CERT-URL'),
            'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
            'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
        ];

        try {
            if (! $paypal->verifyWebhookSignature($headers, $event, $webhookId)) {
                Log::warning('PayPal webhook signature verification failed', [
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                ]);

                return response()->json(['error' => 'Invalid PayPal webhook signature.'], 401);
            }
        } catch (Throwable $e) {
            Log::error('PayPal webhook verification failed', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Unable to verify PayPal webhook.'], 503);
        }

        try {
            DB::transaction(function () use ($event, $eventId, $eventType, $paypal): void {
                $storedEvent = PayPalWebhookEvent::query()
                    ->where('event_id', $eventId)
                    ->lockForUpdate()
                    ->first();

                if ($storedEvent?->processed_at !== null) {
                    return;
                }

                $paypalOrderId = $this->paypalOrderIdFromEvent($event);
                $paypalCaptureId = $this->paypalCaptureIdFromEvent($event);

                $storedEvent ??= PayPalWebhookEvent::create([
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                    'paypal_order_id' => $paypalOrderId,
                    'paypal_capture_id' => $paypalCaptureId,
                    'payload' => $event,
                ]);

                $order = $this->processPayPalWebhookEvent($event, $paypal);

                $storedEvent->update([
                    'order_id' => $order?->id,
                    'processed_at' => now(),
                ]);
            });
        } catch (Throwable $e) {
            Log::error('PayPal webhook processing failed', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'PayPal webhook processing failed.'], 500);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Create a Cryptomus invoice for the current cart and redirect the user.
     */
    public function cryptomusCreate(Request $request, Cart $cart, CryptomusService $cryptomus)
    {
        if ($cart->count() === 0) {
            return response()->json(['error' => 'Your cart is empty.'], 422);
        }

        if (! $cryptomus->isConfigured()) {
            return response()->json(['error' => 'Cryptomus is not configured.'], 422);
        }

        $validated = $this->validateCheckout($request);

        DB::beginTransaction();

        try {
            $order = $this->preparePendingCheckoutOrder(
                $request,
                $cart,
                $validated,
                'cryptomus',
                true,
            );

            $invoice = $cryptomus->createInvoice((float) $order->total, $order->id);

            $order->update(['payment_id' => $invoice['uuid'] ?? null]);

            DB::commit();
        } catch (DiscountException $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Cryptomus checkout failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Unable to start Cryptomus payment.'], 500);
        }

        $cart->clear();
        $order->update(['checkout_token' => null]);

        return response()->json([
            'redirect' => $invoice['url'] ?? null,
        ]);
    }

    /**
     * Handle Cryptomus webhook notifications.
     */
    public function cryptomusWebhook(Request $request, CryptomusService $cryptomus)
    {
        $payload = $request->getContent();
        $signature = $request->header('Sign') ?? '';

        if (! $cryptomus->verifyWebhookSignature($payload, (string) $signature)) {
            Log::warning('Cryptomus webhook signature mismatch', ['payload' => $payload]);

            return response()->json(['error' => 'Invalid signature.'], 401);
        }

        $data = $request->all();
        $orderId = $data['order_id'] ?? null;
        $status = $data['payment_status'] ?? $data['status'] ?? null;
        $uuid = $data['uuid'] ?? null;

        if (empty($orderId) || empty($status)) {
            return response()->json(['error' => 'Missing required fields.'], 422);
        }

        $order = Order::find($orderId);

        if (! $order || $order->payment_method !== 'cryptomus') {
            return response()->json(['error' => 'Order not found.'], 404);
        }

        if ($uuid && $order->payment_id && $uuid !== $order->payment_id) {
            Log::warning('Cryptomus webhook UUID mismatch', [
                'order_id' => $orderId,
                'expected' => $order->payment_id,
                'received' => $uuid,
            ]);

            return response()->json(['error' => 'Invoice mismatch.'], 409);
        }

        switch (strtolower((string) $status)) {
            case 'paid':
            case 'paid_over':
            case 'confirm_check':
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'checkout_token' => null,
                ]);
                break;

            case 'fail':
            case 'cancel':
            case 'system_fail':
                $order->update([
                    'payment_status' => 'failed',
                    'status' => 'cancelled',
                    'checkout_token' => null,
                ]);
                break;

            case 'wait':
            case 'check':
            default:
                // Leave as pending.
                break;
        }

        Log::info('Cryptomus webhook processed', [
            'order_id' => $order->id,
            'status' => $status,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * @param  array<string, mixed>  $event
     */
    protected function processPayPalWebhookEvent(array $event, PayPalService $paypal): ?Order
    {
        $eventType = (string) $event['event_type'];
        $paypalOrderId = $this->paypalOrderIdFromEvent($event);
        $paypalCaptureId = $this->paypalCaptureIdFromEvent($event);
        $order = $this->findPayPalOrder($paypalOrderId, null, $paypalCaptureId);

        if (! $order) {
            Log::warning('PayPal webhook has no matching local order', [
                'event_id' => $event['id'] ?? null,
                'event_type' => $eventType,
                'paypal_order_id' => $paypalOrderId,
                'paypal_capture_id' => $paypalCaptureId,
            ]);

            return null;
        }

        return match ($eventType) {
            'CHECKOUT.ORDER.APPROVED' => $this->settleApprovedPayPalOrder($order, $paypalOrderId, $paypal),
            'PAYMENT.CAPTURE.COMPLETED' => $this->settleCompletedPayPalCapture($order, $paypalCaptureId, $event, $paypal),
            'PAYMENT.CAPTURE.DENIED', 'CHECKOUT.ORDER.DECLINED' => $this->updatePayPalOrderState($order, 'failed', true),
            'CHECKOUT.PAYMENT-APPROVAL.REVERSED', 'PAYMENT.CAPTURE.REVERSED' => $this->updatePayPalOrderState($order, 'reversed', true),
            'PAYMENT.CAPTURE.REFUNDED' => $this->updatePayPalOrderState($order, 'refunded', false),
            default => $order,
        };
    }

    protected function settleApprovedPayPalOrder(?Order $order, ?string $paypalOrderId, PayPalService $paypal): ?Order
    {
        if (! $order || ! $paypalOrderId || $order->payment_status === 'paid') {
            return $order;
        }

        $capture = $this->captureApprovedPayPalOrder($paypal, $paypalOrderId);
        if (($capture['status'] ?? null) !== 'COMPLETED') {
            return $order;
        }

        return $this->completePayPalOrder($order, $paypal->captureId($capture));
    }

    /**
     * @param  array<string, mixed>  $event
     */
    protected function settleCompletedPayPalCapture(
        Order $order,
        ?string $paypalCaptureId,
        array $event,
        PayPalService $paypal,
    ): Order {
        $this->assertPayPalCaptureMatchesOrder($event, $order, $paypal);

        return $this->completePayPalOrder($order, $paypalCaptureId);
    }

    /** @return array<string, mixed> */
    protected function captureApprovedPayPalOrder(PayPalService $paypal, string $paypalOrderId): array
    {
        try {
            return $paypal->captureOrder($paypalOrderId);
        } catch (Throwable $captureException) {
            // The browser and the APPROVED webhook can race. If the browser
            // captured first, read the order and settle it from that result.
            try {
                $current = $paypal->showOrder($paypalOrderId);
            } catch (Throwable) {
                throw $captureException;
            }

            if (($current['status'] ?? null) === 'COMPLETED') {
                return $current;
            }

            throw $captureException;
        }
    }

    protected function completePayPalOrder(Order $order, ?string $captureId): Order
    {
        return DB::transaction(function () use ($order, $captureId): Order {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->whereKey($order->getKey())
                ->firstOrFail();

            if ($lockedOrder->payment_method !== 'paypal') {
                throw new \RuntimeException('The local order is not a PayPal order.');
            }

            if (! in_array($lockedOrder->payment_status, ['refunded', 'reversed'], true)) {
                $updates = [
                    'payment_status' => 'paid',
                    'checkout_token' => null,
                ];

                if ($lockedOrder->status === 'pending') {
                    $updates['status'] = 'processing';
                }

                if ($captureId && ! $lockedOrder->payment_id) {
                    $updates['payment_id'] = $captureId;
                }

                $lockedOrder->update($updates);
            }

            return $lockedOrder->fresh();
        });
    }

    protected function updatePayPalOrderState(Order $order, string $paymentStatus, bool $cancel): Order
    {
        return DB::transaction(function () use ($order, $paymentStatus, $cancel): Order {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->whereKey($order->getKey())
                ->firstOrFail();

            if ($lockedOrder->payment_status === 'paid' && $paymentStatus === 'failed') {
                return $lockedOrder->fresh();
            }

            $updates = ['payment_status' => $paymentStatus];
            if ($cancel && in_array($lockedOrder->status, ['pending', 'processing'], true)) {
                $updates['status'] = 'cancelled';
                $updates['checkout_token'] = null;
            }

            $lockedOrder->update($updates);

            return $lockedOrder->fresh();
        });
    }

    protected function findPayPalOrder(?string $paypalOrderId, ?int $userId = null, ?string $paypalCaptureId = null): ?Order
    {
        if (! $paypalOrderId && ! $paypalCaptureId) {
            return null;
        }

        return Order::query()
            ->where('payment_method', 'paypal')
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->where(function ($query) use ($paypalOrderId, $paypalCaptureId): void {
                if ($paypalOrderId) {
                    $query->where('paypal_order_id', $paypalOrderId);
                }

                if ($paypalCaptureId) {
                    $query->orWhere('payment_id', $paypalCaptureId);
                }
            })
            ->first();
    }

    /**
     * @param  array<string, mixed>  $event
     */
    protected function paypalOrderIdFromEvent(array $event): ?string
    {
        $eventType = (string) ($event['event_type'] ?? '');
        $orderId = str_starts_with($eventType, 'CHECKOUT.ORDER.')
            || $eventType === 'CHECKOUT.PAYMENT-APPROVAL.REVERSED'
            ? data_get($event, 'resource.id')
            : data_get($event, 'resource.supplementary_data.related_ids.order_id');

        return is_string($orderId) && $orderId !== '' ? $orderId : null;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    protected function paypalCaptureIdFromEvent(array $event): ?string
    {
        $captureId = data_get($event, 'resource.id');

        return str_contains((string) ($event['event_type'] ?? ''), 'PAYMENT.CAPTURE.')
            && is_string($captureId)
            && $captureId !== ''
            ? $captureId
            : null;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    protected function assertPayPalCaptureMatchesOrder(array $event, Order $order, PayPalService $paypal): void
    {
        $amount = data_get($event, 'resource.amount.value');
        $currency = data_get($event, 'resource.amount.currency_code');

        if ($amount !== null && number_format((float) $amount, 2, '.', '') !== number_format((float) $order->total, 2, '.', '')) {
            throw new \RuntimeException('PayPal webhook amount does not match the local order.');
        }

        if ($currency !== null && strtoupper((string) $currency) !== strtoupper($paypal->currency())) {
            throw new \RuntimeException('PayPal webhook currency does not match the local order.');
        }
    }

    /**
     * Return the pending order for this browser checkout or create it once.
     *
     * The token is derived from the authenticated user and session, so a page
     * refresh reuses the same local order while a different checkout session
     * gets its own order. The unique database column also handles concurrent
     * refreshes that arrive before the first response saves session state.
     *
     * @param  array<string, mixed>|null  $validated
     */
    protected function preparePendingCheckoutOrder(
        Request $request,
        Cart $cart,
        ?array $validated = null,
        ?string $paymentMethod = null,
        bool $redeemDiscount = false,
    ): Order {
        $token = $this->checkoutToken($request);
        $order = $this->findPendingCheckoutOrder($request, $token);

        if (! $order) {
            try {
                $order = DB::transaction(function () use ($request, $cart, $token): Order {
                    return $this->findPendingCheckoutOrder($request, $token)
                        ?? $this->createPendingCheckoutOrder($request, $cart, $token);
                });
            } catch (QueryException $exception) {
                // Another request may have created the unique token first.
                $order = $this->findPendingCheckoutOrder($request, $token);

                if (! $order) {
                    throw $exception;
                }
            }
        }

        return $this->synchronizePendingCheckoutOrder(
            $request,
            $cart,
            $order,
            $validated,
            $paymentMethod,
            $redeemDiscount,
        );
    }

    protected function checkoutToken(Request $request): string
    {
        $user = $request->user();

        if (! $user) {
            throw new \LogicException('A logged-in customer is required to start checkout.');
        }

        return hash(
            'sha256',
            self::CHECKOUT_TOKEN_PREFIX.$user->getAuthIdentifier().':'.$request->session()->getId(),
        );
    }

    protected function findPendingCheckoutOrder(Request $request, string $token): ?Order
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        return Order::query()
            ->where('checkout_token', $token)
            ->where('user_id', (int) $user->getAuthIdentifier())
            ->where('status', 'pending')
            ->where('payment_status', 'pending')
            ->first();
    }

    protected function createPendingCheckoutOrder(Request $request, Cart $cart, string $token): Order
    {
        $user = $request->user();

        if (! $user) {
            throw new \LogicException('A logged-in customer is required to start checkout.');
        }

        $shippingMethod = $this->shipping->defaultMethod();
        $shippingCountry = $this->shipping->defaultCountry();
        $shipping = $this->shipping->get($shippingMethod, $shippingCountry);
        $quote = $cart->quote((string) $user->email);

        $order = Order::create([
            'user_id' => (int) $user->getAuthIdentifier(),
            'checkout_token' => $token,
            'status' => 'pending',
            'payment_method' => 'manual',
            'payment_status' => 'pending',
            'payment_id' => null,
            'total' => round($quote['total'] + $shipping['fee'], 2),
            'customer_name' => (string) $user->name,
            'customer_email' => (string) $user->email,
            'customer_phone' => null,
            'shipping_address' => null,
            'shipping_city' => null,
            'shipping_state' => null,
            'shipping_zip' => null,
            'shipping_country' => $shippingCountry,
            'shipping_method' => $shipping['code'],
            'shipping_carrier' => $shipping['carrier'],
            'shipping_fee' => $shipping['fee'],
            'notes' => null,
        ]);

        $this->replaceOrderItems($order, $cart);

        return $order;
    }

    /**
     * @param  array<string, mixed>|null  $validated
     */
    protected function synchronizePendingCheckoutOrder(
        Request $request,
        Cart $cart,
        Order $order,
        ?array $validated,
        ?string $paymentMethod,
        bool $redeemDiscount,
    ): Order {
        return DB::transaction(function () use ($request, $cart, $order, $validated, $paymentMethod, $redeemDiscount): Order {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->whereKey($order->getKey())
                ->firstOrFail();

            if ($lockedOrder->status !== 'pending' || $lockedOrder->payment_status !== 'pending') {
                return $lockedOrder;
            }

            $user = $request->user();

            if (! $user) {
                throw new \LogicException('A logged-in customer is required to continue checkout.');
            }

            $shippingCountry = (string) ($validated['shipping_country']
                ?? $lockedOrder->shipping_country
                ?? $this->shipping->defaultCountry());
            $shippingMethod = (string) ($validated['shipping_method']
                ?? $lockedOrder->shipping_method
                ?? $this->shipping->defaultMethod());

            if (! in_array($shippingMethod, $this->shipping->codes(), true)) {
                $shippingMethod = $this->shipping->defaultMethod();
            }

            $shipping = $this->shipping->get($shippingMethod, $shippingCountry);
            $customerEmail = (string) ($validated['customer_email']
                ?? $lockedOrder->customer_email
                ?? $user->email);
            $quote = $this->pendingCheckoutQuote(
                $cart,
                $customerEmail,
                $lockedOrder,
                $redeemDiscount,
            );

            $attributes = [
                'total' => round($quote['total'] + $shipping['fee'], 2),
                'customer_name' => (string) ($validated['customer_name']
                    ?? $lockedOrder->customer_name
                    ?? $user->name),
                'customer_email' => $customerEmail,
                'shipping_country' => $shippingCountry,
                'shipping_method' => $shipping['code'],
                'shipping_carrier' => $shipping['carrier'],
                'shipping_fee' => $shipping['fee'],
                'status' => 'pending',
                'payment_status' => 'pending',
            ];

            if ($validated !== null) {
                $attributes = [
                    ...$attributes,
                    'customer_phone' => $validated['customer_phone'] ?? null,
                    'shipping_address' => $validated['shipping_address'] ?? null,
                    'shipping_city' => $validated['shipping_city'] ?? null,
                    'shipping_state' => $validated['shipping_state'] ?? null,
                    'shipping_zip' => $validated['shipping_zip'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ];
            }

            if ($paymentMethod !== null) {
                $attributes['payment_method'] = $paymentMethod;
                $attributes['payment_id'] = null;

                if ($paymentMethod !== 'paypal') {
                    $attributes['paypal_order_id'] = null;
                }
            }

            $lockedOrder->update($attributes);
            $this->replaceOrderItems($lockedOrder, $cart);
            $this->attachPendingProductDesignRequests($request, $lockedOrder, $cart);

            if ($redeemDiscount) {
                $this->applyPendingCheckoutFinancials($lockedOrder, $quote, $request);
            }

            return $lockedOrder->fresh();
        });
    }

    /**
     * @return array{code: ?string, subtotal: float, discount: float, total: float}
     */
    protected function pendingCheckoutQuote(
        Cart $cart,
        string $customerEmail,
        Order $order,
        bool $strict,
    ): array {
        $redemption = $order->discountRedemption()->first();

        if ($redemption) {
            return [
                'code' => $redemption->code,
                'subtotal' => (float) $redemption->subtotal,
                'discount' => (float) $redemption->discount_amount,
                // Redemptions store the complete order total, including
                // shipping. Checkout quotes keep shipping separate.
                'total' => max(
                    (float) $redemption->subtotal - (float) $redemption->discount_amount,
                    0,
                ),
            ];
        }

        return $cart->quote($customerEmail !== '' ? $customerEmail : null, $strict);
    }

    /**
     * @param  array{code: ?string, subtotal: float, discount: float, total: float}  $quote
     */
    protected function applyPendingCheckoutFinancials(Order $order, array $quote, Request $request): void
    {
        if ($quote['code'] && ! $order->discountRedemption()->exists()) {
            app(DiscountService::class)->redeem(
                $quote['code'],
                $order,
                (string) $order->customer_email,
                $quote['subtotal'],
                $quote['discount'],
                (float) $order->total,
            );
        }

        $user = $request->user();

        if (! $user || AffiliateCommission::where('order_id', $order->id)->exists()) {
            return;
        }

        $referral = AffiliateReferral::where('referred_user_id', $user->getAuthIdentifier())->first();

        if (! $referral) {
            return;
        }

        $affiliate = Affiliate::find($referral->affiliate_id);

        if (! $affiliate || $affiliate->status !== 'active') {
            return;
        }

        $rate = (float) $affiliate->commission_rate;
        $amount = round($quote['total'] * $rate / 100, 2);

        AffiliateCommission::create([
            'affiliate_id' => $affiliate->id,
            'order_id' => $order->id,
            'amount' => $amount,
            'rate' => $rate,
            'status' => 'earned',
        ]);

        $affiliate->increment('total_earnings', $amount);
    }

    protected function replaceOrderItems(Order $order, Cart $cart): void
    {
        $order->items()->delete();

        foreach ($cart->all() as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'subtotal' => $item['price'] * $item['quantity'],
                'options' => $item['options'] ?? null,
            ]);
        }
    }

    protected function attachPendingProductDesignRequests(
        Request $request,
        Order $order,
        Cart $cart,
    ): void {
        $pendingRequestIds = $request->session()->get(
            'pending_product_design_request_ids',
            [],
        );

        if (! is_array($pendingRequestIds) || $pendingRequestIds === []) {
            return;
        }

        $productIds = [];

        foreach ($cart->all() as $item) {
            $productId = (int) ($item['product_id'] ?? 0);

            if ($productId > 0) {
                $productIds[$productId] = true;
            }
        }

        if ($productIds === []) {
            return;
        }

        $designRequests = ProductDesignRequest::query()
            ->whereIn('id', array_map('intval', $pendingRequestIds))
            ->get();
        $resolvedRequestIds = [];

        foreach ($designRequests as $designRequest) {
            if ($designRequest->order_id !== null) {
                $resolvedRequestIds[] = (int) $designRequest->getKey();

                continue;
            }

            $productId = (int) data_get($designRequest->desgin, 'product_id', 0);

            if ($productId <= 0 || ! isset($productIds[$productId])) {
                continue;
            }

            $designRequest->order()->associate($order);
            $designRequest->save();
            $resolvedRequestIds[] = (int) $designRequest->getKey();
        }

        if ($resolvedRequestIds !== []) {
            $remainingRequestIds = array_values(array_diff(
                array_map('intval', $pendingRequestIds),
                $resolvedRequestIds,
            ));

            if ($remainingRequestIds === []) {
                $request->session()->forget('pending_product_design_request_ids');
            } else {
                $request->session()->put(
                    'pending_product_design_request_ids',
                    $remainingRequestIds,
                );
            }
        }
    }

    protected function validateCheckout(Request $request): array
    {
        $request->merge([
            // Contact details come from the authenticated customer profile.
            // Only shipping details remain editable at checkout.
            'customer_name' => $request->user()?->name,
            'customer_email' => $request->user()?->email,
            'customer_phone' => null,
            'shipping_method' => $request->input('shipping_method') ?: $this->shipping->defaultMethod(),
        ]);

        return $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'shipping_address' => 'required|string|max:255',
            'shipping_city' => 'required|string|max:255',
            'shipping_state' => 'required|string|max:255',
            'shipping_zip' => 'required|string|max:20',
            'shipping_country' => 'required|string|max:2',
            'shipping_method' => ['required', Rule::in($this->shipping->codes())],
            'notes' => 'nullable|string',
        ]);
    }
}
