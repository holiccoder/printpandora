<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\PricingService;
use App\Services\ProductConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicCottonBusinessCardPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_selected_cotton_processes_reproduce_the_uploaded_rows(): void
    {
        $product = $this->makeProduct();
        $pricing = app(PricingService::class);
        $options = $this->cottonOptions('rounded', [
            'laser',
            'edge_coloring',
            'double_mounting',
            'custom_die_cut',
        ]);

        foreach ([
            200 => 327.0,
            500 => 715.0,
            1000 => 1168.0,
            2000 => 2272.0,
            3000 => 3306.0,
            4000 => 4284.0,
            5000 => 5200.0,
            10000 => 10400.0,
        ] as $quantity => $expected) {
            $this->assertSame(
                $expected,
                $pricing->calculate($product->id, $options + ['quantity' => $quantity]),
                "Unexpected total for {$quantity} cards.",
            );
        }
    }

    public function test_each_cotton_special_finish_is_priced_independently(): void
    {
        $product = $this->makeProduct();
        $pricing = app(PricingService::class);

        foreach (['laser', 'edge_coloring', 'double_mounting', 'custom_die_cut'] as $finish) {
            $this->assertSame(
                170.0,
                $pricing->calculate(
                    $product->id,
                    $this->cottonOptions('square', [$finish]) + ['quantity' => 200],
                ),
                "Unexpected total for {$finish}.",
            );
        }

        $this->assertSame(
            177.0,
            $pricing->calculate(
                $product->id,
                $this->cottonOptions('rounded', ['laser']) + ['quantity' => 200],
            ),
        );
    }

    public function test_all_cotton_sizes_use_the_same_rule_price(): void
    {
        $product = $this->makeProduct();
        $pricing = app(PricingService::class);
        $prices = [];

        foreach (['standard', 'compact', 'custom'] as $size) {
            $prices[$size] = $pricing->calculate(
                $product->id,
                $this->cottonOptions('square', ['laser']) + [
                    'sizes' => $size,
                    'quantity' => 200,
                ],
            );
        }

        $this->assertSame(['standard' => 170.0, 'compact' => 170.0, 'custom' => 170.0], $prices);
    }

    public function test_name_only_processes_are_normalized_for_the_storefront(): void
    {
        $payload = $this->pricingPayload();

        $payload['processes'] = array_map(static function (array $process): array {
            unset($process['code']);

            return $process;
        }, $payload['processes']);

        $product = $this->makeProduct($payload);
        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertSame(
            [
                'rounded_corners',
                'laser',
                'edge_coloring',
                'double_mounting',
                'custom_die_cut',
            ],
            array_column(data_get($options, 'pricing_rules.0.pricing.processes'), 'code'),
        );
    }

    public function test_pricing_migration_only_replaces_the_target_product_pricing(): void
    {
        $category = ProductCategory::create([
            'name' => 'Cotton Business Cards',
            'slug' => 'cotton-business-cards',
        ]);
        $product = Product::create([
            'name' => 'Basic Cotton Business Card',
            'slug' => 'basic-cotton-business-card',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => ['keep' => true],
                'media' => ['gallery' => ['/images/keep.webp']],
                'pricing' => [
                    'mode' => 'fixed_tiers',
                    'scenarios' => ['old' => ['basePrice' => 1]],
                    'quantity_price_table' => [['quantity' => 50]],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_15_000001_add_basic_cotton_business_card_pricing.php',
        );
        $migration->up();
        $product->refresh();

        $this->assertTrue(data_get($product->product_config, 'options.keep'));
        $this->assertSame(
            ['/images/keep.webp'],
            data_get($product->product_config, 'media.gallery'),
        );
        $this->assertSame('rule_based', data_get($product->product_config, 'pricing.mode'));
        $this->assertSame([], data_get($product->product_config, 'pricing.scenarios'));
        $this->assertSame(
            'basic-cotton-business-card-default',
            data_get($product->product_config, 'pricing.rules.0.id'),
        );
    }

    /**
     * @param  array<int, string>  $specialFinish
     * @return array<string, mixed>
     */
    private function cottonOptions(string $corners, array $specialFinish): array
    {
        return [
            'sizes' => 'standard',
            'corners' => $corners,
            'texture' => 'wild_450gsm',
            'special_finish' => $specialFinish,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $pricingPayload
     */
    private function makeProduct(?array $pricingPayload = null): Product
    {
        $category = ProductCategory::create([
            'name' => 'Cotton Business Cards',
            'slug' => 'cotton-business-cards',
        ]);

        return Product::create([
            'name' => 'Basic Cotton Business Card',
            'slug' => 'basic-cotton-business-card',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'sizes' => [
                        'values' => [
                            ['code' => 'standard', 'label' => 'Standard'],
                            ['code' => 'compact', 'label' => 'Compact'],
                            ['code' => 'custom', 'label' => 'Custom'],
                        ],
                    ],
                    'corners' => [
                        'values' => [
                            ['code' => 'square', 'label' => 'Square'],
                            ['code' => 'rounded', 'label' => 'Rounded'],
                        ],
                    ],
                    'texture' => [
                        'values' => [
                            ['code' => 'wild_450gsm', 'label' => 'Wild 450gsm'],
                        ],
                    ],
                    'special_finish' => [
                        'type' => 'multi_select',
                        'values' => [
                            ['code' => 'laser', 'label' => 'Laser'],
                            ['code' => 'edge_coloring', 'label' => 'Edge Coloring'],
                            ['code' => 'double_mounting', 'label' => 'Double Mounting'],
                            ['code' => 'custom_die_cut', 'label' => 'Custom Die-Cut'],
                        ],
                    ],
                ],
                'pricing' => [
                    'mode' => 'rule_based',
                    'currency' => 'USD',
                    'total_rounding' => 'nearest_integer',
                    'rules' => [[
                        'id' => 'basic-cotton-business-card-default',
                        'match' => [],
                        'pricing' => $pricingPayload ?? $this->pricingPayload(),
                    ]],
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function pricingPayload(): array
    {
        $specialRates = [
            '100' => 50,
            '200' => 75,
            '500' => 80,
            '1000' => 82,
            '2000' => 82.5,
            '3000' => 83,
            '4000' => 83.5,
            '5000' => 84,
            '10000' => 84,
        ];

        return [
            'packageName' => '棉纸-基础型',
            'basePrice' => 1.9,
            'startQuantity' => 200,
            'paperRates' => [
                '100' => 47.37,
                '200' => 68.42,
                '500' => 68.42,
                '1000' => 77.89,
                '2000' => 78.42,
                '3000' => 78.95,
                '4000' => 79.47,
                '5000' => 80,
                '10000' => 80,
            ],
            'processes' => [
                [
                    'name' => '圆角',
                    'code' => 'rounded_corners',
                    'markup' => 0.14,
                    'rates' => [
                        '100' => 50,
                        '200' => 75,
                        '500' => 78.57,
                        '1000' => 80,
                        '2000' => 81.43,
                        '3000' => 84.29,
                        '4000' => 85,
                        '5000' => 85.74,
                        '10000' => 85.74,
                    ],
                ],
                ...array_map(
                    static fn (string $name, string $code): array => [
                        'name' => $name,
                        'code' => $code,
                        'markup' => 1,
                        'rates' => $specialRates,
                    ],
                    ['激光', '滚边', '对裱', '异形模切'],
                    ['laser', 'edge_coloring', 'double_mounting', 'custom_die_cut'],
                ),
            ],
        ];
    }
}
