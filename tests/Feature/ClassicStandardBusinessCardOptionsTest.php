<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\PricingService;
use Database\Seeders\ClassicStandardBusinessCardOptionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClassicStandardBusinessCardOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_imports_custom_size_and_requested_finish_options(): void
    {
        $product = $this->makeProduct([
            'options' => [
                'sizes' => [
                    'values' => [
                        ['code' => 'standard', 'label' => 'Standard'],
                        ['code' => 'square', 'label' => 'Square'],
                    ],
                ],
                'paper_finish' => [
                    'values' => [
                        ['code' => 'matte', 'label' => 'Matte'],
                        ['code' => 'gloss', 'label' => 'Gloss'],
                        ['code' => 'uv', 'label' => 'UV'],
                    ],
                ],
                'corners' => [
                    'values' => [
                        ['code' => 'square', 'label' => 'Square'],
                        ['code' => 'rounded', 'label' => 'Rounded'],
                    ],
                ],
                'special_finish' => [
                    'values' => [
                        ['code' => 'no_special_finish', 'label' => 'Old label'],
                        ['code' => 'bright_gold', 'label' => 'Old foil'],
                    ],
                ],
            ],
            'faq' => [['question' => 'Keep this FAQ', 'answer' => 'Yes']],
        ]);

        (new ClassicStandardBusinessCardOptionsSeeder)->run();

        $product->refresh();

        $this->assertSame(
            ['standard', 'square', 'custom'],
            data_get($product->product_config, 'options.sizes.values.*.code'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/standard-size.webp',
            data_get($product->product_config, 'options.sizes.values.0.swatch_image'),
        );
        $this->assertSame(
            '2.0x3.5  ',
            data_get($product->product_config, 'options.sizes.values.0.description'),
        );
        $this->assertSame(
            '2.5x2.5',
            data_get($product->product_config, 'options.sizes.values.1.description'),
        );
        $this->assertSame(
            [
                'no_special_finish',
                'black_gold',
                'blue_gold',
                'bright_gold',
                'bright_silver',
                'green_gold',
                'matte_gold',
                'matte_silver',
                'red_gold',
                'rose_gold',
                'aged_gold',
                'muted_purple_gold',
            ],
            data_get($product->product_config, 'options.special_finish.values.*.code'),
        );
        $this->assertSame(
            'Custom',
            data_get($product->product_config, 'options.sizes.values.2.label'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/square-size.webp',
            data_get($product->product_config, 'options.sizes.values.1.swatch_image'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/custom-size.webp',
            data_get($product->product_config, 'options.sizes.values.2.swatch_image'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/matte-paper-finish.webp',
            data_get($product->product_config, 'options.paper_finish.values.0.swatch_image'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/gloss-paper-finish.webp',
            data_get($product->product_config, 'options.paper_finish.values.1.swatch_image'),
        );
        $this->assertSame(
            'With a smooth feel. Shine-free so no glare.',
            data_get($product->product_config, 'options.paper_finish.values.0.description'),
        );
        $this->assertSame(
            'Eye-catchingly shiny. Makes color photos pop.',
            data_get($product->product_config, 'options.paper_finish.values.1.description'),
        );
        $this->assertSame(
            'UV effect business card',
            data_get($product->product_config, 'options.paper_finish.values.2.description'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/square.webp',
            data_get($product->product_config, 'options.corners.values.0.swatch_image'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/rounded.webp',
            data_get($product->product_config, 'options.corners.values.1.swatch_image'),
        );
        $this->assertSame(
            '',
            data_get($product->product_config, 'options.corners.values.1.description'),
        );
        $this->assertArrayNotHasKey('print_code', $product->product_config['options']);
        $this->assertSame('Keep this FAQ', data_get($product->product_config, 'faq.0.question'));
    }

    public function test_custom_dimensions_are_normalized_and_priced_as_standard_cards(): void
    {
        $product = $this->makeProduct([
            'options' => [
                'sizes' => [
                    'values' => [
                        ['code' => 'standard', 'label' => 'Standard'],
                        ['code' => 'custom', 'label' => 'Custom'],
                    ],
                ],
                'paper_finish' => [
                    'values' => [['code' => 'matte', 'label' => 'Matte']],
                ],
                'corners' => [
                    'values' => [['code' => 'square', 'label' => 'Square']],
                ],
            ],
            'pricing' => [
                'mode' => 'rule_based',
                'rules' => [[
                    'id' => 'standard',
                    'match' => ['sizes' => 'standard'],
                    'pricing' => [
                        'basePrice' => 0.065,
                        'startQuantity' => 200,
                        'paperRates' => ['200' => 0],
                        'processes' => [],
                    ],
                ]],
            ],
        ]);

        $pricing = app(PricingService::class);
        $normalized = $pricing->validateOptions($product, [
            'sizes' => 'custom',
            'custom_width' => '2.10',
            'custom_height' => '3.5',
        ]);

        $this->assertSame('2.10', $normalized['custom_width']);
        $this->assertSame('3.50', $normalized['custom_height']);
        $this->assertSame(
            13.0,
            $pricing->calculate($product->id, $normalized + [
                'paper_finish' => 'matte',
                'corners' => 'square',
                'quantity' => '200',
            ]),
        );
    }

    public function test_custom_dimensions_outside_the_allowed_range_are_rejected(): void
    {
        $product = $this->makeProduct([
            'options' => [
                'sizes' => [
                    'values' => [['code' => 'custom', 'label' => 'Custom']],
                ],
            ],
        ]);

        $this->expectException(ValidationException::class);

        app(PricingService::class)->validateOptions($product, [
            'sizes' => 'custom',
            'custom_width' => '2.09',
            'custom_height' => '3.51',
        ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function makeProduct(array $config = []): Product
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        return Product::create([
            'name' => 'Classic Standard Business Cards',
            'slug' => 'classic-standard-business-cards',
            'product_category_id' => $category->id,
            'product_config' => $config,
        ]);
    }
}
