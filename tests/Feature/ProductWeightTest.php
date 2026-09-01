<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductWeightTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_products_default_to_zero_weight(): void
    {
        $category = ProductCategory::create([
            'name' => 'Weight test category',
            'slug' => 'weight-test-category',
        ]);

        $product = Product::create([
            'name' => 'Zero weight product',
            'slug' => 'zero-weight-product',
            'product_category_id' => $category->getKey(),
            'is_active' => true,
        ]);

        $this->assertSame(0, $product->fresh()->weight);
    }

    public function test_product_weight_can_be_saved_as_an_integer(): void
    {
        $category = ProductCategory::create([
            'name' => 'Weight persistence category',
            'slug' => 'weight-persistence-category',
        ]);

        $product = Product::create([
            'name' => 'Weighted product',
            'slug' => 'weighted-product',
            'product_category_id' => $category->getKey(),
            'is_active' => true,
            'weight' => 250,
        ]);

        $this->assertSame(250, $product->fresh()->weight);
    }
}
