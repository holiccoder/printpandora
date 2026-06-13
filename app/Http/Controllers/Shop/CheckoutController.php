<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliateReferral;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Cart;
use App\Services\PayPalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class CheckoutController extends Controller
{
    public function show(Cart $cart)
    {
        if ($cart->count() === 0) {
            return redirect()->route('shop.cart');
        }

        return Inertia::render('shop/checkout', [
            'cart' => $cart->all(),
            'subtotal' => $cart->subtotal(),
            'paypal' => [
                'client_id' => config('services.paypal.client_id'),
                'mode' => config('services.paypal.mode', 'sandbox'),
                'currency' => config('services.paypal.currency', 'USD'),
            ],
        ]);
    }

    public function store(Request $request, Cart $cart)
    {
        if ($cart->count() === 0) {
            return back()->withErrors(['cart' => 'Your cart is empty.']);
        }

        $validated = $this->validateCheckout($request);

        $order = $this->placeOrder($request, $cart, $validated, 'manual', 'pending', null);

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
            $result = $paypal->createOrder($cart->subtotal(), $reference);

            return response()->json(['id' => $result['id'] ?? null]);
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

        $order = $this->placeOrder(
            $request,
            $cart,
            $validated,
            'paypal',
            'paid',
            $capture['id'] ?? null,
            'processing',
        );

        $cart->clear();

        return response()->json([
            'redirect' => route('shop.orders.show', $order->id),
        ]);
    }

    protected function validateCheckout(Request $request): array
    {
        return $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'shipping_address' => 'required|string|max:255',
            'shipping_city' => 'required|string|max:255',
            'shipping_state' => 'nullable|string|max:255',
            'shipping_zip' => 'required|string|max:20',
            'shipping_country' => 'required|string|max:2',
            'notes' => 'nullable|string',
        ]);
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
        return DB::transaction(function () use ($validated, $cart, $request, $paymentMethod, $paymentStatus, $paymentId, $orderStatus) {
            $order = Order::create([
                'user_id' => $request->user()?->id ?? 1,
                'status' => $orderStatus,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'payment_id' => $paymentId,
                'total' => $cart->subtotal(),
                ...$validated,
            ]);

            foreach ($cart->all() as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity'],
                ]);
            }

            if ($request->user()) {
                $referral = AffiliateReferral::where('referred_user_id', $request->user()->id)->first();
                if ($referral) {
                    $affiliate = Affiliate::find($referral->affiliate_id);
                    if ($affiliate && $affiliate->status === 'active') {
                        $rate = (float) $affiliate->commission_rate;
                        $amount = round($cart->subtotal() * $rate / 100, 2);

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
