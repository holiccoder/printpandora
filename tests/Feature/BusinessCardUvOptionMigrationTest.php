<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCardUvOptionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_splits_uv_from_paper_finish_idempotently(): void
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
                    'paper_finish' => [
                        'label' => 'Paper Finish',
                        'type' => 'select',
                        'required' => true,
                        'default' => 'uv',
                        'values' => [
                            ['code' => 'matte', 'label' => 'Matte'],
                            ['code' => 'gloss', 'label' => 'Gloss'],
                            ['code' => 'uv', 'label' => 'UV'],
                        ],
                    ],
                ],
                'media' => [
                    'gallery_rules' => [
                        [
                            'id' => 'uv-gallery',
                            'match' => ['paper_finish' => 'uv'],
                            'images' => ['/images/uv.png'],
                            'primary' => '/images/uv.png',
                        ],
                        [
                            'id' => 'matte-gallery',
                            'match' => ['paper_finish' => 'matte'],
                            'images' => ['/images/matte.png'],
                            'primary' => '/images/matte.png',
                        ],
                    ],
                ],
                'pricing' => [
                    'rules' => [
                        [
                            'id' => 'pricing-uv',
                            'match' => [
                                'sizes' => 'standard',
                                'paper_finish' => 'uv',
                            ],
                            'pricing' => [
                                'basePrice' => 0.14,
                                'startQuantity' => 200,
                                'paperRates' => ['200' => 0],
                                'processes' => [],
                            ],
                        ],
                    ],
                ],
            ],
            'product_options' => [
                'paper_finish' => [
                    ['name' => 'Matte', 'code' => 'matte'],
                    ['name' => 'Gloss', 'code' => 'gloss'],
                    ['name' => 'UV', 'code' => 'uv'],
                ],
                'galleries' => [[
                    'id' => 'uv-gallery',
                    'match' => ['paper_finish' => 'UV'],
                    'images' => ['/images/uv.png'],
                ]],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_16_000003_split_uv_paper_finish_option.php',
        );
        $migration->up();
        $migration->up();

        $product = Product::where('slug', 'classic-standard-business-cards')->firstOrFail();
        $config = $product->product_config;
        $legacy = $product->product_options;

        $this->assertSame(
            ['matte', 'gloss'],
            data_get($config, 'options.paper_finish.values.*.code'),
        );
        $this->assertFalse(data_get($config, 'options.paper_finish.required'));
        $this->assertSame('matte', data_get($config, 'options.paper_finish.default'));
        $this->assertSame(
            ['paper_finish', 'uv_finish'],
            array_values(array_filter(
                array_keys($config['options']),
                static fn (string $key): bool => in_array($key, ['paper_finish', 'uv_finish'], true),
            )),
        );
        $this->assertSame('matte', data_get($config, 'options.paper_finish.default'));
        $this->assertSame('UV', data_get($config, 'options.uv_finish.label'));
        $this->assertFalse(data_get($config, 'options.uv_finish.required'));
        $this->assertNull(data_get($config, 'options.uv_finish.default'));
        $this->assertSame(
            ['single_side_uv', 'both_sides_uv'],
            data_get($config, 'options.uv_finish.values.*.code'),
        );

        $configRules = data_get($config, 'pricing.rules');
        $this->assertSame(
            [
                ['sizes' => 'standard', 'uv_finish' => 'single_side_uv'],
                ['sizes' => 'standard', 'uv_finish' => 'both_sides_uv'],
            ],
            array_map(
                static fn (array $rule): array => $rule['match'],
                $configRules,
            ),
        );
        $this->assertSame(
            [
                ['uv_finish' => 'single_side_uv'],
                ['uv_finish' => 'both_sides_uv'],
            ],
            array_map(
                static fn (array $rule): array => $rule['match'],
                collect(data_get($config, 'media.gallery_rules'))
                    ->filter(static fn (array $rule): bool => array_key_exists('uv_finish', $rule['match']))
                    ->values()
                    ->all(),
            ),
        );
        $this->assertSame(
            ['matte', 'gloss'],
            data_get($legacy, 'paper_finish.*.code'),
        );
        $this->assertSame(
            ['single_side_uv', 'both_sides_uv'],
            data_get($legacy, 'uv_finish.*.code'),
        );
        $this->assertSame(
            [
                ['uv_finish' => 'single_side_uv'],
                ['uv_finish' => 'both_sides_uv'],
            ],
            array_map(
                static fn (array $rule): array => $rule['match'],
                data_get($legacy, 'galleries'),
            ),
        );
    }
}
