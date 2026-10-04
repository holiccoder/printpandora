<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\CustomerNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendOrderConfirmationReminders extends Command
{
    protected $signature = 'orders:send-confirmation-reminders';

    protected $description = 'Send reminders for paid orders waiting for file confirmation';

    public function handle(CustomerNotificationService $notifications): int
    {
        $cutoff = now()->subHours(24);
        $sent = 0;

        Order::query()
            ->where('payment_status', 'paid')
            ->where('status', Order::STATUS_PENDING_CONFIRMATION)
            ->whereNotNull('confirmation_requested_at')
            ->where('confirmation_requested_at', '<=', $cutoff)
            ->whereNull('confirmation_reminded_at')
            ->orderBy('id')
            ->eachById(function (Order $order) use ($notifications, &$sent): void {
                $claimedOrder = DB::transaction(function () use ($order): ?Order {
                    $lockedOrder = Order::query()
                        ->lockForUpdate()
                        ->whereKey($order->getKey())
                        ->firstOrFail();
                    $requestedAt = $lockedOrder->getRawOriginal('confirmation_requested_at');

                    if ($lockedOrder->payment_status !== 'paid'
                        || $lockedOrder->status !== Order::STATUS_PENDING_CONFIRMATION
                        || ! is_string($requestedAt)
                        || Carbon::parse($requestedAt)->isAfter(now()->subHours(24))
                        || $lockedOrder->confirmation_reminded_at !== null) {
                        return null;
                    }

                    $lockedOrder->update(['confirmation_reminded_at' => now()]);

                    return $lockedOrder->fresh();
                });

                if (! $claimedOrder) {
                    return;
                }

                $notifications->orderFileConfirmationReminder($claimedOrder);
                $sent++;
            });

        $this->info("Sent {$sent} order confirmation reminder(s).");

        return self::SUCCESS;
    }
}
