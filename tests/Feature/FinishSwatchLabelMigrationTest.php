<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinishSwatchLabelMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_normalizes_canonical_and_legacy_finish_labels_idempotently(): void
    {
        $category = ProductCategory::create([
            'name' => 'Finish Label Test Category',
            'slug' => 'finish-label-test-category',
        ]);

        $product = Product::create([
            'name' => 'Finish Label Test Product',
            'slug' => 'finish-label-test-product',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'paper_finish' => [
                        'values' => [
                            ['code' => 'matte_lamination', 'label' => 'Matte Lamination'],
                            ['code' => 'gloss_lamination', 'label' => 'Gloss Lamination'],
                            ['code' => 'soft_touch_lamination', 'label' => 'Soft-Touch Lamination'],
                        ],
                    ],
                ],
            ],
            'product_options' => [
                'texture' => [
                    ['code' => 'matte', 'name' => 'Matte Lamination'],
                    ['code' => 'gloss', 'name' => 'Gloss Lamination'],
                    ['code' => 'soft_touch_film', 'name' => 'Soft-Touch Film'],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_29_000014_normalize_finish_swatch_labels.php',
        );

        $migration->up();
        $product->refresh();
        $afterFirstRun = [
            $product->product_config,
            $product->product_options,
        ];

        $migration->up();
        $product->refresh();

        $this->assertSame($afterFirstRun, [
            $product->product_config,
            $product->product_options,
        ]);
        $this->assertSame(
            ['Matte', 'Gloss', 'Soft-Touch'],
            data_get($product->product_config, 'options.paper_finish.values.*.label'),
        );
        $this->assertSame(
            ['Matte', 'Gloss', 'Soft-Touch'],
            data_get($product->product_options, 'texture.*.name'),
        );
    }
}
