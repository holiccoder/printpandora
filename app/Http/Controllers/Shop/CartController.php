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
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'integer|min:1',
        ]);

        $cart->add($request->product_id, $request->input('quantity', 1));

        if ($request->wantsJson()) {
            return response()->json(['count' => $cart->count(), 'message' => 'Added to cart']);
        }

        return back()->with('success', 'Added to cart');
    }

    public function update(Request $request, Cart $cart)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:0',
        ]);

        $cart->update($request->product_id, $request->quantity);

        return back();
    }

    public function remove(Request $request, Cart $cart)
    {
        $request->validate(['product_id' => 'required|integer']);
        $cart->remove($request->product_id);

        if ($request->wantsJson()) {
            return response()->json(['count' => $cart->count(), 'message' => 'Removed from cart']);
        }

        return back();
    }
}
