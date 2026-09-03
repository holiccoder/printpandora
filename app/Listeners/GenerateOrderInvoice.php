<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Services\OrderInvoiceService;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateOrderInvoice
{
    public function __construct(
        protected OrderInvoiceService $invoices,
    ) {}

    public function handle(OrderPaid $event): void
    {
        try {
            $this->invoices->issueAndEmail($event->order);
        } catch (Throwable $exception) {
            Log::error('Order invoice generation failed.', [
                'order_id' => $event->order->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
