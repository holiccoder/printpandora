<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\OrderWeightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWeightServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_square_and_custom_card_weights_use_the_product_gsm(): void
    {
        $product = new Product(['weight' => 300]);
        $weights = app(OrderWeightService::class);

        $this->assertEqualsWithDelta(
            0.09 * 0.054 * 300 * 2,
            $weights->forLine($product, [], 2),
            0.00001,
        );
        $this->assertEqualsWithDelta(
            0.06 * 0.06 * 300 * 2,
            $weights->forLine($product, ['sizes' => 'square'], 2),
            0.00001,
        );
        $this->assertEqualsWithDelta(
            (2.1 * 0.0254) * (3.5 * 0.0254) * 300 * 2,
            $weights->forLine($product, [
                'sizes' => 'custom',
                'custom_width' => '2.1',
                'custom_height' => '3.5',
            ], 2),
            0.00001,
        );
    }

    public function test_missing_custom_dimensions_fall_back_to_the_default_card_size(): void
    {
        $product = new Product(['weight' => 300]);
        $weights = app(OrderWeightService::class);

        $this->assertEqualsWithDelta(
            0.09 * 0.054 * 300,
            $weights->forLine($product, ['sizes' => 'custom'], 1),
            0.00001,
        );
    }

    public function test_cart_weight_adds_the_fixed_package_weight_once(): void
    {
        $category = ProductCategory::create([
            'name' => 'Weight test category',
            'slug' => 'weight-test-category-'.uniqid(),
        ]);
        $product = Product::create([
            'name' => 'Weighted card',
            'slug' => 'weighted-card-'.uniqid(),
            'product_category_id' => $category->getKey(),
            'weight' => 100,
        ]);

        $weight = app(OrderWeightService::class)->forCart([
            'card-line' => [
                'product_id' => $product->getKey(),
                'quantity' => 1,
                'options' => ['sizes' => 'square', 'quantity' => '2'],
            ],
        ]);

        $this->assertEqualsWithDelta(
            250 + (0.06 * 0.06 * 100 * 2),
            $weight,
            0.00001,
        );
        $this->assertSame(251, app(OrderWeightService::class)->wholeGrams($weight));
    }
}
