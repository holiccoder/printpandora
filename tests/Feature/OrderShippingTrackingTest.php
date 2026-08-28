<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderShippingTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();
        $this->actingAs(Admin::factory()->create(), 'admin');
    }

    public function test_admin_can_save_tracking_details_and_mark_an_order_as_shipped(): void
    {
        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'processing',
            'total' => 49.99,
            'customer_name' => 'Tracking Customer',
            'customer_email' => 'tracking@example.com',
            'shipping_address' => '1 Main Street',
            'shipping_city' => 'Austin',
            'shipping_zip' => '78701',
            'shipping_country' => 'US',
            'shipping_method' => 'dhl_express',
            'shipping_carrier' => 'DHL Express',
            'shipping_fee' => 12.00,
        ]);

        Livewire::test(ListOrders::class)
            ->callTableAction('addShippingTracking', $order, [
                'tracking_number' => 'DHL-TRACK-123',
                'tracking_url' => 'https://www.dhl.com/track?id=DHL-TRACK-123',
            ])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'tracking_number' => 'DHL-TRACK-123',
            'tracking_url' => 'https://www.dhl.com/track?id=DHL-TRACK-123',
            'status' => 'shipped',
        ]);
    }
}
