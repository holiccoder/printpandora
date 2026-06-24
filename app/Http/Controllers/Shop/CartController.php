<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\Cart;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    public function index(Cart $cart)
    {
        return Inertia::render('shop/cart', [
            'cart' => $cart->all(),
            'subtotal' => $cart->subtotal(),
            'count' => $cart->count(),
        ]);
    }

    public function add(Request $request, Cart $cart)
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'options' => 'nullable|array',
        ]);

        $itemKey = $cart->add(
            $request->product_id,
            $data['options'] ?? [],
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
}
