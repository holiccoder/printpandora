<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\PricingService;
use App\Services\ProductConfigurationService;
use App\Support\FlyersAndBrochuresProductCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FlyersAndBrochuresProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_flyer_products_are_seeded_with_the_requested_options(): void
    {
        foreach (FlyersAndBrochuresProductCatalog::slugs() as $slug) {
            $product = Product::query()->where('slug', $slug)->firstOrFail();
            $config = $product->product_config;

            $this->assertSame('mm', data_get($config, 'options.sizes.values.0.unit'));
            $this->assertSame('custom', data_get($config, 'options.sizes.values.5.code'));
            $this->assertSame(100, data_get($config, 'options.sizes.values.5.min_width'));
            $this->assertSame(600, data_get($config, 'options.sizes.values.5.max_width'));
            $this->assertCount(4, data_get($config, 'media.gallery'));

            foreach (data_get($config, 'media.gallery', []) as $image) {
                $this->assertFileExists(public_path(ltrim($image, '/')));
            }
        }

        $classicFinishes = data_get(
            Product::query()->where('slug', 'classic-standard-flyers-and-brochures')->firstOrFail()->product_config,
            'options.paper_finish.values',
        );

        $this->assertSame(
            ['Matte Lamination', 'Gloss Lamination', 'Gloss Varnish', 'Soft-Touch Lamination'],
            array_column($classicFinishes, 'label'),
        );
        $classicFinishSwatches = [
            '/images/product-options/flyers-and-brochures/paper-finishes/matte-lamination.png',
            '/images/product-options/flyers-and-brochures/paper-finishes/gloss-lamination.png',
            '/images/product-options/flyers-and-brochures/paper-finishes/gloss-varnish.png',
            '/images/product-options/flyers-and-brochures/paper-finishes/soft-touch-lamination.png',
        ];
        $this->assertSame($classicFinishSwatches, array_column($classicFinishes, 'swatch_image'));

        foreach ($classicFinishSwatches as $swatch) {
            $this->assertFileExists(public_path(ltrim($swatch, '/')));
        }

        foreach ([
            'quality-flyers-and-brochures',
            'special-flyers-and-brochures',
            'super-flyers-and-brochures',
        ] as $slug) {
            $product = Product::query()->where('slug', $slug)->firstOrFail();
            $this->assertArrayNotHasKey('paper_finish', $product->product_config['options']);

            $storefrontOptions = app(ProductConfigurationService::class)->storefrontOptions($product);
            $this->assertNotContains(
                'paper_finish',
                array_column($storefrontOptions['option_groups'] ?? [], 'key'),
            );
        }
        $this->assertCount(
            6,
            data_get(
                Product::query()->where('slug', 'classic-standard-flyers-and-brochures')->firstOrFail()->product_config,
                'options.folding.values',
            ),
        );
        $this->assertCount(
            1,
            data_get(
                Product::query()->where('slug', 'quality-flyers-and-brochures')->firstOrFail()->product_config,
                'options.folding.values',
            ),
        );
    }

    public function test_flyer_routes_and_header_menu_use_the_requested_paths(): void
    {
        $this->get('/flyers-and-brochures/classic-standard')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('shop/show')
                ->where('product.slug', 'classic-standard-flyers-and-brochures')
                ->where('productOptions.option_groups.0.key', 'sizes')
                ->where('productOptions.option_groups.1.key', 'paper_finish')
                ->where('productOptions.option_groups.2.key', 'folding'));

        $this->get('/classic-standard-flyers-and-brochures')
            ->assertStatus(301)
            ->assertRedirect('/flyers-and-brochures/classic-standard');

        $this->get('/flyers-and-brochures')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('content.global_chrome.header.flyers_brochures_mega_menu.link_groups.0.links', 4)
                ->where('content.global_chrome.header.flyers_brochures_mega_menu.link_groups.0.links.0.children.0.href', '/flyers-and-brochures/classic-standard')
                ->where('content.global_chrome.header.flyers_brochures_mega_menu.link_groups.0.links.0.children.1.href', '/flyers-and-brochures/classic-super')
                ->where('content.global_chrome.header.flyers_brochures_mega_menu.link_groups.0.links.0.children.2.href', '/flyers-and-brochures/classic-luxe')
                ->where('content.global_chrome.header.flyers_brochures_mega_menu.link_groups.0.links.1.href', '/flyers-and-brochures/quality')
                ->where('content.global_chrome.header.flyers_brochures_mega_menu.link_groups.0.links.2.href', '/flyers-and-brochures/special')
                ->where('content.global_chrome.header.flyers_brochures_mega_menu.link_groups.0.links.3.href', '/flyers-and-brochures/super'));
    }

    public function test_area_pricing_uses_millimetres_for_fixed_and_custom_sizes(): void
    {
        $product = Product::query()
            ->where('slug', 'classic-standard-flyers-and-brochures')
            ->firstOrFail();
        $pricing = app(PricingService::class);
        $storefront = app(ProductConfigurationService::class)->storefrontOptions($product);
        $this->assertSame('mm', data_get($storefront, 'option_groups.0.values.5.unit'));

        $fixed = $pricing->validateOptions($product, [
            'sizes' => '105x148',
            'paper_finish' => 'matte_lamination',
            'folding' => 'half_fold',
            'quantity' => '200',
        ]);

        $this->assertSame('0.01554000', $fixed['paper_area']);
        $this->assertSame(30.0, $pricing->calculate($product->id, $fixed));

        $custom = $pricing->validateOptions($product, [
            'sizes' => 'custom',
            'custom_width' => '100',
            'custom_height' => '100',
            'paper_finish' => 'matte_lamination',
            'folding' => 'half_fold',
            'quantity' => '200',
        ]);

        $this->assertSame('0.01000000', $custom['paper_area']);
        $this->assertSame(20.0, $pricing->calculate($product->id, $custom));

        $this->expectException(ValidationException::class);
        $pricing->validateOptions($product, [
            'sizes' => 'custom',
            'custom_width' => '99',
            'custom_height' => '100',
        ]);
    }
}
