<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\PricingService;
use App\Services\ProductConfigurationService;
use App\Support\PrintDesignSpecifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StickerProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_sticker_products_include_design_specifications(): void
    {
        foreach (['classic-stickers', 'premium-stickers', 'super-stickers'] as $slug) {
            $product = Product::query()->where('slug', $slug)->firstOrFail();

            $this->assertSame(
                PrintDesignSpecifications::businessCards(),
                data_get($product->product_config, 'detail_sections.design_specifications'),
            );
        }
    }

    public function test_sticker_products_are_created_with_the_requested_option_contract(): void
    {
        $this->assertSame(
            3,
            Product::whereIn('slug', [
                'classic-stickers',
                'premium-stickers',
                'super-stickers',
            ])->count(),
        );

        $expectedSizeCodes = ['1x2', '1x2_8', '1_5x5_5', '2x3', '3x4', '4x6'];
        $expectedSizeLabels = [
            '1 x 2 in',
            '1 x 2.8 in',
            '1.5 x 5.5 in',
            '2 x 3 in',
            '3 x 4 in',
            '4 x 6 in',
        ];
        $expectedSizeSwatches = [
            '/images/product-options/stickers/size-swatches/1-x-2-in.webp',
            '/images/product-options/stickers/size-swatches/1-x-2-8-in.webp',
            '/images/product-options/stickers/size-swatches/1-5-x-5-5-in.webp',
            '/images/product-options/stickers/size-swatches/2-x-3-in.webp',
            '/images/product-options/stickers/size-swatches/3-x-4-in.webp',
            '/images/product-options/stickers/size-swatches/4-x-6-in.webp',
        ];

        foreach (['classic-stickers', 'premium-stickers', 'super-stickers'] as $slug) {
            $config = Product::where('slug', $slug)->firstOrFail()->product_config;

            $this->assertSame($expectedSizeCodes, data_get($config, 'options.sizes.values.*.code'));
            $this->assertSame($expectedSizeLabels, data_get($config, 'options.sizes.values.*.label'));
            $this->assertSame($expectedSizeSwatches, data_get($config, 'options.sizes.values.*.swatch_image'));
        }

        $expectedShapeCodes = ['die_cut', 'round', 'rounded_corner', 'square_corner'];
        $expectedShapeSwatches = [
            '/images/product-options/stickers/shapes/any-shape.png',
            '/images/product-options/stickers/shapes/round.png',
            '/images/product-options/stickers/shapes/rounded-corner.png',
            '/images/product-options/stickers/shapes/square-corner.png',
        ];

        foreach (['classic-stickers', 'premium-stickers', 'super-stickers'] as $slug) {
            $config = Product::where('slug', $slug)->firstOrFail()->product_config;

            $this->assertSame(
                $expectedShapeCodes,
                data_get($config, 'options.shape.values.*.code'),
            );
            $this->assertSame(
                $expectedShapeSwatches,
                data_get($config, 'options.shape.values.*.swatch_image'),
            );
        }

        $config = Product::where('slug', 'premium-stickers')->firstOrFail()->product_config;
        $this->assertSame(
            [
                'off_white_grass_scented',
                'off_white_cotton_fiber',
                'dark_yellow_grass_scented',
                'woodgrain',
                'white_fabric_texture',
                'pearl_white',
                'antique_water_ripple_white',
                'masking_paper',
            ],
            data_get($config, 'options.material.values.*.code'),
        );
        $this->assertSame(
            0.00129032,
            data_get($config, 'options.sizes.values.0.area_sq_m'),
        );

        $materials = data_get($config, 'options.material.values');
        $rules = data_get($config, 'media.gallery_rules');

        foreach ($materials as $material) {
            $rule = collect($rules)->first(
                fn (array $candidate): bool => data_get($candidate, 'match.material') === $material['code'],
            );

            $this->assertSame($material['swatch_image'], data_get($rule, 'primary'));
            $this->assertSame([$material['swatch_image']], data_get($rule, 'images'));
        }
    }

    public function test_sticker_storefront_preserves_area_metadata_and_routes_render(): void
    {
        $product = Product::where('slug', 'classic-stickers')->firstOrFail();
        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertNotNull($options);
        $sizes = collect($options['option_groups'])
            ->firstWhere('key', 'sizes')['values'];
        $shapes = collect($options['option_groups'])
            ->firstWhere('key', 'shape')['values'];

        $this->assertSame(0.00129032, $sizes[0]['area_sq_m']);
        $this->assertSame('1 x 2 in', $sizes[0]['name']);
        $this->assertSame('4 x 6 in', $sizes[5]['name']);
        $this->assertSame(7, count($options['galleries']));
        $this->assertSame(
            [
                '/images/product-options/stickers/shapes/any-shape.png',
                '/images/product-options/stickers/shapes/round.png',
                '/images/product-options/stickers/shapes/rounded-corner.png',
                '/images/product-options/stickers/shapes/square-corner.png',
            ],
            array_column($shapes, 'swatch_image'),
        );

        foreach ([
            '/stickers/classic' => 'classic-stickers',
            '/stickers/premium' => 'premium-stickers',
            '/stickers/super' => 'super-stickers',
        ] as $path => $slug) {
            $this->get($path)
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('shop/show')
                    ->where('product.slug', $slug)
                    ->where(
                        'productOptions.detail_sections.design_specifications.heading',
                        'Design Specifications',
                    ));
        }

        $this->get('/classic-stickers')->assertStatus(301)->assertRedirect('/stickers/classic');
        $this->get('/premium-stickers')->assertStatus(301)->assertRedirect('/stickers/premium');
        $this->get('/super-stickers')->assertStatus(301)->assertRedirect('/stickers/super');
    }

    public function test_sticker_prices_are_derived_from_selected_dimensions(): void
    {
        $pricing = app(PricingService::class);
        $premium = Product::where('slug', 'premium-stickers')->firstOrFail();
        $super = Product::where('slug', 'super-stickers')->firstOrFail();

        $premiumFifty = $pricing->calculate($premium->id, [
            'quantity' => '50',
            'sizes' => '1x2',
        ]);
        $premiumTwoByThree = $pricing->calculate($premium->id, [
            'quantity' => '50',
            'sizes' => '2x3',
            'paper_area' => '0.012',
        ]);
        $superFourBySixFifty = $pricing->calculate($super->id, [
            'quantity' => '50',
            'sizes' => '4x6',
        ]);
        $superForgedAreaFifty = $pricing->calculate($super->id, [
            'quantity' => '50',
            'sizes' => '4x6',
            'paper_area' => '0.012',
        ]);

        $this->assertSame(11.0, $premiumFifty);
        $this->assertSame(33.0, $premiumTwoByThree);
        $this->assertSame(167.0, $superFourBySixFifty);
        $this->assertSame($superFourBySixFifty, $superForgedAreaFifty);
    }

    public function test_sticker_paper_area_is_derived_and_manual_values_are_ignored(): void
    {
        $pricing = app(PricingService::class);

        foreach (['classic-stickers', 'premium-stickers', 'super-stickers'] as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();
            $normalized = $pricing->validateOptions($product, [
                'sizes' => '1x2',
                'paper_area' => '0.012',
            ]);

            $this->assertSame('0.00129032', $normalized['paper_area']);
        }
    }

    public function test_sticker_sizes_are_required_and_custom_sizes_are_removed(): void
    {
        $product = Product::where('slug', 'classic-stickers')->firstOrFail();
        $pricing = app(PricingService::class);

        foreach ([
            [],
            ['sizes' => 'unknown'],
            ['sizes' => 'custom'],
            ['sizes' => '2x2'],
        ] as $options) {
            try {
                $pricing->validateOptions($product, $options);
                $this->fail('An invalid sticker size selection was accepted.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertSame(
            '0.00129032',
            $pricing->validateOptions($product, ['sizes' => '1x2'])['paper_area'],
        );
    }
}
