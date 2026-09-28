<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_filter_orders_and_see_products_and_notes(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::create([
            'name' => 'Dashboard order products',
            'slug' => 'dashboard-order-products-'.uniqid(),
        ]);
        $product = Product::create([
            'name' => 'Premium Business Cards',
            'slug' => 'premium-business-cards-'.uniqid(),
            'product_category_id' => $category->id,
        ]);

        $matchingOrder = $this->makeOrder($user, [
            'status' => Order::STATUS_PRODUCTION,
            'notes' => 'Please keep the corners sharp.',
        ]);
        $matchingOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 20,
            'subtotal' => 40,
            'options' => [],
        ]);
        $otherOrder = $this->makeOrder($user, [
            'status' => Order::STATUS_SHIPPED,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.orders', ['status' => Order::STATUS_PRODUCTION]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('dashboard/orders')
                ->where('selectedStatus', Order::STATUS_PRODUCTION)
                ->where('statusOptions', Order::statusOptions())
                ->where('orders.total', 1)
                ->where('orders.data.0.id', $matchingOrder->id)
                ->where('orders.data.0.product_names', [$product->name])
                ->where('orders.data.0.notes', 'Please keep the corners sharp.')
                ->missing('orders.data.0.payment_status')
                ->missing('orders.data.0.payment_method')
            );

        $this->assertNotSame($matchingOrder->id, $otherOrder->id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeOrder(User $user, array $attributes = []): Order
    {
        return Order::create(array_merge([
            'user_id' => $user->id,
            'status' => Order::STATUS_CONFIRMED,
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
