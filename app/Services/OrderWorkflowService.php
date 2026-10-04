<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class OrderWorkflowService
{
    private const AWAITING_FILE_STATUS = 'awaiting_confirmation';

    public function transition(Order $order, string $targetStatus): Order
    {
        return DB::transaction(function () use ($order, $targetStatus): Order {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->whereKey($order->getKey())
                ->firstOrFail();

            $this->assertCanTransition($lockedOrder, $targetStatus);

            if ($lockedOrder->status === $targetStatus) {
                return $lockedOrder->fresh();
            }

            $attributes = ['status' => $targetStatus];

            if ($targetStatus === Order::STATUS_PENDING_CONFIRMATION) {
                $attributes['confirmation_requested_at'] = now();
                $attributes['confirmation_reminded_at'] = null;
            } elseif (in_array($targetStatus, [
                Order::STATUS_PENDING_REVIEW,
                Order::STATUS_NEEDS_REUPLOAD,
            ], true)) {
                $attributes['confirmation_requested_at'] = null;
                $attributes['confirmation_reminded_at'] = null;
            }

            $lockedOrder->update($attributes);

            return $lockedOrder->fresh();
        });
    }

    public function assertCanTransition(Order $order, string $targetStatus): void
    {
        if ($order->status === $targetStatus) {
            return;
        }

        if (! array_key_exists($targetStatus, Order::statusOptions())) {
            throw ValidationException::withMessages([
                'status' => '订单状态无效。',
            ]);
        }

        $fromStatus = $order->status;

        if ($targetStatus !== Order::STATUS_PENDING
            && $order->payment_status !== 'paid') {
            throw ValidationException::withMessages([
                'status' => '订单付款完成后才能进入文件审核流程。',
            ]);
        }

        $allowed = match ($targetStatus) {
            Order::STATUS_PENDING => [
                Order::STATUS_PENDING,
                Order::STATUS_PENDING_REVIEW,
            ],
            Order::STATUS_PENDING_REVIEW => [
                Order::STATUS_PENDING,
                Order::STATUS_PENDING_REVIEW,
                Order::STATUS_NEEDS_REUPLOAD,
                Order::STATUS_PENDING_CONFIRMATION,
            ],
            Order::STATUS_NEEDS_REUPLOAD => [
                Order::STATUS_PENDING_REVIEW,
                Order::STATUS_PENDING_CONFIRMATION,
            ],
            Order::STATUS_PENDING_CONFIRMATION => [
                Order::STATUS_PENDING_REVIEW,
                Order::STATUS_PENDING_CONFIRMATION,
            ],
            Order::STATUS_CONFIRMED => [Order::STATUS_PENDING_CONFIRMATION],
            Order::STATUS_PRODUCTION => [
                Order::STATUS_CONFIRMED,
                Order::STATUS_PRODUCTION,
            ],
            Order::STATUS_SHIPPED => [
                Order::STATUS_PRODUCTION,
                Order::STATUS_SHIPPED,
            ],
            default => [],
        };

        if (! in_array($fromStatus, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    '订单不能从“%s”变更为“%s”。',
                    Order::statusLabel($fromStatus),
                    Order::statusLabel($targetStatus),
                ),
            ]);
        }

        if ($targetStatus === Order::STATUS_PENDING_CONFIRMATION
            && ! $this->hasAwaitingFiles($order)) {
            throw ValidationException::withMessages([
                'files' => '提交客户确认前至少需要一个待确认文件。',
            ]);
        }

        if ($targetStatus === Order::STATUS_CONFIRMED
            && ! $this->hasAwaitingFiles($order)) {
            throw ValidationException::withMessages([
                'files' => '确认订单前至少需要一个待确认文件。',
            ]);
        }

        if ($targetStatus === Order::STATUS_PENDING
            && ! in_array($order->payment_status, ['pending', 'failed', 'refunded', 'reversed'], true)) {
            throw ValidationException::withMessages([
                'status' => '当前付款状态不能回到待付款。',
            ]);
        }
    }

    public function canTransition(Order $order, string $targetStatus): bool
    {
        try {
            $this->assertCanTransition($order, $targetStatus);

            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    private function hasAwaitingFiles(Order $order): bool
    {
        if (! Schema::hasTable('order_files')) {
            return false;
        }

        $version = OrderFile::query()
            ->where('order_id', $order->getKey())
            ->where('status', self::AWAITING_FILE_STATUS)
            ->max('version');

        return $version !== null && OrderFile::query()
            ->where('order_id', $order->getKey())
            ->where('version', $version)
            ->where('status', self::AWAITING_FILE_STATUS)
            ->exists();
    }
}
