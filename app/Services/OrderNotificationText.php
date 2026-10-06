<?php

namespace App\Services;

use App\Models\Order;

class OrderNotificationText
{
    /**
     * @return array{title: string, body: string}
     */
    public static function placed(int|string $orderId, string $status): array
    {
        return [
            'title' => "Order #{$orderId} received",
            'body' => "We've received your order. Its current status is ".self::statusLabel($status).'.',
        ];
    }

    /**
     * @return array{title: string, body: string}
     */
    public static function statusChanged(int|string $orderId, string $status, ?string $previousStatus): array
    {
        $currentLabel = self::statusLabel($status);
        $previousLabel = $previousStatus !== null ? self::statusLabel($previousStatus) : null;

        return [
            'title' => "Order #{$orderId} status updated",
            'body' => $previousLabel !== null && $previousLabel !== $currentLabel
                ? "Your order status changed from {$previousLabel} to {$currentLabel}."
                : "Your order status is now {$currentLabel}.",
        ];
    }

    /**
     * Rebuild system order updates from stored status codes so older notifications
     * use the same customer-facing English text as newly created ones.
     *
     * @param  array<string, mixed>  $data
     * @return array{title: string, body: string}|null
     */
    public static function fromStored(array $data): ?array
    {
        $event = $data['event'] ?? null;
        $orderId = $data['order_id'] ?? null;
        $status = $data['order_status'] ?? null;

        if (! in_array($event, ['order_placed', 'order_status_changed'], true)
            || ! is_numeric($orderId)
            || ! is_string($status)) {
            return null;
        }

        if ($event === 'order_placed') {
            return self::placed((string) $orderId, $status);
        }

        $previousStatus = $data['previous_order_status'] ?? null;

        return self::statusChanged(
            (string) $orderId,
            $status,
            is_string($previousStatus) ? $previousStatus : null,
        );
    }

    private static function statusLabel(string $status): string
    {
        return [
            Order::STATUS_PENDING => 'Pending Payment',
            Order::STATUS_PENDING_REVIEW => 'Pending Review',
            Order::STATUS_NEEDS_REUPLOAD => 'Needs File Re-upload',
            Order::STATUS_PENDING_CONFIRMATION => 'Pending Confirmation',
            Order::STATUS_CONFIRMED => 'Confirmed',
            Order::STATUS_PRODUCTION => 'In Production',
            Order::STATUS_SHIPPED => 'Shipped',
        ][$status] ?? $status;
    }
}
