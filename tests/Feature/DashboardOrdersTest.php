<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductDesignRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
            'weight' => 300,
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
            'options' => [
                'sizes' => 'square',
                'corners' => 'rounded',
            ],
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
                ->where('orders.data.0.weight', 252)
                ->where('orders.data.0.product_names', [$product->name])
                ->where('orders.data.0.products', [[
                    'name' => $product->name,
                    'options' => [
                        'sizes' => 'square',
                        'corners' => 'rounded',
                    ],
                    'weight' => 300,
                ]])
                ->where('orders.data.0.notes', 'Please keep the corners sharp.')
                ->missing('orders.data.0.payment_status')
                ->missing('orders.data.0.payment_method')
            );

        $this->assertNotSame($matchingOrder->id, $otherOrder->id);
    }

    public function test_customer_can_view_and_download_files_from_their_order(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $order = $this->makeOrder($user);
        $designPath = 'product-designs/designs/order-design.pdf';
        $logoPath = 'product-designs/logos/order-logo.png';

        Storage::disk('public')->put($designPath, 'confirmed design');
        Storage::disk('public')->put($logoPath, 'uploaded logo');

        $designRequest = ProductDesignRequest::create([
            'order_id' => $order->id,
            'desgin' => [
                'source' => 'product-page',
                'mode' => 'upload',
                'design_path' => $designPath,
                'logo_path' => $logoPath,
            ],
        ]);

        $downloadUrl = route('dashboard.orders.file', [
            'id' => $order->id,
            'file' => "product-design-{$designRequest->id}-design-0",
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.orders'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('orders.data.0.awaiting_confirmation.0.filename', 'order-design.pdf')
                ->where('orders.data.0.awaiting_confirmation.0.size', strlen('confirmed design'))
                ->where('orders.data.0.awaiting_confirmation.0.download_url', $downloadUrl)
                ->where('orders.data.0.uploaded_files.0.filename', 'order-logo.png')
                ->where('orders.data.0.uploaded_files.0.size', strlen('uploaded logo'))
            );

        $this->actingAs($user)
            ->get($downloadUrl)
            ->assertOk()
            ->assertDownload('order-design.pdf');

        $this->actingAs(User::factory()->create())
            ->get($downloadUrl)
            ->assertNotFound();
    }

    public function test_customer_can_filter_orders_by_order_time_keyword_and_shipping_number(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::create([
            'name' => 'Filter products',
            'slug' => 'filter-products-'.uniqid(),
        ]);
        $product = Product::create([
            'name' => 'Filterable Business Cards',
            'slug' => 'filterable-business-cards-'.uniqid(),
            'product_category_id' => $category->id,
        ]);

        $matchingOrder = $this->makeOrder($user, [
            'status' => Order::STATUS_SHIPPED,
            'tracking_number' => 'SHIP-12345',
        ]);
        $matchingOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 20,
            'subtotal' => 20,
            'options' => [],
        ]);
        $matchingOrder->forceFill([
            'created_at' => CarbonImmutable::parse('2026-09-10 09:00:00'),
        ])->saveQuietly();

        $otherOrder = $this->makeOrder($user, [
            'status' => Order::STATUS_SHIPPED,
            'tracking_number' => 'OTHER-99999',
        ]);
        $otherOrder->forceFill([
            'created_at' => CarbonImmutable::parse('2026-08-10 09:00:00'),
        ])->saveQuietly();

        $this->actingAs($user)
            ->get(route('dashboard.orders', [
                'order_time_start' => '2026-09-01',
                'order_time_end' => '2026-09-30',
                'keyword' => 'Filterable',
                'shipping_number' => '12345',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('filters.order_time_start', '2026-09-01')
                ->where('filters.keyword', 'Filterable')
                ->where('filters.shipping_number', '12345')
                ->missing('filters.shipping_time_start')
                ->missing('filters.shipping_time_end')
                ->where('orders.total', 1)
                ->where('orders.data.0.id', $matchingOrder->id)
            );
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
