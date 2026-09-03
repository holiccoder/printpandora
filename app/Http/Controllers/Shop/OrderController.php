<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderInvoiceService;
use App\Services\ShippingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OrderController extends Controller
{
    public function index(): InertiaResponse
    {
        $orders = Order::with('items.product')
            ->where('user_id', auth()->id())
            ->latest()
            ->simplePaginate(10);

        return Inertia::render('shop/orders/index', [
            'orders' => $orders,
        ]);
    }

    public function show(int $id): InertiaResponse
    {
        $order = Order::with('items.product')
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return Inertia::render('shop/orders/show', [
            'order' => $order,
        ]);
    }

    public function downloadInvoice(
        Request $request,
        int $id,
        OrderInvoiceService $invoices,
    ): Response {
        $order = Order::query()
            ->where('user_id', $request->user()->id)
            ->where('payment_status', 'paid')
            ->findOrFail($id);

        try {
            $order = $invoices->ensureInvoice($order);
        } catch (Throwable $exception) {
            report($exception);

            abort(503, 'The invoice is temporarily unavailable.');
        }

        return Storage::disk('local')->download(
            $order->invoice_path,
            $order->invoice_number.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function thankYou(int $id, ShippingService $shipping): InertiaResponse
    {
        $order = Order::query()
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        $shippingMethod = $shipping->get($order->shipping_method);
        $orderDate = CarbonImmutable::instance($order->created_at);

        return Inertia::render('shop/thank-you', [
            'order' => [
                'id' => $order->id,
                'shipping_method' => $shippingMethod['label'],
                'carrier' => $shippingMethod['carrier'],
                'shipping_estimate' => $shippingMethod['estimated_delivery'],
                'dispatch_date' => $orderDate
                    ->startOfDay()
                    ->addWeekdays(1)
                    ->format('D, j M'),
                'delivery_date' => $shipping
                    ->latestDeliveryDate($order->shipping_method, $order->created_at)
                    ->format('D, j M'),
            ],
        ]);
    }
}
