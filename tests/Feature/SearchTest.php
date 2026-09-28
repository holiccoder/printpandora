<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_search_returns_matching_active_products(): void
    {
        $businessCards = ProductCategory::query()->create([
            'name' => 'Search Business Cards',
            'slug' => 'search-business-cards',
        ]);

        $stickers = ProductCategory::query()->create([
            'name' => 'Search Stickers & Labels',
            'slug' => 'search-stickers-and-labels',
        ]);

        $matchingProduct = Product::query()->create([
            'name' => 'Search Classic Business Cards',
            'slug' => 'search-classic-business-cards',
            'subtitle' => 'A polished everyday card.',
            'description' => 'Printed on premium stock.',
            'product_category_id' => $businessCards->getKey(),
            'is_active' => true,
        ]);

        Product::query()->create([
            'name' => 'Search Classic Stickers',
            'slug' => 'search-classic-stickers',
            'description' => 'Durable labels for packaging.',
            'product_category_id' => $stickers->getKey(),
            'is_active' => true,
        ]);

        Product::query()->create([
            'name' => 'Search Archived Business Cards',
            'slug' => 'search-archived-business-cards',
            'description' => 'No longer available.',
            'product_category_id' => $businessCards->getKey(),
            'is_active' => false,
        ]);

        $this->get('/search?q=business+cards')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('shop/search')
                ->where('query', 'business cards')
                ->has('products.data', 1)
                ->where('products.data.0.id', $matchingProduct->getKey())
                ->where('products.data.0.name', 'Search Classic Business Cards'));
    }
}
