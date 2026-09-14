<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\PricingService;
use App\Services\ProductConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StickerProductTest extends TestCase
{
    use RefreshDatabase;

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

        $product = Product::where('slug', 'premium-stickers')->firstOrFail();
        $config = $product->product_config;

        $this->assertSame(
            ['2x2', '3x3', '4x4', '5x5', 'custom'],
            data_get($config, 'options.sizes.values.*.code'),
        );
        $this->assertSame(
            ['square_corner', 'die_cut'],
            data_get($config, 'options.shape.values.*.code'),
        );
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
            0.00258064,
            data_get($config, 'options.sizes.values.0.area_sq_m'),
        );
        $this->assertSame(
            '0.71',
            data_get($config, 'options.sizes.values.4.min_width'),
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

        $this->assertSame(0.00258064, $sizes[0]['area_sq_m']);
        $this->assertSame('18 x 18 mm minimum; up to 430 x 301 mm.', $sizes[4]['description']);
        $this->assertSame(7, count($options['galleries']));

        foreach ([
            '/stickers/classic' => 'classic-stickers',
            '/stickers/premium' => 'premium-stickers',
            '/stickers/super' => 'super-stickers',
        ] as $path => $slug) {
            $this->get($path)
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('shop/show')
                    ->where('product.slug', $slug));
        }

        $this->get('/classic-stickers')->assertStatus(301)->assertRedirect('/stickers/classic');
        $this->get('/premium-stickers')->assertStatus(301)->assertRedirect('/stickers/premium');
        $this->get('/super-stickers')->assertStatus(301)->assertRedirect('/stickers/super');
    }

    public function test_workbook_area_formula_is_used_for_sticker_prices(): void
    {
        $pricing = app(PricingService::class);
        $premium = Product::where('slug', 'premium-stickers')->firstOrFail();
        $super = Product::where('slug', 'super-stickers')->firstOrFail();

        $premiumFifty = $pricing->calculate($premium->id, [
            'quantity' => '50',
            'paper_area' => '0.012',
        ]);
        $premiumHundred = $pricing->calculate($premium->id, [
            'quantity' => '100',
            'paper_area' => '0.012',
        ]);
        $superFifty = $pricing->calculate($super->id, [
            'quantity' => '50',
            'paper_area' => '0.012',
        ]);

        $this->assertSame(101.0, $premiumFifty);
        $this->assertSame(118.0, $premiumHundred);
        $this->assertSame(130.0, $superFifty);
    }

    public function test_sticker_paper_area_is_required_and_normalized(): void
    {
        $product = Product::where('slug', 'classic-stickers')->firstOrFail();
        $pricing = app(PricingService::class);

        $normalized = $pricing->validateOptions($product, [
            'sizes' => '2x2',
            'paper_area' => '0.012',
        ]);

        $this->assertSame('0.012000', $normalized['paper_area']);

        $this->expectException(ValidationException::class);
        $pricing->validateOptions($product, ['sizes' => '2x2']);
    }
}
