<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderResourceFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();
        $this->actingAs(Admin::factory()->create(), 'admin');
    }

    public function test_order_list_exposes_the_requested_filters_and_statuses(): void
    {
        Livewire::test(ListOrders::class)
            ->assertTableFilterExists('status', static fn ($filter): bool => $filter->isMultiple())
            ->assertTableFilterExists('order_date')
            ->assertTableFilterExists('shipped_date')
            ->assertTableFilterExists('recipient')
            ->assertTableFilterExists('keyword')
            ->assertTableFilterExists('shipping_tracking')
            ->assertTableFilterExists('order_number');

        $this->assertSame([
            'pending',
            'confirmed',
            'pending_modification',
            'pending_production',
            'production',
            'pending_shipment',
            'shipped',
            'cancelled',
        ], array_keys(Order::statusOptions()));
    }

    public function test_requested_text_filters_match_order_data(): void
    {
        $category = ProductCategory::create([
            'name' => 'Filter products',
            'slug' => 'filter-products-'.uniqid(),
        ]);
        $product = Product::create([
            'name' => 'Keyword Business Cards',
            'slug' => 'keyword-business-cards-'.uniqid(),
            'product_category_id' => $category->id,
        ]);

        $matchingOrder = $this->makeOrder([
            'customer_name' => 'Alice Receiver',
            'customer_email' => 'alice@example.com',
            'shipping_carrier' => 'DHL Express',
            'tracking_number' => 'DHL-FILTER-123',
        ]);
        $matchingOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 20,
            'subtotal' => 20,
            'options' => [],
        ]);
        $otherOrder = $this->makeOrder([
            'customer_name' => 'Bob Receiver',
            'customer_email' => 'bob@example.com',
            'shipping_carrier' => 'Standard',
            'tracking_number' => 'STD-999',
        ]);

        Livewire::test(ListOrders::class)
            ->filterTable('recipient', ['value' => 'alice@example.com'])
            ->assertCanSeeTableRecords([$matchingOrder])
            ->assertCanNotSeeTableRecords([$otherOrder])
            ->resetTableFilters()
            ->filterTable('keyword', ['value' => 'Keyword Business Cards'])
            ->assertCanSeeTableRecords([$matchingOrder])
            ->assertCanNotSeeTableRecords([$otherOrder])
            ->resetTableFilters()
            ->filterTable('shipping_tracking', ['value' => 'DHL-FILTER-123'])
            ->assertCanSeeTableRecords([$matchingOrder])
            ->assertCanNotSeeTableRecords([$otherOrder])
            ->resetTableFilters()
            ->filterTable('order_number', ['value' => (string) $matchingOrder->id])
            ->assertCanSeeTableRecords([$matchingOrder])
            ->assertCanNotSeeTableRecords([$otherOrder]);
    }

    public function test_marking_an_order_as_shipped_records_the_shipping_time(): void
    {
        $order = $this->makeOrder();

        $order->update(['status' => Order::STATUS_SHIPPED]);

        $this->assertNotNull($order->fresh()->shipped_at);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeOrder(array $attributes = []): Order
    {
        $user = User::factory()->create();

        return Order::create(array_merge([
            'user_id' => $user->id,
            'status' => Order::STATUS_CONFIRMED,
            'total' => 20,
            'customer_name' => 'Default Receiver',
            'customer_email' => 'default@example.com',
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
