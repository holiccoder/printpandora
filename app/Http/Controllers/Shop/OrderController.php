<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\DesignServiceRequest;
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
        $order->setAttribute(
            'design_service_attachments',
            $this->designServiceAttachments($order),
        );

        return Inertia::render('shop/orders/show', [
            'order' => $order,
        ]);
    }

    public function downloadDesignServiceAttachment(
        Request $request,
        int $id,
        int $designServiceRequest,
        string $attachment,
    ): Response {
        $order = Order::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
        $designRequest = $order->designServiceRequests()
            ->whereKey($designServiceRequest)
            ->firstOrFail();
        $path = $this->designServiceAttachmentPath($designRequest, $attachment);

        if ($path === null || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $path,
            $this->designServiceDownloadName($path, $attachment),
        );
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

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function designServiceAttachments(Order $order): array
    {
        $attachments = [];

        DesignServiceRequest::query()
            ->where('order_id', $order->getKey())
            ->get()
            ->each(function (DesignServiceRequest $designRequest) use (&$attachments, $order): void {
                if (is_string($designRequest->logo_path) && $designRequest->logo_path !== '') {
                    $this->addDesignServiceAttachment(
                        $attachments,
                        $order,
                        $designRequest,
                        'logo',
                        'Logo',
                    );
                }

                $examplePaths = $designRequest->getAttribute('example_paths');

                if (! is_array($examplePaths)) {
                    return;
                }

                foreach ($examplePaths as $index => $path) {
                    if (! is_string($path) || $path === '') {
                        continue;
                    }

                    $this->addDesignServiceAttachment(
                        $attachments,
                        $order,
                        $designRequest,
                        'example-'.(int) $index,
                        'Example '.((int) $index + 1),
                    );
                }
            });

        return $attachments;
    }

    /**
     * @param  array<int, array{label: string, url: string}>  $attachments
     */
    private function addDesignServiceAttachment(
        array &$attachments,
        Order $order,
        DesignServiceRequest $designRequest,
        string $attachment,
        string $label,
    ): void {
        $path = $this->designServiceAttachmentPath($designRequest, $attachment);

        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return;
        }

        $attachments[] = [
            'label' => $label,
            'url' => route('shop.orders.design-service-attachment', [
                'id' => $order->getKey(),
                'designServiceRequest' => $designRequest->getKey(),
                'attachment' => $attachment,
            ]),
        ];
    }

    private function designServiceAttachmentPath(
        DesignServiceRequest $designRequest,
        string $attachment,
    ): ?string {
        if ($attachment === 'logo') {
            return is_string($designRequest->logo_path) && $designRequest->logo_path !== ''
                ? $designRequest->logo_path
                : null;
        }

        if (! preg_match('/^example-(\d+)$/', $attachment, $matches)) {
            return null;
        }

        $examplePaths = $designRequest->getAttribute('example_paths');

        if (! is_array($examplePaths)) {
            return null;
        }

        $path = $examplePaths[(int) $matches[1]] ?? null;

        return is_string($path) && $path !== '' ? $path : null;
    }

    private function designServiceDownloadName(string $path, string $attachment): string
    {
        $baseName = $attachment === 'logo'
            ? 'design-service-logo'
            : 'design-service-'.$attachment;
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension === '' ? $baseName : $baseName.'.'.$extension;
    }
}
