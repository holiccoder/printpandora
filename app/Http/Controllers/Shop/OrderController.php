<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('items.product')
            ->where('user_id', auth()->id())
            ->latest()
            ->simplePaginate(10);

        return Inertia::render('shop/orders/index', [
            'orders' => $orders,
        ]);
    }

    public function show(int $id)
    {
        $order = Order::with('items.product')
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return Inertia::render('shop/orders/show', [
            'order' => $order,
        ]);
    }
}
