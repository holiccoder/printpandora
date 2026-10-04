<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderFileConfirmationReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Reminder: confirm files for order #{$this->order->id}")
            ->greeting("Hello {$this->order->customer_name},")
            ->line("The files for order #{$this->order->id} are waiting for your confirmation.")
            ->line('Please review the files and confirm them so production can begin.')
            ->action('Review order files', route('dashboard.orders.show', $this->order->id))
            ->line('If you have already confirmed the files, you can ignore this reminder.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'category' => CustomerNotification::CATEGORY_SYSTEM,
            'type' => CustomerNotification::CATEGORY_SYSTEM,
            'title' => "Order #{$this->order->id} files need confirmation",
            'body' => 'Please review and confirm the files so production can begin.',
            'action_url' => route('dashboard.orders.show', $this->order->id),
            'event' => 'order_file_confirmation_reminder',
            'order_id' => $this->order->id,
            'order_status' => $this->order->status,
        ];
    }
}
