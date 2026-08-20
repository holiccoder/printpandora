<?php

namespace App\Ai\Tools;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * Reports the shipping/tracking state of the authenticated customer's order
 * from the stored 4PX data. Read-only — never calls the live 4PX API from a
 * chat request.
 */
class TrackShipment implements Tool
{
    public function __construct(protected ?User $user)
    {
        //
    }

    public function description(): string
    {
        return 'Get shipping and tracking information for one of the current customer\'s orders. '
            .'Use when the customer asks where their order is or about delivery progress. '
            .'Only works for signed-in customers.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'order_id' => $schema->integer()
                ->description('The numeric order ID. Omit to track the most recent order.'),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $this->user) {
            return 'The customer is not signed in. Ask them to sign in to their InkPavo account '
                .'to track an order (or create a support ticket for help).';
        }

        $query = Order::query()->where('user_id', $this->user->id)->latest();

        $orderId = $request['order_id'] ?? null;
        $order = $orderId !== null ? $query->find($orderId) : $query->first();

        if (! $order) {
            return $orderId !== null
                ? "No order #{$orderId} found for this customer."
                : 'This customer has no orders yet.';
        }

        return $this->describe($order);
    }

    protected function describe(Order $order): string
    {
        $lines = ["Order #{$order->id} — status: {$order->status}"];

        if ($order->tracking_number) {
            $lines[] = "Tracking number: {$order->tracking_number}";
        }

        if ($order->tracking_url) {
            $lines[] = "Tracking page: {$order->tracking_url}";
        }

        $events = collect((array) data_get($order->fourpx_tracking_response, 'data.trackingList', []))
            ->take(3)
            ->map(function ($event) {
                $content = data_get($event, 'trackingContent');
                $time = data_get($event, 'trackingTime') ?? data_get($event, 'gmtCreate');

                return $content ? trim(($time ? "{$time}: " : '').$content) : null;
            })
            ->filter()
            ->values();

        if ($events->isNotEmpty()) {
            $lines[] = 'Latest tracking updates:';
            $lines[] = $events->map(fn (string $event) => "- {$event}")->implode("\n");
        } elseif ($order->status === 'shipped') {
            $lines[] = 'The order has shipped, but detailed checkpoints are not available yet.';
        } else {
            $lines[] = 'The order has not shipped yet.';
        }

        return implode("\n", $lines);
    }
}
