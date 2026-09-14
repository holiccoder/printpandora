<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CottonBusinessCardNfcRemovalMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private const COTTON_PRODUCT_SLUGS = [
        'basic-cotton-business-card',
        'classic-cotton-business-card',
        'premium-cotton-business-card',
        'luxe-cotton-business-card',
        'grand-cotton-business-card',
    ];

    public function test_migration_removes_nfc_options_and_pricing_from_all_cotton_cards(): void
    {
        $category = ProductCategory::create([
            'name' => 'Cotton Business Cards',
            'slug' => 'cotton-business-cards',
        ]);

        foreach (self::COTTON_PRODUCT_SLUGS as $slug) {
            Product::create([
                'name' => str_replace('-', ' ', $slug),
                'slug' => $slug,
                'product_category_id' => $category->id,
                'product_options' => [
                    'corners' => [['code' => 'square']],
                    'with_nfc' => [['code' => 'no_nfc']],
                ],
                'product_config' => [
                    'options' => [
                        'corners' => [
                            'values' => [['code' => 'square']],
                        ],
                        'with_nfc' => [
                            'default' => 'no_nfc',
                            'values' => [
                                ['code' => 'no_nfc'],
                                ['code' => 'with_nfc'],
                            ],
                        ],
                    ],
                    'pricing' => [
                        'scenarios' => [
                            'rectangle' => [
                                'processes' => [
                                    ['code' => 'rounded_corners'],
                                    ['code' => 'nfc'],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        }

        $migration = require base_path(
            'database/migrations/2026_09_14_000005_remove_cotton_business_card_nfc_options.php',
        );
        $migration->up();
        $migration->up();

        foreach (self::COTTON_PRODUCT_SLUGS as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();

            $this->assertArrayNotHasKey('with_nfc', $product->product_config['options']);
            $this->assertSame(
                ['rounded_corners'],
                array_column(data_get($product->product_config, 'pricing.scenarios.rectangle.processes'), 'code'),
            );
            $this->assertArrayNotHasKey('with_nfc', $product->product_options);
            $this->assertSame(
                ['square'],
                data_get($product->product_options, 'corners.*.code'),
            );
        }
    }
}
