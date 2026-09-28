<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CustomerNotification extends Notification
{
    public const CATEGORY_SYSTEM = 'system';

    public const CATEGORY_ADMIN = 'admin';

    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $category,
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $actionUrl = null,
        public readonly array $meta = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    /**
     * Keep the notification usable if another channel is enabled later.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'category' => $this->category,
            'type' => $this->category,
            'title' => $this->title,
            'body' => $this->body,
            'action_url' => $this->actionUrl,
            ...$this->meta,
        ];
    }
}
