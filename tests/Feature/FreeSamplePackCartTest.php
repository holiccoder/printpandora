<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\Cart;
use App\Services\OrderWeightService;
use App\Services\ShippingService;
use App\Support\FreeSamplePackProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreeSamplePackCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_request_adds_the_pack_and_redirects_to_the_cart(): void
    {
        $this->post(route('shop.cart.add.free-sample-pack'))
            ->assertRedirect(route('shop.cart'));

        $cart = app(Cart::class)->all();

        $this->assertCount(1, $cart);
        $this->assertSame(
            FreeSamplePackProduct::SLUG,
            reset($cart)['slug'],
        );
    }

    public function test_free_sample_pack_endpoint_adds_a_free_fixed_weight_cart_line(): void
    {
        $response = $this->postJson(route('shop.cart.add.free-sample-pack'));

        $product = Product::query()
            ->where('slug', FreeSamplePackProduct::SLUG)
            ->firstOrFail();

        $response
            ->assertOk()
            ->assertJson([
                'count' => 1,
                'item_key' => $product->id.':default',
                'message' => 'Added to cart',
            ]);

        $cart = app(Cart::class)->all();
        $item = reset($cart);

        $this->assertIsArray($item);
        $this->assertSame($product->id, $item['product_id']);
        $this->assertSame('Free Business Card Sample Pack', $item['name']);
        $this->assertSame(0.0, (float) $item['price']);
        $this->assertSame(1, $item['quantity']);
        $this->assertSame(500, $product->shipping_weight_grams);
        $this->assertSame(500, app(OrderWeightService::class)->wholeGrams(
            app(OrderWeightService::class)->forCart($cart),
        ));
        $this->assertSame(0.0, app(Cart::class)->quote()['total']);
        $this->assertGreaterThan(
            0.0,
            app(ShippingService::class)->fee('standard', 'US', 500),
        );
    }
}
