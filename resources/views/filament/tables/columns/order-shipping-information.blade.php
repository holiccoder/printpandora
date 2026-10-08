@php($order = $getRecord())
<div>
    <div>订单重量：{{ $order->shipping_weight_grams === null ? '—' : \App\Support\WeightFormatter::formatGrams($order->shipping_weight_grams) }}</div>
    <div>承运商：{{ $order->shipping_carrier ?: '—' }}</div>
    <div>快递单号：{{ $order->tracking_number ?: '—' }}</div>
    <div>商品件数：{{ $order->items->sum('quantity') }}</div>
</div>
