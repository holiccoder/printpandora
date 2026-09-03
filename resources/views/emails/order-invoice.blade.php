<p>Hello {{ $order->customer_name }},</p>

<p>Thank you for your payment. Your invoice for order #{{ $order->id }} is attached to this email.</p>

<p>Invoice number: <strong>{{ $order->invoice_number }}</strong></p>

<p>If you have any questions about your order, please contact our support team.</p>

<p>Thank you,<br>InkPavo</p>
