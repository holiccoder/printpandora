@php
    $awaitingFiles = $files['awaiting_confirmation'] ?? [];
    $confirmedFiles = $files['confirmed_files'] ?? [];
    $latestRejection = $files['latest_rejection'] ?? null;
    $uploadAction = $action->getModalAction('upload');
    $confirmAction = $action->getModalAction('confirmForCustomer');
    $rejectAction = $action->getModalAction('rejectReview');
    $deleteAction = $action->getModalAction('deleteFile');
    $canReviewFiles = $order->payment_status === 'paid' && in_array($order->status, [
        \App\Models\Order::STATUS_PENDING_REVIEW,
        \App\Models\Order::STATUS_PENDING_CONFIRMATION,
    ], true);
    $canConfirmFiles = $order->payment_status === 'paid'
        && $order->status === \App\Models\Order::STATUS_PENDING_CONFIRMATION;
@endphp

<div class="space-y-8">
    @if ($latestRejection && filled($latestRejection['reason'] ?? null))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-100">
            <p class="font-semibold">审核不通过理由（第 {{ $latestRejection['version'] ?? '-' }} 版）</p>
            <p class="mt-1 whitespace-pre-wrap">{{ $latestRejection['reason'] }}</p>
        </div>
    @endif

    <section class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">待确认文件</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    客户和管理员上传但尚未由客户确认的文件。
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($uploadAction)
                    {{ $uploadAction }}
                @endif
                @if ($confirmAction && $canConfirmFiles && count($awaitingFiles) > 0)
                    {{ $confirmAction }}
                @endif
                @if ($rejectAction && $canReviewFiles && count($awaitingFiles) > 0)
                    {{ $rejectAction }}
                @endif
                <a
                    href="{{ route('admin.orders.files.zip', ['id' => $order->getKey()]) }}"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3 py-2 text-sm font-semibold text-white transition hover:bg-gray-700"
                >
                    <x-filament::icon icon="heroicon-o-archive-box-arrow-down" class="size-4" />
                    下载 ZIP
                </a>
            </div>
        </div>

        @include('filament.pages.order-files-table', [
            'files' => $awaitingFiles,
            'deleteAction' => $deleteAction,
            'showUploader' => true,
            'empty' => '暂无等待确认的文件。',
        ])
    </section>

    <section class="space-y-4">
        <div>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">已确认文件</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                已由客户确认的当前版本和历史版本。
            </p>
        </div>

        @include('filament.pages.order-files-table', [
            'files' => $confirmedFiles,
            'deleteAction' => null,
            'showUploader' => true,
            'empty' => '暂无已确认的文件。',
        ])
    </section>
</div>
