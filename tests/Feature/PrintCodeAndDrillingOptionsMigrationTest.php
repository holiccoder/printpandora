<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintCodeAndDrillingOptionsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_removes_legacy_no_values_and_makes_each_group_optional(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        Product::create([
            'name' => 'Legacy Option Product',
            'slug' => 'legacy-option-product',
            'product_category_id' => $category->id,
            'product_options' => [
                'print_code' => [
                    ['code' => 'no_print_code'],
                    ['code' => 'need_print_code'],
                ],
                'drill' => [
                    ['code' => 'no_drilling'],
                    ['code' => 'needs_drilling'],
                ],
                'print_code_or_signature_stripe' => [
                    ['code' => 'no_print_code_or_signature_stripe'],
                    ['code' => 'print_code'],
                    ['code' => 'signature_stripe'],
                ],
                'print_code_or_magnetic_stripe' => [
                    ['code' => 'no_print_code_or_magnetic_stripe'],
                    ['code' => 'print_code'],
                    ['code' => 'magnetic_stripe'],
                ],
                'unrelated' => [
                    ['code' => 'keep_me'],
                ],
                'option_groups' => [
                    [
                        'key' => 'print_code',
                        'required' => true,
                        'default' => 'no_print_code',
                        'values' => [
                            ['code' => 'no_print_code'],
                            ['code' => 'need_print_code'],
                        ],
                    ],
                ],
            ],
            'product_config' => [
                'options' => [
                    'print_code' => [
                        'required' => true,
                        'default' => 'no_print_code',
                        'values' => [
                            ['code' => 'no_print_code'],
                            ['code' => 'print_code'],
                        ],
                    ],
                    'drill' => [
                        'required' => true,
                        'default' => 'no_drilling',
                        'values' => [
                            ['code' => 'no_drilling'],
                            ['code' => 'needs_drilling'],
                        ],
                    ],
                    'print_code_or_signature_stripe' => [
                        'required' => true,
                        'default' => 'no_print_code_or_signature_stripe',
                        'values' => [
                            ['code' => 'no_print_code_or_signature_stripe'],
                            ['code' => 'signature_stripe'],
                        ],
                    ],
                    'print_code_or_magnetic_stripe' => [
                        'required' => true,
                        'default' => 'no_print_code_or_magnetic_stripe',
                        'values' => [
                            ['code' => 'no_print_code_or_magnetic_stripe'],
                            ['code' => 'magnetic_stripe'],
                        ],
                    ],
                    'unrelated' => [
                        'required' => true,
                        'default' => 'keep_me',
                        'values' => [['code' => 'keep_me']],
                    ],
                    'option_groups' => [
                        [
                            'key' => 'drilling',
                            'required' => true,
                            'default' => 'no_drilling',
                            'values' => [
                                ['code' => 'no_drilling'],
                                ['code' => 'needs_drilling'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_24_000009_remove_no_print_code_and_no_drilling_options.php',
        );
        $migration->up();
        $migration->up();

        $product = Product::where('slug', 'legacy-option-product')->firstOrFail();

        $this->assertSame(
            ['print_code'],
            data_get($product->product_config, 'options.print_code.values.*.code'),
        );
        $this->assertSame(
            ['needs_drilling'],
            data_get($product->product_config, 'options.drill.values.*.code'),
        );
        $this->assertSame(
            ['signature_stripe'],
            data_get($product->product_config, 'options.print_code_or_signature_stripe.values.*.code'),
        );
        $this->assertSame(
            ['magnetic_stripe'],
            data_get($product->product_config, 'options.print_code_or_magnetic_stripe.values.*.code'),
        );

        foreach ([
            'print_code',
            'drill',
            'print_code_or_signature_stripe',
            'print_code_or_magnetic_stripe',
        ] as $groupKey) {
            $this->assertFalse(data_get($product->product_config, "options.{$groupKey}.required"));
            $this->assertNull(data_get($product->product_config, "options.{$groupKey}.default"));
        }

        $this->assertSame(
            ['keep_me'],
            data_get($product->product_config, 'options.unrelated.values.*.code'),
        );
        $this->assertSame(
            ['keep_me'],
            data_get($product->product_options, 'unrelated.*.code'),
        );
        $this->assertSame(
            ['need_print_code'],
            data_get($product->product_options, 'print_code.*.code'),
        );
        $this->assertSame(
            ['needs_drilling'],
            data_get($product->product_options, 'drill.*.code'),
        );
        $this->assertSame(
            ['need_print_code'],
            data_get($product->product_options, 'option_groups.0.values.*.code'),
        );
        $this->assertFalse(data_get($product->product_config, 'options.option_groups.0.required'));
        $this->assertNull(data_get($product->product_config, 'options.option_groups.0.default'));
    }
}
