<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Notifications\CustomerNotification;
use App\Notifications\OrderFileConfirmationReminder;

class CustomerNotificationService
{
    public function orderPlaced(Order $order): void
    {
        $user = $order->user;

        if (! $user) {
            return;
        }

        $user->notify(new CustomerNotification(
            category: CustomerNotification::CATEGORY_SYSTEM,
            title: "Order #{$order->id} received",
            body: "We've received your order. Its current status is ".Order::statusLabel($order->status).'.',
            actionUrl: route('dashboard.orders.show', $order->id),
            meta: [
                'event' => 'order_placed',
                'order_id' => $order->id,
                'order_status' => $order->status,
            ],
        ));
    }

    public function orderStatusChanged(Order $order, ?string $previousStatus = null): void
    {
        $user = $order->user;

        if (! $user) {
            return;
        }

        $previousLabel = $previousStatus !== null
            ? Order::statusLabel($previousStatus)
            : null;
        $currentLabel = Order::statusLabel($order->status);
        $body = "Your order status is now {$currentLabel}.";

        if ($previousLabel !== null && $previousLabel !== $currentLabel) {
            $body = "Your order status changed from {$previousLabel} to {$currentLabel}.";
        }

        $user->notify(new CustomerNotification(
            category: CustomerNotification::CATEGORY_SYSTEM,
            title: "Order #{$order->id} status updated",
            body: $body,
            actionUrl: route('dashboard.orders.show', $order->id),
            meta: [
                'event' => 'order_status_changed',
                'order_id' => $order->id,
                'order_status' => $order->status,
                'previous_order_status' => $previousStatus,
            ],
        ));
    }

    public function orderFileReviewRejected(Order $order, string $reason, int $version): void
    {
        $user = $order->user;

        if (! $user) {
            return;
        }

        $user->notify(new CustomerNotification(
            category: CustomerNotification::CATEGORY_SYSTEM,
            title: "Order #{$order->id} files need to be re-uploaded",
            body: "The files in version {$version} were not approved: {$reason}",
            actionUrl: route('dashboard.orders.show', $order->id),
            meta: [
                'event' => 'order_file_review_rejected',
                'order_id' => $order->id,
                'order_status' => $order->status,
                'file_version' => $version,
                'review_reason' => $reason,
            ],
        ));
    }

    public function orderFileConfirmationReminder(Order $order): void
    {
        $user = $order->user;

        if (! $user) {
            return;
        }

        $user->notify(new OrderFileConfirmationReminder($order));
    }

    /**
     * @param  iterable<int, User>  $users
     * @param  array<string, mixed>  $meta
     */
    public function sendAdminNotification(
        iterable $users,
        string $title,
        string $body,
        ?string $actionUrl = null,
        array $meta = [],
    ): void {
        $notification = new CustomerNotification(
            category: CustomerNotification::CATEGORY_ADMIN,
            title: $title,
            body: $body,
            actionUrl: $actionUrl,
            meta: [
                'event' => 'admin_message',
                ...$meta,
            ],
        );

        foreach ($users as $user) {
            $user->notify($notification);
        }
    }
}
