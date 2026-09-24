<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCardQuantityRecommendationMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_quantity_is_separate_from_the_recommended_quantity(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        $product = Product::create([
            'name' => 'Classic Standard Business Cards',
            'slug' => 'classic-standard-business-cards',
            'product_category_id' => $category->id,
            'product_config' => [
                'pricing' => [
                    'mode' => 'rule_based',
                    'rules' => [[
                        'id' => 'default',
                        'match' => [],
                        'pricing' => [
                            'startQuantity' => 200,
                            'paperRates' => [
                                '50' => 0,
                                '100' => 0,
                                '200' => 10,
                            ],
                            'processes' => [],
                        ],
                    ]],
                    'quantity_price_table' => [
                        ['quantity' => '50', 'is_recommended' => true],
                        ['quantity' => '200', 'is_recommended' => false],
                    ],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_24_000013_separate_business_card_quantity_recommendations.php',
        );
        $migration->up();
        $product->refresh();

        $this->assertSame(
            50,
            data_get($product->product_config, 'pricing.rules.0.pricing.startQuantity'),
        );
        $this->assertSame(
            200,
            data_get($product->product_config, 'pricing.rules.0.pricing.recommendedQuantity'),
        );
        $this->assertFalse(
            data_get($product->product_config, 'pricing.quantity_price_table.0.is_recommended'),
        );
        $this->assertTrue(
            data_get($product->product_config, 'pricing.quantity_price_table.1.is_recommended'),
        );
    }
}
