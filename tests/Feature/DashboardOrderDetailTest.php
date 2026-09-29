<?php

namespace Tests\Feature;

use App\Models\DiscountRedemption;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductDesignRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardOrderDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_their_order_detail_sections(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $category = ProductCategory::create([
            'name' => 'Dashboard detail products',
            'slug' => 'dashboard-detail-products-'.uniqid(),
        ]);
        $product = Product::create([
            'name' => 'Detail Business Cards',
            'slug' => 'detail-business-cards-'.uniqid(),
            'featured_image' => '/images/products/detail-business-cards.webp',
            'product_category_id' => $category->id,
        ]);
        $order = Order::create([
            'user_id' => $user->id,
            'status' => Order::STATUS_PENDING_CONFIRMATION,
            'total' => 48.5,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '+1 555 0100',
            'shipping_address' => '1 Main Street',
            'shipping_city' => 'Austin',
            'shipping_state' => 'TX',
            'shipping_zip' => '78701',
            'shipping_country' => 'US',
            'shipping_method' => 'standard',
            'shipping_carrier' => 'USPS',
            'shipping_fee' => 8.5,
            'tracking_number' => 'TRACK-123',
            'tracking_url' => 'https://tracking.example.test/TRACK-123',
            'notes' => 'Please keep the artwork centered.',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 20,
            'subtotal' => 40,
            'options' => [
                'size' => 'standard',
                'finish' => 'matte',
            ],
        ]);

        DiscountRedemption::create([
            'order_id' => $order->id,
            'code' => 'WELCOME15',
            'customer_email' => $user->email,
            'subtotal' => 55,
            'discount_amount' => 15,
            'total' => 48.5,
        ]);

        $designPath = 'product-designs/dashboard-detail/design.pdf';
        Storage::disk('public')->put($designPath, 'design');
        ProductDesignRequest::create([
            'order_id' => $order->id,
            'desgin' => [
                'design_path' => $designPath,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.orders.show', ['id' => $order->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('dashboard/order-show')
                ->where('order.id', $order->id)
                ->where('order.status', Order::STATUS_PENDING_CONFIRMATION)
                ->where('order.items.0.product.name', $product->name)
                ->where('order.items.0.product.featured_image', $product->featured_image)
                ->where('order.items.0.options.size', 'standard')
                ->where('order.notes', 'Please keep the artwork centered.')
                ->where('order.coupon_code', 'WELCOME15')
                ->where('order.contact.phone', '+1 555 0100')
                ->where('order.address.city', 'Austin')
                ->where('order.shipping.carrier', 'USPS')
                ->where('order.shipping.number', 'TRACK-123')
                ->where('order.shipping.tracking_link', 'https://tracking.example.test/TRACK-123')
                ->where('order.files.awaiting_confirmation.0.filename', 'design.pdf')
            );
    }

    public function test_customer_cannot_view_another_customer_order_detail(): void
    {
        $owner = User::factory()->create();
        $order = Order::create([
            'user_id' => $owner->id,
            'status' => Order::STATUS_CONFIRMED,
            'total' => 20,
            'customer_name' => $owner->name,
            'customer_email' => $owner->email,
            'shipping_address' => '1 Main Street',
            'shipping_city' => 'Austin',
            'shipping_zip' => '78701',
            'shipping_country' => 'US',
            'shipping_method' => 'standard',
            'shipping_carrier' => 'USPS',
            'shipping_fee' => 0,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard.orders.show', ['id' => $order->id]))
            ->assertNotFound();
    }
}
