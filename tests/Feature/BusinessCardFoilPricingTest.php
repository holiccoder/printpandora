<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCardFoilPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_sides_doubles_the_foil_markup_server_side(): void
    {
        $product = $this->makeProduct();
        $pricing = app(PricingService::class);
        $baseOptions = [
            'sizes' => 'standard',
            'paper_finish' => 'matte',
            'corners' => 'square',
            'special_finish' => ['gold_foil'],
            'quantity' => '100',
        ];

        $singleSide = $pricing->calculate($product->id, $baseOptions + [
            'special_finish_on_sides' => ['gold_foil' => 'one_side'],
        ]);
        $bothSides = $pricing->calculate($product->id, $baseOptions + [
            'special_finish_on_sides' => ['gold_foil' => 'both_sides'],
        ]);

        $this->assertSame(30.0, $singleSide);
        $this->assertSame(50.0, $bothSides);
    }

    public function test_hot_and_cold_foil_side_prices_are_independent_in_rules(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        $product = Product::create([
            'name' => 'Independent Foil Test Business Card',
            'slug' => 'independent-foil-test-business-card',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'special_finish' => [
                        'type' => 'multi_select',
                        'values' => [
                            ['code' => 'hot_foil'],
                            ['code' => 'cold_foil'],
                        ],
                    ],
                ],
                'pricing' => [
                    'mode' => 'rule_based',
                    'rules' => [[
                        'id' => 'foil',
                        'match' => [],
                        'pricing' => [
                            'basePrice' => 0.1,
                            'startQuantity' => 100,
                            'paperRates' => ['100' => 0],
                            'processes' => [
                                [
                                    'code' => 'hot_foil',
                                    'name' => 'Hot Foil',
                                    'markup' => 0.2,
                                    'rates' => [],
                                ],
                                [
                                    'code' => 'cold_foil',
                                    'name' => 'Cold Foil',
                                    'markup' => 0.3,
                                    'rates' => [],
                                ],
                            ],
                        ],
                    ]],
                ],
            ],
        ]);

        $pricing = app(PricingService::class);

        $this->assertSame(
            30.0,
            $pricing->calculate($product->id, [
                'special_finish' => ['hot_foil'],
                'special_finish_on_sides' => ['hot_foil' => 'one_side'],
                'quantity' => '100',
            ]),
        );
        $this->assertSame(
            50.0,
            $pricing->calculate($product->id, [
                'special_finish' => ['hot_foil'],
                'special_finish_on_sides' => ['hot_foil' => 'both_sides'],
                'quantity' => '100',
            ]),
        );
        $this->assertSame(
            40.0,
            $pricing->calculate($product->id, [
                'special_finish' => ['cold_foil'],
                'special_finish_on_sides' => ['cold_foil' => 'one_side'],
                'quantity' => '100',
            ]),
        );
        $this->assertSame(
            70.0,
            $pricing->calculate($product->id, [
                'special_finish' => ['cold_foil'],
                'special_finish_on_sides' => ['cold_foil' => 'both_sides'],
                'quantity' => '100',
            ]),
        );
        $this->assertSame(
            80.0,
            $pricing->calculate($product->id, [
                'special_finish' => ['hot_foil', 'cold_foil'],
                'special_finish_on_sides' => [
                    'hot_foil' => 'both_sides',
                    'cold_foil' => 'one_side',
                ],
                'quantity' => '100',
            ]),
        );
    }

    public function test_multiple_selected_hot_and_cold_foils_scale_the_markup_by_selection_count(): void
    {
        $product = $this->makeProduct();
        $pricing = app(PricingService::class);
        $baseOptions = [
            'sizes' => 'standard',
            'paper_finish' => 'matte',
            'corners' => 'square',
            'quantity' => '100',
        ];

        $this->assertSame(
            50.0,
            $pricing->calculate($product->id, $baseOptions + [
                'special_finish' => ['gold_foil', 'silver_foil'],
                'special_finish_on_sides' => [
                    'gold_foil' => 'one_side',
                    'silver_foil' => 'one_side',
                ],
            ]),
        );
        $this->assertSame(
            70.0,
            $pricing->calculate($product->id, $baseOptions + [
                'special_finish' => ['gold_foil', 'silver_foil', 'cold_red_gold'],
                'special_finish_on_sides' => [
                    'gold_foil' => 'one_side',
                    'silver_foil' => 'one_side',
                    'cold_red_gold' => 'one_side',
                ],
            ]),
        );
        $this->assertSame(
            70.0,
            $pricing->calculate($product->id, $baseOptions + [
                'special_finish' => ['gold_foil', 'silver_foil'],
                'special_finish_on_sides' => [
                    'gold_foil' => 'both_sides',
                    'silver_foil' => 'one_side',
                ],
            ]),
        );
    }

    private function makeProduct(): Product
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        return Product::create([
            'name' => 'Foil Test Business Card',
            'slug' => 'foil-test-business-card',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'sizes' => [
                        'values' => [['code' => 'standard']],
                    ],
                    'paper_finish' => [
                        'values' => [['code' => 'matte']],
                    ],
                    'corners' => [
                        'values' => [['code' => 'square']],
                    ],
                    'special_finish' => [
                        'type' => 'multi_select',
                        'values' => [
                            ['code' => 'no_special_finish'],
                            ['code' => 'gold_foil'],
                            ['code' => 'silver_foil'],
                            ['code' => 'cold_red_gold'],
                        ],
                    ],
                ],
                'pricing' => [
                    'mode' => 'rule_based',
                    'scenarios' => [
                        'rectangle' => [
                            'base_price_per_card' => 0.1,
                            'start_quantity' => 100,
                            'quantity_discounts_percent' => ['100' => 0],
                            'processes' => [[
                                'code' => 'foil',
                                'label' => 'Hot Foil',
                                'markup_per_card' => 0.2,
                                'quantity_discounts_percent' => [],
                            ]],
                        ],
                    ],
                    'rules' => [],
                ],
            ],
        ]);
    }
}
