<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassicSpecialMatteTextureMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_removes_matte_from_canonical_and_legacy_texture_groups(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        Product::create([
            'name' => 'Classic Special Business Cards',
            'slug' => 'classic-special-business-cards',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'texture' => [
                        'default' => 'matte',
                        'values' => [
                            ['code' => 'matte', 'label' => 'Matte'],
                            ['code' => 'water_ripple_paper', 'label' => 'Water Ripple Paper'],
                            ['code' => 'linen_paper', 'label' => 'Linen Paper'],
                        ],
                    ],
                ],
            ],
            'product_options' => [
                'texture' => [
                    ['code' => 'matte', 'name' => 'Matte'],
                    ['code' => 'water_ripple_paper', 'name' => 'Water Ripple Paper'],
                    ['code' => 'linen_paper', 'name' => 'Linen Paper'],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_24_000009_remove_classic_special_matte_texture_option.php',
        );
        $migration->up();
        $migration->up();

        $product = Product::where('slug', 'classic-special-business-cards')->firstOrFail();

        $this->assertSame(
            ['water_ripple_paper', 'linen_paper'],
            data_get($product->product_config, 'options.texture.values.*.code'),
        );
        $this->assertSame(
            'water_ripple_paper',
            data_get($product->product_config, 'options.texture.default'),
        );
        $this->assertSame(
            ['water_ripple_paper', 'linen_paper'],
            data_get($product->product_options, 'texture.*.code'),
        );
    }
}
