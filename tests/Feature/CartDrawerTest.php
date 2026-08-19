<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CartDrawerTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_cart_payload_contains_session_cart_lines(): void
    {
        $product = $this->makeProduct();
        app(Cart::class)->add($product->id);

        $this->get(route('shop.cart'))
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('shop/cart')
                ->where('global_cart.count', 1)
                ->has('global_cart.items', 1)
                ->where('global_cart.items.0.id', $product->id.':default')
                ->where('global_cart.items.0.name', $product->name)
                ->where('global_cart.items.0.href', '/'.$product->slug)
                ->where('global_cart.items.0.quantity', 1)
                ->where('global_cart.subtotal', fn (string $subtotal): bool => str_starts_with($subtotal, '$'))
            );
    }

    private function makeProduct(): Product
    {
        $category = ProductCategory::create([
            'name' => 'Cart drawer products',
            'slug' => 'cart-drawer-'.uniqid(),
        ]);

        return Product::create([
            'name' => 'Cart drawer test product',
            'slug' => 'cart-drawer-product-'.uniqid(),
            'product_category_id' => $category->id,
        ]);
    }
}
