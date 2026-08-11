<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Cart;
use App\Services\DiscountException;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    public function index(Cart $cart)
    {
        $quote = $cart->quote();

        return Inertia::render('shop/cart', [
            'cart' => $cart->all(),
            'subtotal' => $quote['subtotal'],
            'discount' => $quote['code'],
            'discountAmount' => $quote['discount'],
            'total' => $quote['total'],
            'count' => $cart->count(),
        ]);
    }

    public function add(Request $request, Cart $cart, PricingService $pricing)
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'options' => 'nullable|array',
        ]);

        $product = Product::query()->findOrFail((int) $data['product_id']);
        $options = $pricing->validateOptions($product, $data['options'] ?? []);

        $itemKey = $cart->add(
            $product->id,
            $options,
        );

        if ($request->wantsJson()) {
            return response()->json([
                'count' => $cart->count(),
                'item_key' => $itemKey,
                'message' => 'Added to cart',
            ]);
        }

        return redirect('/cart')->with('success', 'Added to cart');
    }

    public function remove(Request $request, Cart $cart)
    {
        $request->validate(['item_key' => 'required|string']);
        $cart->remove($request->item_key);

        if ($request->wantsJson()) {
            return response()->json(['count' => $cart->count(), 'message' => 'Removed from cart']);
        }

        return back();
    }

    public function applyDiscount(Request $request, Cart $cart)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $cart->applyDiscountCode($data['code']);

        try {
            $cart->quote(null, true);
        } catch (DiscountException $exception) {
            $cart->removeDiscountCode();

            return back()->withErrors(['discount_code' => $exception->getMessage()]);
        }

        return back()->with('success', 'Discount code applied.');
    }

    public function removeDiscount(Cart $cart)
    {
        $cart->removeDiscountCode();

        return back()->with('success', 'Discount code removed.');
    }
}
