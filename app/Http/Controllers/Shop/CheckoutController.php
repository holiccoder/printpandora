<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliateReferral;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Cart;
use App\Services\CryptomusService;
use App\Services\DiscountException;
use App\Services\DiscountService;
use App\Services\PayPalService;
use App\Services\ShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        protected ShippingService $shipping,
    ) {}

    public function show(Cart $cart)
    {
        if ($cart->count() === 0) {
            return redirect()->route('shop.cart');
        }

        $quote = $cart->quote();
        $defaultShippingMethod = $this->shipping->defaultMethod();
        $shippingFee = $this->shipping->fee($defaultShippingMethod);

        return Inertia::render('shop/checkout', [
            'cart' => $cart->all(),
            'subtotal' => $quote['subtotal'],
            'discountAmount' => $quote['discount'],
            'itemsTotal' => $quote['total'],
            'total' => round($quote['total'] + $shippingFee, 2),
            'shippingFee' => $shippingFee,
            'shippingMethods' => $this->shipping->methods(),
            'defaultShippingMethod' => $defaultShippingMethod,
            'discountCode' => $quote['code'],
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
            $order = $this->placeOrder($request, $cart, $validated, 'manual', 'pending', null);
        } catch (DiscountException $exception) {
            return back()->withErrors(['discount_code' => $exception->getMessage()])->withInput();
        }

        $cart->clear();

        return redirect()->route('shop.orders.show', $order->id)
            ->with('success', 'Order placed successfully!');
    }

    /**
     * Create a PayPal order for the current cart and return its id to the SDK.
     */
    public function paypalCreate(Request $request, Cart $cart, PayPalService $paypal)
    {
        if ($cart->count() === 0) {
            return response()->json(['error' => 'Your cart is empty.'], 422);
        }

        try {
            $reference = 'cart-'.($request->user()?->id ?? 'guest').'-'.now()->timestamp;
            $shippingMethod = $this->validateShippingMethod($request);
            $quote = $cart->quote($request->input('customer_email'), true);
            $total = round($quote['total'] + $this->shipping->fee($shippingMethod), 2);
            $result = $paypal->createOrder($total, $reference);

            return response()->json(['id' => $result['id'] ?? null]);
        } catch (DiscountException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('PayPal create order failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Unable to create PayPal order.'], 500);
        }
    }

    /**
     * Capture an approved PayPal order and persist the local Order.
     */
    public function paypalCapture(Request $request, Cart $cart, PayPalService $paypal)
    {
        if ($cart->count() === 0) {
            return response()->json(['error' => 'Your cart is empty.'], 422);
        }

        $validated = $this->validateCheckout($request);

        $request->validate([
            'paypal_order_id' => 'required|string',
        ]);

        try {
            $capture = $paypal->captureOrder($request->input('paypal_order_id'));
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

        try {
            $order = $this->placeOrder(
                $request,
                $cart,
                $validated,
                'paypal',
                'paid',
                $capture['id'] ?? null,
                'processing',
            );
        } catch (DiscountException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        $cart->clear();

        return response()->json([
            'redirect' => route('shop.orders.show', $order->id),
        ]);
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
            $order = $this->placeOrder(
                $request,
                $cart,
                $validated,
                'cryptomus',
                'pending',
                null,
                'pending',
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
                ]);
                break;

            case 'fail':
            case 'cancel':
            case 'system_fail':
                $order->update([
                    'payment_status' => 'failed',
                    'status' => 'cancelled',
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

    protected function validateCheckout(Request $request): array
    {
        $request->merge([
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

    protected function validateShippingMethod(Request $request): string
    {
        return $request->validate([
            'shipping_method' => ['required', Rule::in($this->shipping->codes())],
        ])['shipping_method'];
    }

    /**
     * Persist an Order + OrderItems and credit any affiliate commission.
     */
    protected function placeOrder(
        Request $request,
        Cart $cart,
        array $validated,
        string $paymentMethod,
        string $paymentStatus,
        ?string $paymentId,
        string $orderStatus = 'pending',
    ): Order {
        $shipping = $this->shipping->get($validated['shipping_method']);

        return DB::transaction(function () use ($validated, $cart, $request, $paymentMethod, $paymentStatus, $paymentId, $orderStatus, $shipping) {
            $quote = $cart->quote($validated['customer_email'], true);
            $orderTotal = round($quote['total'] + $shipping['fee'], 2);

            $order = Order::create([
                'user_id' => $request->user()?->id ?? 1,
                'status' => $orderStatus,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'payment_id' => $paymentId,
                'total' => $orderTotal,
                'shipping_method' => $shipping['code'],
                'shipping_carrier' => $shipping['carrier'],
                'shipping_fee' => $shipping['fee'],
                ...$validated,
            ]);

            if ($quote['code']) {
                app(DiscountService::class)->redeem(
                    $quote['code'],
                    $order,
                    $validated['customer_email'],
                    $quote['subtotal'],
                    $quote['discount'],
                    $orderTotal,
                );
            }

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

            if ($request->user()) {
                $referral = AffiliateReferral::where('referred_user_id', $request->user()->id)->first();
                if ($referral) {
                    $affiliate = Affiliate::find($referral->affiliate_id);
                    if ($affiliate && $affiliate->status === 'active') {
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
                }
            }

            return $order;
        });
    }
}
