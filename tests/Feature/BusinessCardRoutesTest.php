<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BusinessCardRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_business_card_path_loads_the_existing_product(): void
    {
        $category = ProductCategory::create([
            'name' => 'Cotton Business Cards',
            'slug' => 'cotton-business-cards',
        ]);

        Product::create([
            'name' => 'Basic cotton business card',
            'slug' => 'basic-cotton-business-card',
            'description' => 'Cotton card',
            'price' => 0,
            'product_category_id' => $category->id,
            'is_active' => true,
            'product_config' => [],
        ]);

        $this->get('/business-cards/basic-cotton')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('shop/show')
                ->where('product.slug', 'basic-cotton-business-card'));
    }

    public function test_legacy_business_card_path_redirects_to_the_canonical_path(): void
    {
        $category = ProductCategory::create([
            'name' => 'Cotton Business Cards',
            'slug' => 'cotton-business-cards',
        ]);

        Product::create([
            'name' => 'Basic cotton business card',
            'slug' => 'basic-cotton-business-card',
            'description' => 'Cotton card',
            'price' => 0,
            'product_category_id' => $category->id,
            'is_active' => true,
        ]);

        $this->get('/basic-cotton-business-card')
            ->assertStatus(301)
            ->assertRedirect('/business-cards/basic-cotton');
    }
}
