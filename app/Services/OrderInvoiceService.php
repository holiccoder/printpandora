<?php

namespace App\Services;

use App\Mail\OrderInvoiceMail;
use App\Models\Order;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class OrderInvoiceService
{
    /**
     * Create the private invoice PDF for a paid order if it is not already
     * present. The database lock makes invoice numbering safe for duplicate
     * payment callbacks arriving at the same time.
     */
    public function ensureInvoice(Order $order): Order
    {
        if ($order->payment_status !== 'paid') {
            return $order;
        }

        $invoice = DB::transaction(function () use ($order): Order {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($lockedOrder->payment_status !== 'paid') {
                return $lockedOrder;
            }

            $invoiceNumber = $lockedOrder->invoice_number
                ?: $this->makeInvoiceNumber($lockedOrder);
            $invoicePath = $lockedOrder->invoice_path
                ?: 'invoices/'.$invoiceNumber.'.pdf';

            if (! $lockedOrder->invoice_number || ! $lockedOrder->invoice_path || ! $lockedOrder->invoice_issued_at) {
                $lockedOrder->forceFill([
                    'invoice_number' => $invoiceNumber,
                    'invoice_path' => $invoicePath,
                    'invoice_issued_at' => now(),
                ])->save();
            }

            return $lockedOrder->fresh([
                'items.product',
                'discountRedemption',
            ]);
        });

        $disk = Storage::disk('local');

        if (! $disk->exists($invoice->invoice_path)) {
            if (! $disk->put($invoice->invoice_path, $this->renderPdf($invoice))) {
                throw new \RuntimeException('Unable to store the order invoice PDF.');
            }
        }

        return $invoice;
    }

    /**
     * Ensure the invoice exists and send it once to the customer's order
     * email. A failed email is logged while leaving the PDF available for
     * download and for a later retry.
     */
    public function issueAndEmail(Order $order): Order
    {
        $invoice = $this->ensureInvoice($order);

        if ($invoice->payment_status !== 'paid'
            || $invoice->invoice_emailed_at
            || blank($invoice->customer_email)) {
            return $invoice;
        }

        $pdfData = Storage::disk('local')->get($invoice->invoice_path);

        try {
            Mail::to($invoice->customer_email)->send(new OrderInvoiceMail($invoice, $pdfData));

            $invoice->forceFill([
                'invoice_emailed_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            Log::error('Order invoice email failed.', [
                'order_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'error' => $exception->getMessage(),
            ]);
        }

        return $invoice->fresh();
    }

    protected function makeInvoiceNumber(Order $order): string
    {
        $year = $order->created_at?->format('Y') ?: now()->format('Y');

        return sprintf('INV-%s-%06d', $year, $order->id);
    }

    protected function renderPdf(Order $order): string
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('invoices.order', [
            'order' => $order,
        ])->render(), 'UTF-8');
        $dompdf->setPaper('a4');
        $dompdf->render();

        return $dompdf->output();
    }
}
