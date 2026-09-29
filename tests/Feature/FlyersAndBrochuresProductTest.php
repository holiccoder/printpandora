<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\PricingService;
use App\Services\ProductConfigurationService;
use App\Support\FlyersAndBrochuresProductCatalog;
use App\Support\PrintDesignSpecifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FlyersAndBrochuresProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_flyer_products_include_design_specifications(): void
    {
        foreach (FlyersAndBrochuresProductCatalog::slugs() as $slug) {
            $product = Product::query()->where('slug', $slug)->firstOrFail();

            $this->assertSame(
                PrintDesignSpecifications::businessCards(),
                data_get($product->product_config, 'detail_sections.design_specifications'),
            );
        }
    }

    public function test_all_flyer_products_are_seeded_with_the_requested_options(): void
    {
        foreach (FlyersAndBrochuresProductCatalog::slugs() as $slug) {
            $product = Product::query()->where('slug', $slug)->firstOrFail();
            $config = $product->product_config;

            $this->assertSame('in', data_get($config, 'options.sizes.values.0.unit'));
            $this->assertSame([
                ['105x148', '4.13', '5.83', '4.13 × 5.83 in'],
                ['120x120', '4.72', '4.72', '4.72 × 4.72 in'],
                ['99x210', '3.90', '8.27', '3.90 × 8.27 in'],
                ['148x210', '5.83', '8.27', '5.83 × 8.27 in'],
                ['210x297', '8.27', '11.69', '8.27 × 11.69 in'],
            ], array_map(
                static fn (array $value): array => [
                    $value['code'],
                    $value['width'],
                    $value['height'],
                    $value['description'],
                ],
                array_slice(data_get($config, 'options.sizes.values'), 0, 5),
            ));
            $this->assertSame('custom', data_get($config, 'options.sizes.values.5.code'));
            $this->assertSame('in', data_get($config, 'options.sizes.values.5.unit'));
            $this->assertSame('3.94', data_get($config, 'options.sizes.values.5.min_width'));
            $this->assertSame('23.62', data_get($config, 'options.sizes.values.5.max_width'));
            $this->assertArrayNotHasKey('description', data_get($config, 'options.sizes.values.5'));
            $this->assertCount(4, data_get($config, 'media.gallery'));

            foreach (data_get($config, 'media.gallery', []) as $image) {
                $this->assertFileExists(public_path(ltrim($image, '/')));
            }
        }

        $expectedFinishLabels = [
            'Gloss Varnish',
        ];
        $expectedFinishSwatches = array_column(
            data_get(
                FlyersAndBrochuresProductCatalog::definition('classic-standard-flyers-and-brochures'),
                'product_config.options.paper_finish.values',
                [],
            ),
            'swatch_image',
        );
        foreach (['classic-standard-flyers-and-brochures'] as $slug) {
            $product = Product::query()->where('slug', $slug)->firstOrFail();
            $finishes = data_get($product->product_config, 'options.paper_finish.values');

            $this->assertSame($expectedFinishLabels, array_column($finishes, 'label'));
            $this->assertSame(['gloss_varnish'], array_column($finishes, 'code'));
            $this->assertSame('gloss_varnish', data_get($product->product_config, 'options.paper_finish.default'));

            foreach ($expectedFinishSwatches as $swatch) {
                $this->assertFileExists(public_path(ltrim($swatch, '/')));
            }

            $storefrontOptions = app(ProductConfigurationService::class)->storefrontOptions($product);
            $this->assertContains(
                'paper_finish',
                array_column($storefrontOptions['option_groups'] ?? [], 'key'),
            );
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
                ->where('productOptions.option_groups.2.key', 'folding')
                ->where(
                    'productOptions.detail_sections.design_specifications.heading',
                    'Design Specifications',
                ));

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

    public function test_area_pricing_uses_inches_for_fixed_and_custom_sizes(): void
    {
        $product = Product::query()
            ->where('slug', 'classic-standard-flyers-and-brochures')
            ->firstOrFail();
        $pricing = app(PricingService::class);
        $storefront = app(ProductConfigurationService::class)->storefrontOptions($product);
        $this->assertSame('in', data_get($storefront, 'option_groups.0.values.5.unit'));
        $this->assertArrayNotHasKey(
            'description',
            data_get($storefront, 'option_groups.0.values.5'),
        );

        $fixed = $pricing->validateOptions($product, [
            'sizes' => '105x148',
            'paper_finish' => 'gloss_varnish',
            'folding' => 'half_fold',
            'quantity' => '200',
        ]);

        $this->assertSame('0.01554000', $fixed['paper_area']);
        $this->assertSame(30.0, $pricing->calculate($product->id, $fixed));

        $custom = $pricing->validateOptions($product, [
            'sizes' => 'custom',
            'custom_width' => '3.94',
            'custom_height' => '3.94',
            'paper_finish' => 'gloss_varnish',
            'folding' => 'half_fold',
            'quantity' => '200',
        ]);

        $this->assertSame('0.01001521', $custom['paper_area']);
        $this->assertSame(20.0, $pricing->calculate($product->id, $custom));

        $this->expectException(ValidationException::class);
        $pricing->validateOptions($product, [
            'sizes' => 'custom',
            'custom_width' => '3.93',
            'custom_height' => '3.94',
        ]);
    }
}
