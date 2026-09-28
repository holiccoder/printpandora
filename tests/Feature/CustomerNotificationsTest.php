<?php

namespace Tests\Feature;

use App\Filament\Resources\CustomerNotificationResource\Pages\CreateCustomerNotification;
use App\Filament\Resources\CustomerNotificationResource\Pages\ListCustomerNotifications;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use App\Notifications\CustomerNotification;
use App\Services\CustomerNotificationService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitting_an_order_creates_a_system_notification(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, ['checkout_token' => 'checkout-token']);

        $order->update(['checkout_token' => null]);

        $notification = $user->notifications()->firstOrFail();

        $this->assertSame(CustomerNotification::class, $notification->type);
        $this->assertSame(CustomerNotification::CATEGORY_SYSTEM, $notification->data['category']);
        $this->assertSame('order_placed', $notification->data['event']);
        $this->assertSame($order->id, $notification->data['order_id']);
    }

    public function test_order_status_changes_create_a_system_notification(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user);

        $order->update(['status' => Order::STATUS_PRODUCTION]);

        $notification = $user->notifications()->firstOrFail();

        $this->assertSame('order_status_changed', $notification->data['event']);
        $this->assertSame(Order::STATUS_PENDING, $notification->data['previous_order_status']);
        $this->assertSame(Order::STATUS_PRODUCTION, $notification->data['order_status']);
    }

    public function test_customers_can_read_and_mark_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, ['checkout_token' => 'checkout-token']);
        $order->update(['checkout_token' => null]);
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->get(route('dashboard.notifications'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard/notifications')
                ->where('unreadCount', 1)
                ->has('notifications.data', 1));

        $this->actingAs($user)
            ->patch(route('dashboard.notifications.read', $notification->getKey()))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_admin_can_send_an_admin_notification_to_multiple_customers(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $admin = Admin::factory()->create();

        app(CustomerNotificationService::class)->sendAdminNotification(
            users: [$firstUser, $secondUser],
            title: 'Holiday schedule',
            body: 'Our holiday schedule has been updated.',
        );

        $this->assertSame(1, $firstUser->notifications()->count());
        $this->assertSame(1, $secondUser->notifications()->count());
        $this->assertSame(
            CustomerNotification::CATEGORY_ADMIN,
            $firstUser->notifications()->firstOrFail()->data['category'],
        );

        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();
        $this->actingAs($admin, 'admin');

        Livewire::test(ListCustomerNotifications::class)
            ->assertCanSeeTableRecords([$firstUser->notifications()->firstOrFail()]);

        $this->get('/admin/notifications')->assertOk();
    }

    public function test_admin_notification_form_sends_to_selected_customers(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $admin = Admin::factory()->create();

        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();
        $this->actingAs($admin, 'admin');

        Livewire::test(CreateCustomerNotification::class)
            ->set('data.title', 'Maintenance')
            ->set('data.body', 'The site will be maintained tonight.')
            ->set('data.recipient_ids', [$firstUser->id, $secondUser->id])
            ->set('data.send_to_all', false)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertSame(1, $firstUser->notifications()->count());
        $this->assertSame(1, $secondUser->notifications()->count());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeOrder(User $user, array $attributes = []): Order
    {
        return Order::create(array_merge([
            'user_id' => $user->id,
            'status' => Order::STATUS_PENDING,
            'checkout_token' => null,
            'total' => 20,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'shipping_address' => '1 Main Street',
            'shipping_city' => 'Austin',
            'shipping_zip' => '78701',
            'shipping_country' => 'US',
            'shipping_method' => 'standard',
            'shipping_carrier' => 'Standard',
            'shipping_fee' => 0,
        ], $attributes));
    }
}
