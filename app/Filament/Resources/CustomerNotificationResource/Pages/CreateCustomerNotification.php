<?php

namespace App\Filament\Resources\CustomerNotificationResource\Pages;

use App\Filament\Resources\CustomerNotificationResource;
use App\Models\User;
use App\Notifications\CustomerNotification as CustomerNotificationMessage;
use App\Services\CustomerNotificationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Arr;

class CreateCustomerNotification extends CreateRecord
{
    protected static string $resource = CustomerNotificationResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $sendToAll = (bool) ($data['send_to_all'] ?? false);
        $recipientIds = array_values(array_unique(array_filter(
            array_map(
                static fn (mixed $id): int => (int) $id,
                (array) ($data['recipient_ids'] ?? []),
            ),
            static fn (int $id): bool => $id > 0,
        )));

        $users = User::query()
            ->when(! $sendToAll, fn ($query) => $query->whereKey($recipientIds))
            ->orderBy('id')
            ->get();

        abort_if($users->isEmpty(), 422, '至少选择一个客户。');

        $payload = Arr::only($data, ['title', 'body', 'action_url']);
        app(CustomerNotificationService::class)->sendAdminNotification(
            users: $users,
            title: (string) $payload['title'],
            body: (string) $payload['body'],
            actionUrl: filled($payload['action_url'] ?? null) ? (string) $payload['action_url'] : null,
            meta: [
                'admin_id' => auth('admin')->id(),
            ],
        );

        return DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $users->firstOrFail()->getKey())
            ->where('type', CustomerNotificationMessage::class)
            ->latest()
            ->firstOrFail();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return '通知已发送';
    }
}
