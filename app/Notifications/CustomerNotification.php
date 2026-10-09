<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
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
        public readonly bool $sendEmail = false,
        public readonly array $meta = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->sendEmail ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello,')
            ->line($this->body);

        if ($this->actionUrl) {
            $message->action('View order details', $this->actionUrl);
        }

        return $message;
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
