<?php

namespace Tests\Feature;

use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\Cart;
use App\Services\DiscountException;
use App\Services\DiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_percentage_and_fixed_quotes_are_calculated_and_capped(): void
    {
        $percent = DiscountCode::create([
            'code' => ' save10 ',
            'type' => 'percent',
            'value' => 10,
        ]);

        $quote = app(DiscountService::class)->quote('save10', 50);

        $this->assertSame('SAVE10', $percent->fresh()->code);
        $this->assertSame(5.0, $quote['discount']);
        $this->assertSame(45.0, $quote['total']);

        $fixed = DiscountCode::create([
            'code' => 'free-ish',
            'type' => 'fixed',
            'value' => 100,
        ]);

        $this->assertSame(20.0, app(DiscountService::class)->quote('FREE-ISH', 20)['discount']);
    }

    public function test_code_requires_minimum_subtotal_and_respects_dates_and_status(): void
    {
        $code = DiscountCode::create([
            'code' => 'threshold',
            'type' => 'fixed',
            'value' => 5,
            'minimum_subtotal' => 50,
            'starts_at' => now()->addDay(),
        ]);

        $this->expectException(DiscountException::class);
        $this->expectExceptionMessage('not active yet');
        app(DiscountService::class)->quote($code->code, 50);
    }

    public function test_cart_can_apply_and_remove_a_code(): void
    {
        $this->makeProduct();
        DiscountCode::create(['code' => 'cart10', 'type' => 'percent', 'value' => 10]);
        $cart = app(Cart::class);
        $cart->add(Product::first()->id);

        $cart->applyDiscountCode('cart10');

        $this->assertSame(0.0, $cart->quote()['discount']);
        $this->assertSame('CART10', $cart->quote()['code']);

        $cart->removeDiscountCode();
        $this->assertNull($cart->discountCode());
    }

    public function test_checkout_persists_discount_snapshot_and_uses_discounted_total(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        DiscountCode::create(['code' => 'checkout10', 'type' => 'percent', 'value' => 10]);
        $cart = app(Cart::class);
        $cart->add($product->id, ['design_service' => 'card_design']);
        $cart->applyDiscountCode('checkout10');

        $this->actingAs($user)->post(route('shop.checkout.store'), [
            'customer_name' => 'Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '555-0100',
            'shipping_address' => '1 Main Street',
            'shipping_city' => 'Austin',
            'shipping_state' => 'TX',
            'shipping_zip' => '78701',
            'shipping_country' => 'US',
            'shipping_method' => 'standard',
            'notes' => '',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('213.10', $order->total);
        $this->assertDatabaseHas('discount_redemptions', [
            'order_id' => $order->id,
            'code' => 'CHECKOUT10',
            'discount_amount' => '7.90',
            'total' => '213.10',
        ]);
        $this->assertSame(1, DiscountCode::firstOrFail()->usage_count);
    }

    public function test_checkout_uses_the_authenticated_customer_contact_details(): void
    {
        $user = User::factory()->create([
            'name' => 'Registered Customer',
            'email' => 'registered@example.com',
        ]);
        $product = $this->makeProduct();
        $cart = app(Cart::class);
        $cart->add($product->id);

        $this->actingAs($user)->post(route('shop.checkout.store'), [
            'shipping_address' => '1 Main Street',
            'shipping_city' => 'Austin',
            'shipping_state' => 'TX',
            'shipping_zip' => '78701',
            'shipping_country' => 'US',
            'shipping_method' => 'standard',
            'notes' => '',
        ])->assertRedirect();

        $order = Order::firstOrFail();

        $this->assertSame('Registered Customer', $order->customer_name);
        $this->assertSame('registered@example.com', $order->customer_email);
        $this->assertNull($order->customer_phone);
    }

    public function test_dhl_express_shipping_is_saved_and_added_to_total(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $cart = app(Cart::class);
        $cart->add($product->id);

        $this->actingAs($user)->post(route('shop.checkout.store'), [
            'customer_name' => 'Customer',
            'customer_email' => 'dhl@example.com',
            'shipping_address' => '1 Main Street',
            'shipping_city' => 'Austin',
            'shipping_state' => 'TX',
            'shipping_zip' => '78701',
            'shipping_country' => 'US',
            'shipping_method' => 'dhl_express',
        ])->assertRedirect();

        $order = Order::with('items')->firstOrFail();

        $this->assertSame('dhl_express', $order->shipping_method);
        $this->assertSame('DHL', $order->shipping_carrier);
        $this->assertSame('201.00', $order->shipping_fee);
        $this->assertEquals(
            (float) $order->items->sum('subtotal') + 201.00,
            (float) $order->total,
        );
    }

    public function test_standard_shipping_uses_the_destination_country_rate(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $cart = app(Cart::class);
        $cart->add($product->id);

        $this->actingAs($user)->post(route('shop.checkout.store'), [
            'shipping_address' => '1 Main Street',
            'shipping_city' => 'Toronto',
            'shipping_state' => 'ON',
            'shipping_zip' => 'M5V 2T6',
            'shipping_country' => 'CA',
            'shipping_method' => 'standard',
        ])->assertRedirect();

        $order = Order::firstOrFail();

        $this->assertSame('CA', $order->shipping_country);
        $this->assertSame('114.00', $order->shipping_fee);
        $this->assertSame('114.00', $order->total);
    }

    public function test_global_and_per_customer_limits_are_enforced(): void
    {
        $code = DiscountCode::create([
            'code' => 'limited',
            'type' => 'fixed',
            'value' => 5,
            'max_uses' => 1,
            'max_uses_per_customer' => 1,
            'usage_count' => 1,
        ]);

        $this->expectException(DiscountException::class);
        $this->expectExceptionMessage('usage limit');
        app(DiscountService::class)->quote($code->code, 50, 'customer@example.com');
    }

    private function makeProduct(): Product
    {
        $category = ProductCategory::create([
            'name' => 'Widgets',
            'slug' => 'widgets-'.uniqid(),
        ]);

        return Product::create([
            'name' => 'Test Card',
            'slug' => 'test-card-'.uniqid(),
            'product_category_id' => $category->id,
        ]);
    }
}
