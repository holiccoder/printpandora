<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderWeightService;
use App\Support\StickerProductCatalog;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StickerWeightCalculationTest extends TestCase
{
    #[DataProvider('stickerSizes')]
    public function test_all_stickers_use_finished_preset_dimensions_at_157_gsm(
        string $slug,
        string $size,
        float $squareInches,
    ): void {
        $product = $this->sticker($slug);

        $weight = app(OrderWeightService::class)->forLine($product, [
            'sizes' => $size,
            'quantity' => '1000',
            'paper_area' => '99',
        ]);

        $this->assertEqualsWithDelta($squareInches * 0.00064516 * 157 * 1000, $weight, 0.000001);
    }

    public static function stickerSizes(): iterable
    {
        foreach (['classic-stickers', 'premium-stickers', 'super-stickers'] as $slug) {
            foreach ([
                '1x2' => 2.0,
                '1x2_8' => 2.8,
                '1_5x5_5' => 8.25,
                '2x3' => 6.0,
                '3x4' => 12.0,
                '4x6' => 24.0,
            ] as $size => $squareInches) {
                yield "{$slug}-{$size}" => [$slug, $size, $squareInches];
            }
        }
    }

    public function test_custom_finished_dimensions_use_157_gsm_for_all_materials(): void
    {
        foreach (StickerProductCatalog::slugs() as $slug) {
            $product = $this->sticker($slug);

            foreach (data_get($product->product_config, 'options.material.values', []) as $material) {
                $weight = app(OrderWeightService::class)->forLine($product, [
                    'sizes' => 'custom',
                    'custom_width' => '2',
                    'custom_height' => '3',
                    'quantity' => '1000',
                    'material' => $material['code'],
                    'paper_area' => '99',
                ]);

                $this->assertEqualsWithDelta(607.74072, $weight, 0.000001, $slug.'-'.$material['code']);
            }
        }
    }

    public function test_configured_sticker_dimensions_and_units_are_respected(): void
    {
        $product = new Product([
            'slug' => 'classic-stickers',
            'weight' => 157,
            'product_config' => ['options' => ['sizes' => [
                'default' => 'configured',
                'values' => [
                    ['code' => 'configured', 'width' => 90, 'height' => 50, 'unit' => 'mm'],
                    ['code' => 'custom', 'unit' => 'mm'],
                ],
            ]]],
        ]);
        $weights = app(OrderWeightService::class);

        $this->assertEqualsWithDelta(70.65, $weights->forLine($product, [], 100), 0.000001);
        $this->assertEqualsWithDelta(70.65, $weights->forLine($product, [
            'sizes' => ['custom'],
            'custom_width' => 90,
            'custom_height' => 50,
        ], 100), 0.000001);
    }

    public function test_invalid_sticker_dimensions_do_not_fall_back_to_business_card_area(): void
    {
        $product = $this->sticker('classic-stickers');
        $weights = app(OrderWeightService::class);

        $this->assertSame(0.0, $weights->forLine($product, ['sizes' => 'unknown'], 100));
        $this->assertSame(0.0, $weights->forLine($product, ['sizes' => 'custom'], 100));
        $this->assertSame(0.0, $weights->forLine($product, [
            'sizes' => 'custom',
            'custom_width' => INF,
            'custom_height' => 2,
        ], 100));
    }

    public function test_sticker_quantity_includes_each_design_once(): void
    {
        $product = $this->sticker('premium-stickers');
        $weights = app(OrderWeightService::class);

        $this->assertEqualsWithDelta(607.74072, $weights->forLine($product, [
            'sizes' => '1x2',
            'quantity' => '1000',
            'design_quantity' => '3',
        ]), 0.000001);
        $this->assertEqualsWithDelta(607.74072, $weights->forLine($product, [
            'sizes' => '1x2',
            'design_quantity' => '3',
        ], 1000), 0.000001);
    }

    public function test_order_weight_counts_designs_and_adds_packaging_once(): void
    {
        config(['shipping.package_weight_grams' => 250]);
        $first = new OrderItem([
            'quantity' => 1,
            'options' => ['sizes' => '2x3', 'quantity' => '100', 'design_quantity' => '2'],
        ]);
        $first->setRelation('product', $this->sticker('classic-stickers'));
        $second = new OrderItem([
            'quantity' => 1,
            'options' => ['sizes' => '2x3', 'quantity' => '500'],
        ]);
        $second->setRelation('product', $this->sticker('super-stickers'));
        $order = new Order;
        $order->setRelation('items', new Collection([$first, $second]));

        $this->assertEqualsWithDelta(675.4185, app(OrderWeightService::class)->forOrder($order), 0.000001);
    }

    public function test_business_card_and_fixed_shipping_weight_calculations_are_preserved(): void
    {
        $weights = app(OrderWeightService::class);
        $card = new Product(['weight' => 300]);

        $this->assertEqualsWithDelta(2.916, $weights->forLine($card, [], 2), 0.000001);
        $this->assertEqualsWithDelta(2.16, $weights->forLine($card, ['sizes' => 'square'], 2), 0.000001);
        $this->assertEqualsWithDelta(2.322576, $weights->forLine($card, [
            'sizes' => 'custom',
            'custom_width' => 2,
            'custom_height' => 3,
        ], 2), 0.000001);
        $this->assertSame(500.0, $weights->forLine(new Product([
            'weight' => 700,
            'shipping_weight_grams' => 500,
        ])));
    }

    public function test_shipping_calculator_fallback_uses_a_default_1_by_2_inch_sticker(): void
    {
        config(['shipping.calculator_product_slugs' => [], 'shipping.package_weight_grams' => 250]);
        $weight = app(OrderWeightService::class)->forCalculator('stickers-labels', 1000);

        $this->assertEqualsWithDelta(452.5802, $weight, 0.000001);
        $this->postJson('/api/shipping/quote', [
            'country' => 'US',
            'product_type' => 'stickers-labels',
            'quantity' => 1000,
        ])->assertOk()->assertJsonPath('shipping_weight_grams', 453);
    }

    public function test_catalog_and_snapshot_default_to_157_gsm_for_all_three_products(): void
    {
        $snapshot = json_decode(file_get_contents(base_path('database/seeders/data/products.json')), true, 512, JSON_THROW_ON_ERROR);
        $stickers = collect($snapshot)->whereIn('slug', StickerProductCatalog::slugs());

        $this->assertCount(3, $stickers);

        foreach ($stickers as $product) {
            $this->assertSame(157, $product['weight']);
            $this->assertSame(157, StickerProductCatalog::definition($product['slug'])['weight']);
        }
    }

    private function sticker(string $slug): Product
    {
        return new Product(StickerProductCatalog::definition($slug));
    }
}
