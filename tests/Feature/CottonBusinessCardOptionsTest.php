<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CottonBusinessCardOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_size_bounds_are_inclusive_and_all_cotton_sizes_keep_the_same_price(): void
    {
        $product = $this->makeProduct();
        $pricing = app(PricingService::class);

        $baseOptions = [
            'corners' => 'square',
            'texture' => 'wild_450gsm',
            'special_finish' => ['edge_coloring', 'laser'],
            'special_finish_on_sides' => [
                'edge_coloring' => 'both_sides',
                'laser' => 'one_side',
            ],
            'quantity' => '50',
        ];

        $standard = $pricing->validateOptions($product, $baseOptions + [
            'sizes' => 'standard',
        ]);
        $compact = $pricing->validateOptions($product, $baseOptions + [
            'sizes' => 'compact',
        ]);
        $custom = $pricing->validateOptions($product, $baseOptions + [
            'sizes' => 'custom',
            'custom_width' => '0.70',
            'custom_height' => '2.13',
        ]);

        $this->assertSame('0.70', $custom['custom_width']);
        $this->assertSame('2.13', $custom['custom_height']);
        $this->assertSame(
            ['edge_coloring', 'laser'],
            $standard['special_finish'],
        );
        $this->assertSame(
            [
                'edge_coloring' => 'both_sides',
                'laser' => 'one_side',
            ],
            $standard['special_finish_on_sides'],
        );
        $this->assertSame(
            $pricing->calculate($product->id, $standard),
            $pricing->calculate($product->id, $compact),
        );
        $this->assertSame(
            $pricing->calculate($product->id, $standard),
            $pricing->calculate($product->id, $custom),
        );
        $this->assertSame(95.0, $pricing->calculate($product->id, $standard));
    }

    public function test_custom_size_bounds_and_required_finish_are_enforced_server_side(): void
    {
        $product = $this->makeProduct();
        $pricing = app(PricingService::class);
        $validOptions = [
            'sizes' => 'custom',
            'corners' => 'square',
            'texture' => 'wild_450gsm',
            'special_finish' => ['laser'],
        ];
        $defaultedSides = $pricing->validateOptions($product, $validOptions + [
            'custom_width' => '0.70',
            'custom_height' => '0.70',
        ]);

        $this->assertSame(
            ['laser' => 'one_side'],
            $defaultedSides['special_finish_on_sides'],
        );

        $invalidSide = $pricing->validateOptions($product, $validOptions + [
            'custom_width' => '0.70',
            'custom_height' => '0.70',
            'special_finish_on_sides' => ['laser' => 'invalid_side'],
        ]);

        $this->assertSame(
            ['laser' => 'one_side'],
            $invalidSide['special_finish_on_sides'],
        );

        foreach ([
            ['custom_width' => '0.69', 'custom_height' => '2.13'],
            ['custom_width' => '3.55', 'custom_height' => '2.13'],
            ['custom_width' => '0.70', 'custom_height' => '0.69'],
            ['custom_width' => '0.70', 'custom_height' => '2.14'],
        ] as $dimensions) {
            try {
                $pricing->validateOptions($product, $validOptions + $dimensions);
                $this->fail('An out-of-range cotton custom size was accepted.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }

        try {
            $pricing->validateOptions($product, [
                'sizes' => 'standard',
                'corners' => 'square',
                'texture' => 'wild_450gsm',
                'special_finish' => [],
            ]);
            $this->fail('An empty cotton special-finish selection was accepted.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }
    }

    private function makeProduct(): Product
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
                'pricing' => [
                    'mode' => 'rule_based',
                    'scenarios' => [
                        'rectangle' => [
                            'base_price_per_card' => 1.9,
                            'start_quantity' => 50,
                            'quantity_discounts_percent' => ['50' => 0],
                            'processes' => [],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
