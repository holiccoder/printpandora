<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCardSpecialFinishMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_removes_no_finish_and_makes_foil_optional(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        Product::create([
            'name' => 'Classic Standard Business Cards',
            'slug' => 'classic-standard-business-cards',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'special_finish' => [
                        'type' => 'select',
                        'required' => true,
                        'default' => 'no_special_finish',
                        'values' => [
                            ['code' => 'no_special_finish', 'label' => 'No finish'],
                            ['code' => 'bright_gold', 'label' => 'Bright Gold hot foil'],
                            ['code' => 'cold_blue_gold', 'label' => 'Cold Blue Gold'],
                        ],
                    ],
                ],
                'media' => [
                    'gallery_rules' => [
                        [
                            'id' => 'no-finish',
                            'match' => [
                                'paper_finish' => 'matte',
                                'special_finish' => 'no_special_finish',
                            ],
                        ],
                        [
                            'id' => 'foil',
                            'match' => ['special_finish' => 'bright_gold'],
                        ],
                    ],
                ],
                'galleries' => [[
                    'id' => 'legacy-no-finish',
                    'match' => ['special_finish' => 'no finish'],
                ]],
            ],
            'product_options' => [
                'special_finish' => [
                    ['code' => 'no_special_finish', 'name' => 'No finish'],
                    ['code' => 'bright_gold', 'name' => 'Bright Gold'],
                ],
                'galleries' => [[
                    'id' => 'legacy-no-finish',
                    'match' => ['special_finish' => 'no_special_finish'],
                ]],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_24_000004_remove_no_special_finish_and_make_foil_optional.php',
        );
        $migration->up();

        $product = Product::where('slug', 'classic-standard-business-cards')->firstOrFail();
        $config = $product->product_config;

        $this->assertSame(
            ['bright_gold', 'cold_blue_gold'],
            data_get($config, 'options.special_finish.values.*.code'),
        );
        $this->assertSame('multi_select', data_get($config, 'options.special_finish.type'));
        $this->assertFalse(data_get($config, 'options.special_finish.required'));
        $this->assertSame([], data_get($config, 'options.special_finish.default'));
        $this->assertSame(
            ['paper_finish' => 'matte'],
            data_get($config, 'media.gallery_rules.0.match'),
        );
        $this->assertSame(
            ['special_finish' => 'bright_gold'],
            data_get($config, 'media.gallery_rules.1.match'),
        );
        $this->assertSame(
            ['id' => 'legacy-no-finish', 'match' => []],
            data_get($config, 'galleries.0'),
        );
        $this->assertSame(
            [['code' => 'bright_gold', 'name' => 'Bright Gold']],
            data_get($product->product_options, 'special_finish'),
        );
        $this->assertSame(
            ['id' => 'legacy-no-finish', 'match' => []],
            data_get($product->product_options, 'galleries.0'),
        );

        $firstConfig = $product->product_config;
        $firstLegacy = $product->product_options;

        $migration->up();

        $product->refresh();
        $this->assertSame($firstConfig, $product->product_config);
        $this->assertSame($firstLegacy, $product->product_options);
    }
}
