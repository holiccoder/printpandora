<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\PricingService;
use App\Services\ProductConfigurationService;
use App\Support\PostcardProductCatalog;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class PostcardProductPricingTest extends TestCase
{
    public function test_eight_sizes_match_the_showcase_and_custom_printing_bounds(): void
    {
        $sizes = PostcardProductCatalog::sizeOptions();

        $this->assertSame('standard', $sizes['default']);
        $this->assertSame([
            ['standard', '4', '6'],
            ['square', '4.72', '4.72'],
            ['rack', '3.67', '8.5'],
            ['half_page', '5.5', '8.5'],
            ['small', '4.13', '5.83'],
            ['medium', '5', '7'],
            ['large', '6', '9'],
            ['custom', null, null],
        ], array_map(fn (array $value): array => [$value['code'], $value['width'] ?? null, $value['height'] ?? null], $sizes['values']));

        foreach ($sizes['values'] as $size) {
            $this->assertFileExists(public_path($size['swatch_image']));
        }

        $custom = $sizes['values'][7];
        $this->assertSame(['mm', 18, 430, 18, 301], [
            $custom['unit'], $custom['min_width'], $custom['max_width'], $custom['min_height'], $custom['max_height'],
        ]);
    }

    public function test_all_sizes_and_quantity_tiers_match_sheet1(): void
    {
        $workbook = $this->workbook();
        $pricing = app(PricingService::class);

        foreach ($workbook['papers'] as $slug => $paper) {
            $product = $this->product($slug);
            $storefront = app(ProductConfigurationService::class)->storefrontOptions($product);
            $this->assertCount(8, $storefront['sizes']);
            $this->assertSame($paper['quantity_multipliers'], $storefront['pricing_rules'][0]['pricing']['unitMultipliers']);
            $this->assertSame($paper['area_unit_prices'], $storefront['pricing_rules'][0]['pricing']['area_unit_prices']);

            foreach (array_slice(PostcardProductCatalog::sizeOptions()['values'], 0, 7) as $size) {
                $area = round((float) $size['width'] * (float) $size['height'] * 0.0254 ** 2, 8);

                foreach ($paper['quantity_multipliers'] as $quantity => $multiplier) {
                    $options = $pricing->validateOptions($product, [
                        'sizes' => $size['code'], 'corners' => 'square', 'quantity' => (string) $quantity,
                        'paper_area' => '999',
                    ]);
                    $this->assertSame($area, (float) $options['paper_area']);
                    $expected = (float) round($area * $this->unitPrice($paper['area_unit_prices'], $area) * $multiplier * $quantity);
                    $this->assertSame($expected, $this->price($product, $options), $slug.' / '.$size['code'].' / '.$quantity);
                }
            }
        }
    }

    public function test_finishes_match_sheet2_and_charge_each_selected_side_and_color(): void
    {
        $workbook = $this->workbook();
        $product = $this->product('classic-standard-postcards');
        $pricing = app(PricingService::class);
        $cases = [
            [['corners' => 'rounded'], ['uv' => 1]],
            [['uv_finish' => 'single_side_uv'], ['uv' => 1]],
            [['uv_finish' => 'both_sides_uv'], ['uv' => 2]],
            [['hot_foil' => ['bright_gold']], ['foil' => 1]],
            [['hot_foil' => ['bright_gold'], 'hot_foil_sides' => ['bright_gold' => 'both_sides']], ['foil' => 2]],
            [['special_finish' => ['cold_bright_gold', 'cold_bright_silver']], ['foil' => 2]],
            [['special_finish' => ['3d_uv']], ['foil' => 1]],
            [['special_finish' => ['custom_die_cut']], ['die_cut' => 1]],
            [['corners' => 'rounded', 'uv_finish' => 'both_sides_uv', 'hot_foil' => ['bright_gold']], ['uv' => 3, 'foil' => 1]],
        ];

        foreach (array_slice(PostcardProductCatalog::sizeOptions()['values'], 0, 7) as $size) {
            foreach ($workbook['papers']['classic-standard-postcards']['quantity_multipliers'] as $quantity => $multiplier) {
                foreach ($cases as [$selection, $processes]) {
                    $options = $pricing->validateOptions($product, array_replace([
                        'sizes' => $size['code'], 'corners' => 'square', 'quantity' => (string) $quantity,
                    ], $selection));
                    $area = (float) $options['paper_area'];
                    $unit = $this->unitPrice($workbook['papers']['classic-standard-postcards']['area_unit_prices'], $area) * $multiplier;

                    foreach ($processes as $code => $count) {
                        $finish = $workbook['finishes'][$code];
                        $unit += $this->unitPrice($finish['area_unit_prices'], $area) * $finish['quantity_multipliers'][$quantity] * $count;
                    }

                    $this->assertSame((float) round($quantity * $area * $unit), $this->price($product, $options), json_encode($selection));
                }
            }
        }
    }

    public function test_custom_dimensions_recalculate_area_and_respect_workbook_limits(): void
    {
        $product = $this->product('super-standard-postcards');
        $pricing = app(PricingService::class);
        $paper = $this->workbook()['papers']['super-standard-postcards'];

        foreach ([[18, 18], [100, 155], [100, 155.01], [200, 200], [430, 301]] as [$width, $height]) {
            $options = $pricing->validateOptions($product, [
                'sizes' => 'custom', 'custom_width' => $width, 'custom_height' => $height,
                'quantity' => '200', 'paper_area' => '999',
            ]);
            $area = round($width * $height / 1000000, 8);
            $this->assertSame($area, (float) $options['paper_area']);
            $this->assertSame((float) round(200 * $area * $this->unitPrice($paper['area_unit_prices'], $area) * 6), $this->price($product, $options));
        }

        foreach ([[17.99, 18], [430.01, 18], [18, 17.99], [18, 301.01], ['', 100]] as [$width, $height]) {
            try {
                $pricing->validateOptions($product, ['sizes' => 'custom', 'custom_width' => $width, 'custom_height' => $height]);
                $this->fail('Invalid custom dimensions were accepted.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
    }

    public function test_synchronization_preserves_other_options_copy_and_media(): void
    {
        $config = ['product' => ['name' => 'Keep copy'], 'options' => ['texture' => ['values' => [['code' => 'keep']]]], 'media' => ['gallery' => ['/keep.webp']]];
        $updated = PostcardProductCatalog::synchronizeConfig($config, 'classic-standard-postcards');

        $this->assertSame($config['product'], $updated['product']);
        $this->assertSame($config['media'], $updated['media']);
        $this->assertSame($config['options']['texture'], $updated['options']['texture']);
        $this->assertSame($updated, PostcardProductCatalog::synchronizeConfig($updated, 'classic-standard-postcards'));
        $this->assertSame($config, PostcardProductCatalog::synchronizeConfig($config, 'classic-standard-business-cards'));
    }

    private function product(string $slug): Product
    {
        $product = new Product([
            'slug' => $slug,
            'name' => 'Postcards',
            'product_config' => PostcardProductCatalog::synchronizeConfig([
                'schema_version' => 1,
                'options' => [],
                'media' => [],
            ], $slug),
        ]);
        $product->setRelation('category', new ProductCategory(['slug' => 'cards-and-postcards']));

        return $product;
    }

    private function price(Product $product, array $options): ?float
    {
        $pricing = app(PricingService::class);

        return (new ReflectionMethod($pricing, 'calculateDynamicPrice'))->invoke($pricing, $product, $options);
    }

    private function workbook(): array
    {
        return json_decode(file_get_contents(base_path('tests/Fixtures/postcard-workbook.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    private function unitPrice(array $prices, float $area): float
    {
        foreach ($prices as $upperArea => $price) {
            if ($area <= (float) $upperArea) {
                return (float) $price;
            }
        }

        return (float) end($prices);
    }
}
