<?php

namespace Tests\Unit;

use App\Http\Controllers\DashboardController;
use App\Models\Order;
use App\Models\User;
use App\Notifications\CustomerNotification;
use App\Services\CustomerNotificationService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use ReflectionMethod;
use Tests\TestCase;

class OrderNotificationLanguageTest extends TestCase
{
    public function test_new_order_status_notification_uses_english_status_labels(): void
    {
        Notification::fake();

        $user = User::factory()->make();
        $order = new Order(['status' => Order::STATUS_PRODUCTION]);
        $order->id = 123;
        $order->setRelation('user', $user);

        app(CustomerNotificationService::class)->orderStatusChanged($order, Order::STATUS_PENDING);

        Notification::assertSentTo($user, CustomerNotification::class, function (CustomerNotification $notification) use ($user): bool {
            $data = $notification->toDatabase($user);

            $this->assertSame('Order #123 status updated', $data['title']);
            $this->assertSame('Your order status changed from Pending Payment to In Production.', $data['body']);

            return true;
        });
    }

    public function test_new_order_placed_notification_uses_an_english_status_label(): void
    {
        Notification::fake();

        $user = User::factory()->make();
        $order = new Order(['status' => Order::STATUS_PENDING]);
        $order->id = 123;
        $order->setRelation('user', $user);

        app(CustomerNotificationService::class)->orderPlaced($order);

        Notification::assertSentTo($user, CustomerNotification::class, function (CustomerNotification $notification) use ($user): bool {
            $this->assertSame(
                "We've received your order. Its current status is Pending Payment.",
                $notification->toDatabase($user)['body'],
            );

            return true;
        });
    }

    public function test_dashboard_displays_existing_order_update_in_english(): void
    {
        $notification = new DatabaseNotification;
        $notification->id = 'existing-order-update';
        $notification->data = [
            'category' => CustomerNotification::CATEGORY_SYSTEM,
            'title' => 'Order #123 status updated',
            'body' => 'Your order status changed from 待付款 to 生产中.',
            'event' => 'order_status_changed',
            'order_id' => 123,
            'previous_order_status' => Order::STATUS_PENDING,
            'order_status' => Order::STATUS_PRODUCTION,
        ];

        $payload = (new ReflectionMethod(DashboardController::class, 'notificationPayload'))
            ->invoke(new DashboardController, $notification);

        $this->assertSame('Order #123 status updated', $payload['title']);
        $this->assertSame('Your order status changed from Pending Payment to In Production.', $payload['body']);
    }

    public function test_dashboard_displays_existing_order_placed_notification_in_english(): void
    {
        $notification = new DatabaseNotification;
        $notification->id = 'existing-order-placed';
        $notification->data = [
            'category' => CustomerNotification::CATEGORY_SYSTEM,
            'title' => 'Order #123 received',
            'body' => "We've received your order. Its current status is 待付款.",
            'event' => 'order_placed',
            'order_id' => 123,
            'order_status' => Order::STATUS_PENDING,
        ];

        $payload = (new ReflectionMethod(DashboardController::class, 'notificationPayload'))
            ->invoke(new DashboardController, $notification);

        $this->assertSame('Order #123 received', $payload['title']);
        $this->assertSame("We've received your order. Its current status is Pending Payment.", $payload['body']);
    }

    public function test_dashboard_preserves_administrator_messages(): void
    {
        $notification = new DatabaseNotification;
        $notification->id = 'administrator-message';
        $notification->data = [
            'category' => CustomerNotification::CATEGORY_ADMIN,
            'title' => 'Custom update',
            'body' => 'Please contact us about your order.',
            'event' => 'admin_message',
        ];

        $payload = (new ReflectionMethod(DashboardController::class, 'notificationPayload'))
            ->invoke(new DashboardController, $notification);

        $this->assertSame('Custom update', $payload['title']);
        $this->assertSame('Please contact us about your order.', $payload['body']);
    }
}
