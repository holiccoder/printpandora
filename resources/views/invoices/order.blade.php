<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->invoice_number }}</title>
    <style>
        @page { margin: 32px; }
        body { color: #262626; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.45; }
        h1 { color: #800020; font-size: 25px; margin: 0; }
        h2 { color: #800020; font-size: 13px; margin: 0 0 6px; }
        p { margin: 0 0 4px; }
        .header { border-bottom: 2px solid #800020; padding-bottom: 18px; }
        .brand { color: #800020; font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        .meta { color: #525252; text-align: right; }
        .meta strong { color: #262626; }
        .columns { margin: 24px 0; }
        .column { display: inline-block; vertical-align: top; width: 48%; }
        .column + .column { margin-left: 3%; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #f5f5f5; color: #525252; font-size: 10px; text-align: left; text-transform: uppercase; }
        th, td { border-bottom: 1px solid #e5e5e5; padding: 8px 7px; vertical-align: top; }
        .number { text-align: right; white-space: nowrap; }
        .totals { margin-top: 18px; margin-left: 56%; width: 44%; }
        .totals td { border-bottom: 0; padding: 4px 7px; }
        .totals .grand-total td { border-top: 2px solid #800020; color: #800020; font-size: 14px; font-weight: bold; padding-top: 9px; }
        .footer { border-top: 1px solid #e5e5e5; color: #737373; margin-top: 34px; padding-top: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td style="border: 0; padding: 0;">
                    <div class="brand">InkPavo</div>
                    <div>Professional printing for your business.</div>
                </td>
                <td class="meta" style="border: 0; padding: 0;">
                    <h1>Invoice</h1>
                    <p><strong>{{ $order->invoice_number }}</strong></p>
                    <p>Issued {{ $order->invoice_issued_at?->format('F j, Y') }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="columns">
        <div class="column">
            <h2>Bill to</h2>
            <p>{{ $order->customer_name }}</p>
            <p>{{ $order->customer_email }}</p>
            @if ($order->customer_phone)
                <p>{{ $order->customer_phone }}</p>
            @endif
        </div>
        <div class="column">
            <h2>Ship to</h2>
            <p>{{ $order->shipping_address }}</p>
            <p>{{ $order->shipping_city }}@if ($order->shipping_state), {{ $order->shipping_state }}@endif {{ $order->shipping_zip }}</p>
            <p>{{ $order->shipping_country }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="number">Qty</th>
                <th class="number">Unit price</th>
                <th class="number">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>
                        {{ $item->product?->name ?: 'Printed product' }}
                        @if ($item->options)
                            <div style="color: #737373; font-size: 9px; margin-top: 3px;">{{ json_encode($item->options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</div>
                        @endif
                    </td>
                    <td class="number">{{ $item->quantity }}</td>
                    <td class="number">${{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="number">${{ number_format((float) $item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $subtotal = (float) $order->items->sum('subtotal');
        $discount = (float) ($order->discountRedemption?->discount_amount ?? 0);
        $shippingFee = (float) ($order->shipping_fee ?? 0);
    @endphp

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="number">${{ number_format($subtotal, 2) }}</td>
        </tr>
        @if ($discount > 0)
            <tr>
                <td>Discount{{ $order->discountRedemption?->code ? ' ('.$order->discountRedemption->code.')' : '' }}</td>
                <td class="number">-${{ number_format($discount, 2) }}</td>
            </tr>
        @endif
        @if ($shippingFee > 0)
            <tr>
                <td>Shipping</td>
                <td class="number">${{ number_format($shippingFee, 2) }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td>Total paid</td>
            <td class="number">${{ number_format((float) $order->total, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        <p>Payment status: Paid{{ $order->payment_method ? ' via '.$order->payment_method : '' }}.</p>
        @if ($order->payment_id)
            <p>Payment reference: {{ $order->payment_id }}</p>
        @endif
        <p>Thank you for choosing InkPavo.</p>
    </div>
</body>
</html>
