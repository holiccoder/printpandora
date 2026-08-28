<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrderThankYouTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_authenticated_customer_can_view_the_thank_you_page_with_delivery_estimates(): void
    {
        Carbon::setTestNow('2026-08-27 09:00:00');

        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'processing',
            'payment_method' => 'paypal',
            'payment_status' => 'paid',
            'payment_id' => 'PAYPAL-CAPTURE-1',
            'total' => 10,
            'customer_name' => 'Thank You Customer',
            'customer_email' => $user->email,
            'shipping_address' => '1 Main Street',
            'shipping_city' => 'Austin',
            'shipping_state' => 'TX',
            'shipping_zip' => '78701',
            'shipping_country' => 'US',
            'shipping_method' => 'standard',
            'shipping_carrier' => '4PX',
            'shipping_fee' => 5.99,
        ]);

        $this->actingAs($user)
            ->get(route('shop.checkout.thank-you', $order->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('shop/thank-you')
                ->where('order.id', $order->id)
                ->where('order.shipping_method', 'Standard Shipping')
                ->where('order.carrier', '4PX')
                ->where('order.shipping_estimate', '7-12 business days')
                ->where('order.dispatch_date', 'Fri, 28 Aug')
                ->where('order.delivery_date', 'Mon, 14 Sep')
            );
    }

    public function test_guest_cannot_view_an_order_thank_you_page(): void
    {
        $this->get(route('shop.checkout.thank-you', 1))
            ->assertRedirect(route('login'));
    }
}
