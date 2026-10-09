<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Cart;
use App\Services\DiscountException;
use App\Services\PricingService;
use App\Support\FreeSamplePackProduct;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    public function index(Request $request, Cart $cart)
    {
        $user = $request->user();
        $customerId = $user?->getAuthIdentifier();
        $customerId = $customerId === null ? null : (int) $customerId;

        $cart->applyAutomaticFirstOrderDiscount($customerId);
        $quote = $cart->quote($user?->email, false, $customerId);

        return Inertia::render('shop/cart', [
            'cart' => $cart->all(),
            'subtotal' => $quote['subtotal'],
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
            'pending_design_id' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9_-]+$/',
            ],
        ]);

        $product = Product::query()->findOrFail((int) $data['product_id']);
        $options = $pricing->validateOptions($product, $data['options'] ?? []);

        $itemKey = $cart->add(
            $product->id,
            $options,
            $data['pending_design_id'] ?? null,
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

    public function addFreeSamplePack(Request $request, Cart $cart)
    {
        $product = FreeSamplePackProduct::resolve();
        $itemKey = $cart->add($product->id);

        if ($request->wantsJson()) {
            return response()->json([
                'count' => $cart->count(),
                'item_key' => $itemKey,
                'message' => 'Added to cart',
            ]);
        }

        return redirect()->route('shop.cart')->with('success', 'Free sample pack added to cart');
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

    public function select(Request $request, Cart $cart)
    {
        $data = $request->validate([
            'item_key' => 'required|string',
            'selected' => 'required|boolean',
        ]);

        $cart->setSelected($data['item_key'], (bool) $data['selected']);

        return back();
    }

    public function applyDiscount(Request $request, Cart $cart)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $cart->applyDiscountCode($data['code']);

        try {
            $user = $request->user();
            $customerId = $user?->getAuthIdentifier();
            $customerId = $customerId === null ? null : (int) $customerId;
            $cart->quote($user?->email, true, $customerId);
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
