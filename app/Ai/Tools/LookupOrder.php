<?php

namespace App\Ai\Tools;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * Lets the support agent look up the authenticated customer's own orders.
 * Guests are told to sign in first — order data is never exposed without
 * ownership.
 */
class LookupOrder implements Tool
{
    public function __construct(protected ?User $user)
    {
        //
    }

    public function description(): string
    {
        return 'Look up the current customer\'s orders. Use when the customer asks about their orders. '
            .'Without an order_id it returns the 3 most recent orders; with an order_id it returns that '
            .'order\'s details. Only works for signed-in customers.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'order_id' => $schema->integer()
                ->description('The numeric order ID, if the customer provided one.'),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $this->user) {
            return 'The customer is not signed in. Ask them to sign in to their InkPavo account '
                .'to check their orders (or create a support ticket for help).';
        }

        $query = Order::query()
            ->where('user_id', $this->user->id)
            ->with('items.product:id,name')
            ->latest();

        $orderId = $request['order_id'] ?? null;

        if ($orderId !== null) {
            $order = (clone $query)->find($orderId);

            if (! $order) {
                return "No order #{$orderId} found for this customer. It may belong to a different "
                    .'account — ask them to double-check the order number or sign in with the '
                    .'account they used at checkout.';
            }

            return $this->describe($order);
        }

        $orders = $query->limit(3)->get();

        if ($orders->isEmpty()) {
            return 'This customer has no orders yet.';
        }

        return "The customer's {$orders->count()} most recent order(s):\n\n"
            .$orders->map(fn (Order $order) => $this->describe($order))->implode("\n\n");
    }

    protected function describe(Order $order): string
    {
        $items = $order->items
            ->map(fn ($item) => "{$item->quantity}x ".($item->product->name ?? "product #{$item->product_id}"))
            ->implode(', ');

        return implode("\n", array_filter([
            "Order #{$order->id}",
            "Placed: {$order->created_at->toDateString()}",
            "Status: {$order->status}; payment: {$order->payment_status}",
            'Total: $'.$order->total,
            $items !== '' ? "Items: {$items}" : null,
            $order->tracking_number ? "Tracking number: {$order->tracking_number}" : null,
        ]));
    }
}
