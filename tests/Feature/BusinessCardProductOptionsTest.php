<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductConfigurationService;
use Database\Seeders\BusinessCardProductOptionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCardProductOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_preserves_database_product_detail_copy(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        foreach (['classic-standard-business-cards', 'classic-special-business-cards'] as $slug) {
            Product::create([
                'name' => str_replace('-', ' ', $slug),
                'slug' => $slug,
                'product_category_id' => $category->id,
                'product_config' => [
                    'options' => [
                        'sizes' => [
                            'values' => [
                                ['code' => 'standard', 'swatch_image' => '/legacy-standard.png'],
                                ['code' => 'square', 'swatch_image' => '/legacy-square.png'],
                                ['code' => 'custom', 'description' => 'Legacy custom range'],
                            ],
                        ],
                    ],
                    'detail_sections' => [
                        'design_specifications' => ['heading' => 'Keep this section'],
                        'feature_cards' => [
                            ['title' => 'Keep this title'],
                            ['tooltip_content' => 'Replace this copy'],
                        ],
                    ],
                ],
            ]);
        }

        (new BusinessCardProductOptionsSeeder)->run();

        $standard = Product::where('slug', 'classic-standard-business-cards')->firstOrFail();
        $special = Product::where('slug', 'classic-special-business-cards')->firstOrFail();

        $this->assertSame(
            'Keep this title',
            data_get($standard->product_config, 'detail_sections.feature_cards.0.title'),
        );
        $this->assertSame(
            'Keep this section',
            data_get($special->product_config, 'detail_sections.design_specifications.heading'),
        );
        $this->assertStringContainsString(
            'replace this copy',
            strtolower((string) data_get($standard->product_config, 'detail_sections.feature_cards.1.tooltip_content')),
        );
        $this->assertStringContainsString(
            'replace this copy',
            strtolower((string) data_get($special->product_config, 'detail_sections.feature_cards.1.tooltip_content')),
        );

        foreach ([$standard, $special] as $product) {
            $this->assertSame(
                '/images/product-options/business-cards/swatches/standard-size.webp',
                data_get($product->product_config, 'options.sizes.values.0.swatch_image'),
            );
            $this->assertSame(
                '/images/product-options/business-cards/swatches/square-size.webp',
                data_get($product->product_config, 'options.sizes.values.1.swatch_image'),
            );
            $this->assertSame(
                $product->slug === 'classic-standard-business-cards'
                    ? '2.1 - 3.5 inches'
                    : 'max range: 2.1 - 3.5 inches',
                data_get($product->product_config, 'options.sizes.values.2.description'),
            );
        }
    }

    public function test_requested_contracts_are_applied_and_existing_content_is_preserved(): void
    {
        $businessCards = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);
        $pvc = ProductCategory::create([
            'name' => 'PVC Business Cards',
            'slug' => 'pvc-business-cards',
            'parent_id' => $businessCards->id,
        ]);

        $quality = Product::create([
            'name' => 'Standard Quality Business Cards',
            'slug' => 'standard-quality-business-cards',
            'product_category_id' => $businessCards->id,
            'product_config' => [
                'options' => [
                    'sizes' => ['values' => [['code' => 'standard'], ['code' => 'square']]],
                    'paper_finish' => ['values' => [['code' => 'matte']]],
                ],
                'media' => ['gallery' => ['/images/quality.jpg']],
                'faq' => [['question' => 'Keep this FAQ', 'answer' => 'Yes']],
                'detail_sections' => ['design_specifications' => ['heading' => 'Keep this design spec']],
            ],
        ]);

        foreach (['basic-pvc-card', 'standard-pvc-card', 'premium-pvc-card'] as $slug) {
            Product::create([
                'name' => $slug,
                'slug' => $slug,
                'subtitle' => "Database subtitle for {$slug}",
                'description' => "Database description for {$slug}",
                'product_category_id' => $pvc->id,
                'product_config' => [],
            ]);
        }

        (new BusinessCardProductOptionsSeeder)->run();

        $quality->refresh();

        $this->assertSame(
            ['standard', 'square', 'custom'],
            data_get($quality->product_config, 'options.sizes.values.*.code'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/standard-size.webp',
            data_get($quality->product_config, 'options.sizes.values.0.swatch_image'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/square-size.webp',
            data_get($quality->product_config, 'options.sizes.values.1.swatch_image'),
        );
        $this->assertSame(
            'max range: 2.1 - 3.5 inches',
            data_get($quality->product_config, 'options.sizes.values.2.description'),
        );
        $this->assertSame(
            ['matte', 'gloss', 'starlight_film', 'holographic_film', 'soft_touch_film'],
            data_get($quality->product_config, 'options.texture.values.*.code'),
        );
        $this->assertSame(
            'Paper Finish',
            data_get($quality->product_config, 'options.texture.label'),
        );
        $this->assertSame(
            [
                '/images/product-options/business-cards/laminates/matte-526x251.jpg',
                '/images/product-options/business-cards/laminates/gloss-526x251.jpg',
                '/images/product-options/business-cards/swatches/quality/starlight-film.png',
                '/images/product-options/business-cards/swatches/quality/holographic-film.png',
                '/images/product-options/business-cards/swatches/quality/soft-touch-film.png',
            ],
            data_get($quality->product_config, 'options.texture.values.*.swatch_image'),
        );
        $this->assertFalse(data_get($quality->product_config, 'options.texture.required'));
        $this->assertSame('matte', data_get($quality->product_config, 'options.texture.default'));
        $this->assertSame('3D UV', data_get($quality->product_config, 'options.uv_finish.label'));
        $this->assertFalse(data_get($quality->product_config, 'options.uv_finish.required'));
        $this->assertNull(data_get($quality->product_config, 'options.uv_finish.default'));
        $this->assertSame(
            ['single_side_uv', 'both_sides_uv'],
            data_get($quality->product_config, 'options.uv_finish.values.*.code'),
        );
        $this->assertSame(
            ['single side', 'both sides'],
            data_get($quality->product_config, 'options.uv_finish.values.*.label'),
        );
        $this->assertSame(
            [
                '/images/products/standard-quality-business-cards/3d-uv.png',
                '/images/products/standard-quality-business-cards/3d-uv.png',
            ],
            data_get($quality->product_config, 'options.uv_finish.values.*.swatch_image'),
        );
        $this->assertArrayNotHasKey('paper_finish', $quality->product_config['options']);
        $this->assertSame(
            [
                '/images/products/standard-quality-business-cards/default-01.png',
                '/images/products/standard-quality-business-cards/default-02.png',
                '/images/products/standard-quality-business-cards/default-03.png',
                '/images/products/standard-quality-business-cards/default-04.png',
            ],
            data_get($quality->product_config, 'media.gallery'),
        );
        $this->assertSame(
            '/images/products/standard-quality-business-cards/default-01.png',
            $quality->featured_image,
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/cold/bright-gold.png',
            data_get($quality->product_config, 'options.special_finish.values', [])[
                array_search('cold_bright_gold', data_get($quality->product_config, 'options.special_finish.values.*.code'), true)
            ]['swatch_image'],
        );
        $this->assertContains(
            'cold_bright_gold',
            data_get($quality->product_config, 'options.special_finish.values.*.code'),
        );
        $this->assertSame('Keep this FAQ', data_get($quality->product_config, 'faq.0.question'));
        $this->assertSame('Keep this design spec', data_get($quality->product_config, 'detail_sections.design_specifications.heading'));

        $this->assertSame(
            ['matte', 'gloss', 'frosted'],
            data_get(Product::where('slug', 'basic-pvc-card')->firstOrFail()->product_config, 'options.paper_finish.values.*.code'),
        );
        $expectedPvcSwatches = [
            'basic-pvc-card' => [
                '/images/products/pvc/basic-pvc-card-matte.png',
                '/images/products/pvc/basic-pvc-card-gloss.png',
                '/images/products/pvc/basic-pvc-card-frosted.png',
            ],
            'standard-pvc-card' => [
                '/images/products/pvc/standard-pvc-card-matte.png',
                '/images/products/pvc/standard-pvc-card-gloss.png',
                '/images/products/pvc/standard-pvc-card-frosted.png',
            ],
            'premium-pvc-card' => [
                '/images/products/pvc/premium-pvc-card-matte.png',
                '/images/products/pvc/premium-pvc-card-gloss.png',
                '/images/products/pvc/premium-pvc-card-frosted.png',
            ],
        ];

        foreach (['basic-pvc-card', 'standard-pvc-card', 'premium-pvc-card'] as $slug) {
            $config = Product::where('slug', $slug)->firstOrFail()->product_config;

            $this->assertSame(
                ['matte', 'gloss', 'frosted'],
                data_get($config, 'options.paper_finish.values.*.code'),
            );
            $this->assertSame(
                $expectedPvcSwatches[$slug],
                data_get($config, 'options.paper_finish.values.*.swatch_image'),
            );

            $this->assertSame(
                [
                    '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
                    $expectedPvcSwatches[$slug][0],
                    $expectedPvcSwatches[$slug][1],
                    $expectedPvcSwatches[$slug][2],
                    '/images/products/pvc/pvc-01.jpg',
                    '/images/products/pvc/pvc-02.jpg',
                    '/images/products/pvc/pvc-03.jpg',
                    '/images/products/pvc/pvc-04.jpg',
                ],
                data_get($config, 'media.gallery'),
            );
        }
        $this->assertSame(
            ['no_print_code_or_signature_stripe', 'print_code', 'signature_stripe'],
            data_get(Product::where('slug', 'standard-pvc-card')->firstOrFail()->product_config, 'options.print_code_or_signature_stripe.values.*.code'),
        );
        $this->assertSame(
            [
                '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
                '/images/products/pvc/pvc-print-code.png',
                '/images/products/pvc/pvc-signature-stripe.png',
            ],
            data_get(Product::where('slug', 'standard-pvc-card')->firstOrFail()->product_config, 'options.print_code_or_signature_stripe.values.*.swatch_image'),
        );
        $this->assertSame(
            ['no_print_code', 'print_code'],
            data_get(Product::where('slug', 'premium-pvc-card')->firstOrFail()->product_config, 'options.print_code.values.*.code'),
        );
        $this->assertSame(
            '/images/products/pvc/pvc-print-code.png',
            data_get(Product::where('slug', 'premium-pvc-card')->firstOrFail()->product_config, 'options.print_code.values.1.swatch_image'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
            data_get(Product::where('slug', 'basic-pvc-card')->firstOrFail()->product_config, 'options.print_code.values.0.swatch_image'),
        );
        $this->assertSame(
            '/images/products/pvc/pvc-print-code.png',
            data_get(Product::where('slug', 'basic-pvc-card')->firstOrFail()->product_config, 'options.print_code.values.1.swatch_image'),
        );

        foreach (['basic-pvc-card', 'standard-pvc-card', 'premium-pvc-card'] as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();

            $this->assertSame("Database description for {$slug}", $product->description);
            $this->assertSame("Database subtitle for {$slug}", $product->subtitle);
            $this->assertSame("Database description for {$slug}", data_get($product->product_config, 'product.description'));
            $this->assertSame("Database subtitle for {$slug}", data_get($product->product_config, 'product.subtitle'));
        }

        foreach (['basic-pvc-card', 'standard-pvc-card', 'premium-pvc-card'] as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();
            $storefront = app(ProductConfigurationService::class)->storefrontOptions($product);
            $paperFinishGroup = collect($storefront['option_groups'] ?? [])->firstWhere('key', 'paper_finish');

            $expectedFinishImages = array_map(
                static fn (string $image): string => str_replace('.png', '.webp', $image),
                $expectedPvcSwatches[$slug],
            );

            $this->assertSame(
                $expectedFinishImages,
                data_get($paperFinishGroup, 'values.*.swatch_image'),
            );

            $expectedGalleryPrimaries = [
                'matte_gallery' => $expectedFinishImages[0],
                'gloss_gallery' => $expectedFinishImages[1],
                'frosted_gallery' => $expectedFinishImages[2],
            ];

            foreach ([
                'matte_gallery',
                'gloss_gallery',
                'frosted_gallery',
            ] as $galleryId) {
                $gallery = collect($storefront['galleries'] ?? [])->firstWhere('id', $galleryId);

                $this->assertSame($expectedGalleryPrimaries[$galleryId], data_get($gallery, 'images.0'));

                $storedGalleryRule = collect(data_get($product->product_config, 'media.gallery_rules', []))
                    ->firstWhere('id', $galleryId);

                $this->assertSame(
                    str_replace('.webp', '.png', $expectedGalleryPrimaries[$galleryId]),
                    data_get($storedGalleryRule, 'primary'),
                );
            }

            $expectedOptionGalleryPrimaries = $slug === 'standard-pvc-card'
                ? [
                    'no_print_code_gallery' => '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
                    'print_code_gallery' => '/images/products/pvc/pvc-print-code.webp',
                    'signature_stripe_gallery' => '/images/products/pvc/pvc-signature-stripe.webp',
                ]
                : [
                    'no_print_code_gallery' => '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
                    'print_code_gallery' => '/images/products/pvc/pvc-print-code.webp',
                ];

            foreach ($expectedOptionGalleryPrimaries as $galleryId => $expectedPrimary) {
                $gallery = collect($storefront['galleries'] ?? [])->firstWhere('id', $galleryId);

                $this->assertSame($expectedPrimary, data_get($gallery, 'images.0'));

                $storedGalleryRule = collect(data_get($product->product_config, 'media.gallery_rules', []))
                    ->firstWhere('id', $galleryId);

                $this->assertSame(
                    str_replace('.webp', '.png', $expectedPrimary),
                    data_get($storedGalleryRule, 'primary'),
                );
            }
        }
    }

    public function test_missing_metal_products_are_created_under_business_cards(): void
    {
        $businessCards = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        (new BusinessCardProductOptionsSeeder)->run();

        $metal = ProductCategory::where('slug', 'metal-business-cards')->firstOrFail();

        $this->assertSame($businessCards->id, $metal->parent_id);
        $this->assertSame(
            ['classic-metal-business-cards', 'luxe-metal-business-cards', 'premium-metal-business-cards'],
            Product::where('product_category_id', $metal->id)->orderBy('slug')->pluck('slug')->all(),
        );
        $this->assertSame(
            ['0_3_mm', '0_5_mm'],
            data_get(Product::where('slug', 'classic-metal-business-cards')->firstOrFail()->product_config, 'options.thickness.values.*.code'),
        );
        $this->assertSame(
            ['laser_engraving', 'color_printing', 'plating'],
            data_get(Product::where('slug', 'premium-metal-business-cards')->firstOrFail()->product_config, 'options.special_finish.values.*.code'),
        );

        $expectedMetalSwatches = [
            'thickness' => [
                '/images/product-options/business-cards/swatches/metal/thickness-0-3mm.png',
                '/images/product-options/business-cards/swatches/metal/thickness-0-5mm.png',
            ],
            'sizes' => [
                '/images/product-options/business-cards/swatches/metal/size-89x51mm.png',
                '/images/product-options/business-cards/swatches/metal/size-85x54mm.png',
                '/images/product-options/business-cards/swatches/metal/size-80x50mm.png',
            ],
            'print_code_or_magnetic_stripe' => [
                '/images/product-options/business-cards/swatches/metal/no-print-code-or-magnetic-stripe.png',
                '/images/product-options/business-cards/swatches/metal/print-code.png',
                '/images/product-options/business-cards/swatches/metal/magnetic-stripe.png',
            ],
        ];
        $expectedMetalSizeTitles = [
            '3.5 × 2.0 inches',
            '3.35 × 2.13 inches',
            '3.15 × 1.97 inches',
        ];
        $expectedMetalSizeDescriptions = [
            '3.5 × 2.0 inches metal business card.',
            '3.35 × 2.13 inches metal business card.',
            '3.15 × 1.97 inches metal business card.',
        ];

        foreach (['classic-metal-business-cards', 'premium-metal-business-cards', 'luxe-metal-business-cards'] as $slug) {
            $config = Product::where('slug', $slug)->firstOrFail()->product_config;

            foreach ($expectedMetalSwatches as $group => $swatches) {
                $this->assertSame($swatches, data_get($config, "options.{$group}.values.*.swatch_image"));
            }

            $this->assertSame($expectedMetalSizeTitles, data_get($config, 'options.sizes.values.*.label'));
            $this->assertSame(
                $expectedMetalSizeDescriptions,
                data_get($config, 'options.sizes.values.*.description'),
            );

            if ($slug !== 'classic-metal-business-cards') {
                $this->assertSame(
                    [
                        '/images/product-options/business-cards/swatches/metal/laser-engraving.png',
                        '/images/product-options/business-cards/swatches/metal/color-printing.png',
                        '/images/product-options/business-cards/swatches/metal/plating.png',
                    ],
                    data_get($config, 'options.special_finish.values.*.swatch_image'),
                );
            }

            $this->assertSame('fixed_tiers', data_get($config, 'pricing.mode'));
            $this->assertSame([], data_get($config, 'pricing.scenarios'));
            $this->assertSame([], data_get($config, 'pricing.rules'));
        }

        $expectedGalleries = [
            'classic-metal-business-cards' => [
                '/images/products/metal/classic-metal-business-cards-01.png',
                '/images/products/metal/classic-metal-business-cards-02.png',
                '/images/products/metal/classic-metal-business-cards-03.png',
                '/images/products/metal/classic-metal-business-cards-04.png',
            ],
            'premium-metal-business-cards' => [
                '/images/products/metal/premium-metal-business-cards-01.png',
                '/images/products/metal/premium-metal-business-cards-02.png',
                '/images/products/metal/premium-metal-business-cards-03.png',
                '/images/products/metal/premium-metal-business-cards-04.png',
            ],
            'luxe-metal-business-cards' => [
                '/images/products/metal/luxe-metal-business-cards-04.png',
                '/images/products/metal/luxe-metal-business-cards-02.png',
                '/images/products/metal/luxe-metal-business-cards-03.png',
                '/images/products/metal/luxe-metal-business-cards-01.png',
            ],
        ];

        foreach ($expectedGalleries as $slug => $gallery) {
            $product = Product::where('slug', $slug)->firstOrFail();

            $this->assertSame($gallery, data_get($product->product_config, 'media.gallery'));
            $this->assertSame($gallery[0], $product->featured_image);
            $this->assertSame($gallery[0], data_get($product->product_config, 'media.gallery_rules.0.primary'));
        }

    }

    public function test_solid_quality_keeps_database_pricing_and_imports_option_gallery_data(): void
    {
        $businessCards = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        Product::create([
            'name' => 'Solid Quality Business Cards',
            'slug' => 'solid-quality-business-cards',
            'product_category_id' => $businessCards->id,
            'product_config' => [
                'pricing' => [
                    'mode' => 'rule_based',
                    'currency' => 'USD',
                    'total_rounding' => 'nearest_integer',
                    'scenarios' => [
                        'database' => [
                            'base_price_per_card' => 0.91,
                            'start_quantity' => 75,
                        ],
                    ],
                    'quantity_price_table' => [],
                    'rules' => [],
                ],
            ],
        ]);

        (new BusinessCardProductOptionsSeeder)->run();

        $product = Product::where('slug', 'solid-quality-business-cards')->firstOrFail();
        $config = $product->product_config;

        $this->assertSame('rule_based', data_get($config, 'pricing.mode'));
        $this->assertSame(0.91, data_get($config, 'pricing.scenarios.database.base_price_per_card'));
        $this->assertSame(75, data_get($config, 'pricing.scenarios.database.start_quantity'));
        $this->assertSame(
            '/images/product-options/business-cards/swatches/standard-size.webp',
            data_get($config, 'options.sizes.values.0.swatch_image'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/square-size.webp',
            data_get($config, 'options.sizes.values.1.swatch_image'),
        );
        $this->assertSame(
            ['standard', 'square', 'custom'],
            data_get($config, 'options.sizes.values.*.code'),
        );
        $this->assertSame(
            'max range: 2.1 - 3.5 inches',
            data_get($config, 'options.sizes.values.2.description'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/custom-size.webp',
            data_get($config, 'options.sizes.values.2.swatch_image'),
        );
        $this->assertSame(
            ['no_print_code', 'need_print_code'],
            data_get($config, 'options.print_code.values.*.code'),
        );
        $this->assertSame(
            [
                '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
                '/images/product-options/business-cards/swatches/pvc-print-code.png',
            ],
            data_get($config, 'options.print_code.values.*.swatch_image'),
        );
        $this->assertSame(
            ['no_drilling', 'needs_drilling'],
            data_get($config, 'options.drill.values.*.code'),
        );
        $this->assertSame(
            [
                '/images/product-options/business-cards/swatches/drilling/no-drilling.png',
                '/images/product-options/business-cards/swatches/drilling/needs-drilling.png',
            ],
            data_get($config, 'options.drill.values.*.swatch_image'),
        );
        $this->assertArrayNotHasKey('texture', $config['options']);
        $this->assertSame(
            ['matte', 'gloss', 'starry_film', 'soft_touch_film', 'holo_film'],
            data_get($config, 'options.paper_finish.values.*.code'),
        );
        $this->assertSame(
            [
                'Matte',
                'Gloss',
                'Starlight Film',
                'Soft-Touch Film',
                'Laser Film',
            ],
            data_get($config, 'options.paper_finish.values.*.label'),
        );
        $this->assertSame(
            [
                '/images/product-options/business-cards/laminates/matte-526x251.jpg',
                '/images/product-options/business-cards/laminates/gloss-526x251.jpg',
                '/images/product-options/business-cards/swatches/quality/starlight-film.png',
                '/images/product-options/business-cards/swatches/quality/soft-touch-film.png',
                '/images/product-options/business-cards/swatches/quality/holographic-film.png',
            ],
            data_get($config, 'options.paper_finish.values.*.swatch_image'),
        );
        $this->assertSame(
            ['single_side_uv', 'both_sides_uv'],
            data_get($config, 'options.uv_finish.values.*.code'),
        );
        $this->assertSame('3D UV', data_get($config, 'options.uv_finish.label'));
        $this->assertSame(
            ['single side', 'both sides'],
            data_get($config, 'options.uv_finish.values.*.label'),
        );
        $this->assertNull(data_get($config, 'options.uv_finish.default'));
        $this->assertSame('select', data_get($config, 'options.uv_finish.type'));
        $uvGalleryRule = collect(data_get($config, 'media.gallery_rules', []))
            ->firstWhere('id', '3d-uv-single-side');
        $this->assertSame(
            [
                'uv_finish' => 'single_side_uv',
                'special_finish' => 'no_special_finish',
            ],
            $uvGalleryRule['match'] ?? null,
        );
        $this->assertSame(
            ['uv_finish' => 'both_sides_uv', 'special_finish' => 'no_special_finish'],
            collect(data_get($config, 'media.gallery_rules', []))
                ->firstWhere('id', '3d-uv-both-sides')['match'] ?? null,
        );
        $this->assertSame(
            [
                '/images/products/solid-quality-business-cards/default-01.png',
                '/images/products/solid-quality-business-cards/default-02.png',
                '/images/products/solid-quality-business-cards/default-03.png',
                '/images/products/solid-quality-business-cards/default-04.png',
            ],
            data_get($config, 'media.gallery'),
        );
        $this->assertSame(
            '/images/products/solid-quality-business-cards/standard-matte-square.png',
            collect(data_get($config, 'media.gallery_rules', []))
                ->firstWhere('id', 'standard-matte-square')['primary'] ?? null,
        );
        $paperFinishPrimaries = collect(data_get($config, 'media.gallery_rules', []))
            ->filter(fn (mixed $rule): bool => is_array($rule)
                && in_array($rule['id'] ?? null, [
                    'standard-starlight-film',
                    'standard-laser-film',
                    'standard-soft-touch-film',
                ], true))
            ->pluck('primary', 'id');
        $this->assertSame(
            [
                'standard-starlight-film' => '/images/products/solid-quality-business-cards/texture/starlight-film-primary.png',
                'standard-laser-film' => '/images/products/solid-quality-business-cards/texture/laser-film-primary.png',
                'standard-soft-touch-film' => '/images/products/solid-quality-business-cards/texture/soft-touch-film-primary.png',
            ],
            $paperFinishPrimaries->all(),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/cold/bright-gold.png',
            data_get($config, 'options.special_finish.values.14.swatch_image'),
        );
        $this->assertSame(
            '/images/products/solid-quality-business-cards/default-01.png',
            $product->featured_image,
        );
        $storefront = app(ProductConfigurationService::class)->storefrontOptions($product);
        $this->assertSame(
            ['sizes', 'corners', 'paper_finish', 'uv_finish', 'special_finish', 'print_code', 'drill'],
            collect($storefront['option_groups'] ?? [])->pluck('key')->all(),
        );
        $this->assertArrayNotHasKey('rectangle', data_get($config, 'pricing.scenarios'));
    }

    public function test_legacy_products_are_normalized_to_the_same_contract(): void
    {
        $category = new ProductCategory([
            'name' => 'PVC Business Cards',
            'slug' => 'pvc-business-cards',
        ]);
        $contents = file_get_contents(base_path('content/product-options/pvc-business-cards/basic-pvc-card.json'));

        if ($contents === false) {
            $this->fail('The Basic PVC legacy options file could not be read.');
        }

        $product = new Product([
            'name' => 'Basic PVC Card',
            'slug' => 'basic-pvc-card',
            'product_options' => json_decode(
                $contents,
                true,
                512,
                JSON_THROW_ON_ERROR,
            ),
        ]);
        $product->setRelation('category', $category);

        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertTrue((bool) data_get($options, 'dynamic_options'));
        $this->assertSame(
            ['matte', 'gloss', 'frosted'],
            array_column(data_get($options, 'option_groups.0.values', []), 'code'),
        );
        $this->assertSame(
            ['no_print_code', 'print_code'],
            array_column(data_get($options, 'option_groups.1.values', []), 'code'),
        );
        $this->assertSame(
            [
                '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
                '/images/products/pvc/pvc-print-code.webp',
            ],
            array_column(data_get($options, 'option_groups.1.values', []), 'swatch_image'),
        );
    }

    public function test_all_cotton_business_cards_receive_the_shared_contract_and_imported_gallery(): void
    {
        $cotton = ProductCategory::create([
            'name' => 'Cotton Business Cards',
            'slug' => 'cotton-business-cards',
        ]);

        $slugs = [
            'basic-cotton-business-card',
            'classic-cotton-business-card',
            'premium-cotton-business-card',
            'luxe-cotton-business-card',
            'grand-cotton-business-card',
        ];

        $expectedThicknessCodes = ['300_360g', '360_450g', '450_700g'];
        $expectedThicknessLabels = ['300-360g', '360-450g', '450-700g'];
        $expectedTextureCodes = [
            'wild_300gsm_white',
            'montericca_360gsm_beige',
            'montericca_360gsm_cotton_white',
            'montericca_360gsm_earth_gray',
            'montericca_360gsm_deep_blue',
            'montericca_360gsm_teal_brown',
            'montericca_360gsm_black',
            'kakun_smooth_300gsm_deep_red',
            'kakun_smooth_300gsm_indigo',
            'kakun_smooth_300gsm_deep_blue',
            'guanya_352gsm_black',
            'zhenpi_360gsm_rhinoceros',
            'zhenpi_360gsm_lizard_black',
            'wild_450gsm_white',
            'wild_450gsm_light_brown',
            'wild_450gsm_dark_coffee',
            'wild_450gsm_black',
            'ningrou_420gsm_cream_yellow',
            'ningrou_420gsm_light_gray',
            'ningrou_420gsm_white',
            'ningrou_420gsm_indigo',
            'ningrou_420gsm_chocolate',
            'kakun_smooth_450gsm_black',
            'kakun_smooth_450gsm_snow_white',
            'guanya_446gsm_ultra_white',
            'guanya_446gsm_cream_yellow',
            'montericca_530gsm_cotton_white',
            'ningrou_523g_white',
            'haiti_550gsm_white',
            'haiti_550gsm_black',
            'haiti_550gsm_kraft',
            'aweike_550gsm_white',
            'aweike_550gsm_strong_black',
            'new_guanya_550gsm_cream_yellow',
            'new_guanya_550gsm_ultra_white',
            'sumei_700gsm_red',
            'sumei_700gsm_white',
            'sumei_700gsm_deep_blue',
            'sumei_700gsm_dark_gray',
            'sumei_700gsm_denim',
            'sumei_700gsm_black',
            'jazz_metal_500gsm_brushed_gold',
            'jazz_metal_500gsm_brushed_silver',
            'ice_white_pearl_500gsm_white',
            'songka_600gsm_pale_yellow',
            'extreme_ink_680gsm_black',
            'silk_classic_white_685gsm_white',
        ];
        $expectedTextureImages = array_map(
            static fn (string $code): string => '/images/products/cotton/paper-samples/'.str_replace('_', '-', $code).'.png',
            $expectedTextureCodes,
        );
        $expectedSpecialFinishImages = [
            '/images/products/cotton/special-finishes/laser-diagram.png',
            '/images/products/cotton/special-finishes/edge-coloring-diagram.png',
            '/images/products/cotton/special-finishes/double-mounting-diagram.png',
            '/images/products/cotton/special-finishes/custom-die-cut-diagram.png',
            '/images/products/cotton/special-finishes/emboss-deboss-diagram.png',
        ];
        $expectedSpecialFinishPrimaryImages = [
            '/images/products/cotton/special-finishes/laser.png',
            '/images/products/cotton/special-finishes/edge-coloring.png',
            '/images/products/cotton/special-finishes/double-mounting.png',
            '/images/products/cotton/special-finishes/emboss.png',
        ];

        foreach ($slugs as $slug) {
            Product::create([
                'name' => $slug,
                'slug' => $slug,
                'product_category_id' => $cotton->id,
                'product_config' => [
                    'media' => [
                        'gallery_rules' => [
                            [
                                'id' => 'default',
                                'match' => [],
                                'images' => ['/images/old-default.png'],
                                'primary' => '/images/old-default.png',
                            ],
                            [
                                'id' => 'rounded_corners',
                                'match' => ['corners' => 'rounded'],
                                'images' => ['/images/old-rounded.png'],
                                'primary' => '/images/old-rounded.png',
                            ],
                        ],
                    ],
                    'faq' => [['question' => 'Keep this FAQ', 'answer' => 'Yes']],
                    'detail_sections' => [
                        'design_specifications' => ['heading' => 'Keep this design spec'],
                    ],
                ],
            ]);
        }

        (new BusinessCardProductOptionsSeeder)->run();

        foreach ($slugs as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();
            $options = data_get($product->product_config, 'options');
            $shortSlug = str_replace('-cotton-business-card', '', $slug);
            $gallery = [
                "/images/products/cotton/{$shortSlug}/{$shortSlug}-01.png",
                "/images/products/cotton/{$shortSlug}/{$shortSlug}-02.png",
                "/images/products/cotton/{$shortSlug}/{$shortSlug}-03.png",
                "/images/products/cotton/{$shortSlug}/{$shortSlug}-04.png",
            ];

            $this->assertSame(
                ['sizes', 'corners', 'thickness', 'texture', 'special_finish'],
                array_keys($options),
            );
            $this->assertSame(
                ['standard', 'compact', 'custom'],
                data_get($options, 'sizes.values.*.code'),
            );
            $this->assertSame('3.54', data_get($options, 'sizes.values.0.width'));
            $this->assertSame('2.13', data_get($options, 'sizes.values.0.height'));
            $this->assertSame('3.5', data_get($options, 'sizes.values.1.width'));
            $this->assertSame('2.0', data_get($options, 'sizes.values.1.height'));
            $this->assertSame(
                [
                    '/images/product-options/business-cards/swatches/standard-size.webp',
                    '/images/product-options/business-cards/swatches/standard-size.webp',
                ],
                data_get($options, 'sizes.values.*.swatch_image')
                    ? array_slice(data_get($options, 'sizes.values.*.swatch_image'), 0, 2)
                    : [],
            );
            $this->assertSame('0.70', data_get($options, 'sizes.values.2.min_width'));
            $this->assertSame('3.54', data_get($options, 'sizes.values.2.max_width'));
            $this->assertSame('0.70', data_get($options, 'sizes.values.2.min_height'));
            $this->assertSame('2.13', data_get($options, 'sizes.values.2.max_height'));
            $this->assertSame(['square', 'rounded'], data_get($options, 'corners.values.*.code'));
            $this->assertSame(
                $expectedThicknessCodes,
                data_get($options, 'thickness.values.*.code'),
            );
            $this->assertSame(
                $expectedThicknessLabels,
                data_get($options, 'thickness.values.*.label'),
            );
            $this->assertSame('300_360g', data_get($options, 'thickness.default'));
            $this->assertSame(
                $expectedTextureCodes,
                data_get($options, 'texture.values.*.code'),
            );
            $this->assertSame(
                $expectedTextureImages,
                data_get($options, 'texture.values.*.swatch_image'),
            );
            $this->assertSame(
                [
                    ...array_fill(0, 13, '300_360g'),
                    ...array_fill(0, 13, '360_450g'),
                    ...array_fill(0, 21, '450_700g'),
                ],
                data_get($options, 'texture.values.*.thickness_code'),
            );
            $this->assertSame(
                'Wilde (300 GSM) · White',
                data_get($options, 'texture.values.0.label'),
            );
            $this->assertSame('Teal', data_get($options, 'texture.values.5.color_label'));
            foreach (data_get($options, 'texture.values.*.swatch_image', []) as $image) {
                $this->assertFileExists(
                    public_path(str_replace('.png', '.webp', ltrim($image, '/'))),
                );
            }
            $this->assertSame(
                [
                    'laser',
                    'edge_coloring',
                    'double_mounting',
                    'custom_die_cut',
                    'emboss',
                ],
                data_get($options, 'special_finish.values.*.code'),
            );
            $this->assertSame(
                $expectedSpecialFinishImages,
                data_get($options, 'special_finish.values.*.swatch_image'),
            );
            $this->assertSame('multi_select', data_get($options, 'special_finish.type'));
            $this->assertSame([], data_get($options, 'special_finish.default'));
            $this->assertSame($gallery, data_get($product->product_config, 'media.gallery'));
            $this->assertSame($gallery[0], $product->featured_image);
            $this->assertCount(53, data_get($product->product_config, 'media.gallery_rules'));
            $specialFinishRules = collect(data_get($product->product_config, 'media.gallery_rules', []))
                ->filter(fn (mixed $rule): bool => is_array($rule) && array_key_exists('special_finish', $rule['match'] ?? []))
                ->values();
            $this->assertSame(
                ['laser', 'edge_coloring', 'double_mounting', 'emboss'],
                $specialFinishRules->pluck('match.special_finish')->all(),
            );
            $this->assertSame(
                $expectedSpecialFinishPrimaryImages,
                $specialFinishRules->pluck('primary')->all(),
            );
            $this->assertSame(
                ['corners' => 'rounded'],
                data_get($product->product_config, 'media.gallery_rules.52.match'),
            );
            $textureRules = collect(data_get($product->product_config, 'media.gallery_rules', []))
                ->filter(fn (mixed $rule): bool => is_array($rule) && array_key_exists('texture', $rule['match'] ?? []));
            $this->assertCount(47, $textureRules);
            $this->assertSame(
                data_get($options, 'texture.values.*.swatch_image'),
                $textureRules->pluck('primary')->values()->all(),
            );
            $sampleRule = $textureRules->firstWhere('id', 'texture_montericca_360gsm_teal_brown');
            $this->assertSame(
                ['thickness' => '300_360g', 'texture' => 'montericca_360gsm_teal_brown'],
                $sampleRule['match'] ?? null,
            );
            $this->assertSame(
                '/images/products/cotton/paper-samples/montericca-360gsm-teal-brown.png',
                $sampleRule['primary'] ?? null,
            );
            $this->assertSame('Keep this FAQ', data_get($product->product_config, 'faq.0.question'));
            $this->assertSame(
                'Keep this design spec',
                data_get($product->product_config, 'detail_sections.design_specifications.heading'),
            );
        }
    }

    public function test_legacy_cotton_products_are_normalized_to_the_same_contract(): void
    {
        $category = new ProductCategory([
            'name' => 'Cotton Business Cards',
            'slug' => 'cotton-business-cards',
        ]);
        $contents = file_get_contents(base_path('content/product-options/cotton-business-cards/basic-cotton-business-card.json'));

        if ($contents === false) {
            $this->fail('The Basic cotton business card legacy options file could not be read.');
        }

        $product = new Product([
            'name' => 'Basic cotton business card',
            'slug' => 'basic-cotton-business-card',
            'product_options' => json_decode(
                $contents,
                true,
                512,
                JSON_THROW_ON_ERROR,
            ),
        ]);
        $product->setRelation('category', $category);

        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertTrue((bool) data_get($options, 'dynamic_options'));
        $this->assertSame(
            ['sizes', 'corners', 'thickness', 'texture', 'special_finish'],
            array_column(data_get($options, 'option_groups', []), 'key'),
        );
        $this->assertSame(
            ['standard', 'compact', 'custom'],
            array_column(data_get($options, 'option_groups.0.values', []), 'code'),
        );
        $this->assertSame(
            [
                '/images/product-options/business-cards/swatches/standard-size.webp',
                '/images/product-options/business-cards/swatches/standard-size.webp',
            ],
            array_slice(data_get($options, 'option_groups.0.values.*.swatch_image'), 0, 2),
        );
        $this->assertSame('0.70', data_get($options, 'option_groups.0.values.2.min_width'));
        $this->assertSame('3.54', data_get($options, 'option_groups.0.values.2.max_width'));
        $this->assertSame(
            ['300_360g', '360_450g', '450_700g'],
            array_column(data_get($options, 'option_groups.2.values', []), 'code'),
        );
        $this->assertSame(
            '300-360g',
            data_get($options, 'option_groups.2.values.0.name'),
        );
        $this->assertSame(
                ['laser', 'edge_coloring', 'double_mounting', 'custom_die_cut', 'emboss'],
            array_column(data_get($options, 'option_groups.4.values', []), 'code'),
        );
        $this->assertSame([], data_get($options, 'option_groups.4.default'));
        $this->assertSame(
            '/images/products/cotton/paper-samples/wild-300gsm-white.webp',
            data_get($options, 'option_groups.3.values.0.swatch_image'),
        );
        $this->assertSame(
            'Wilde (300 GSM)',
            data_get($options, 'option_groups.3.values.0.texture_label'),
        );
        $this->assertSame(
            'White',
            data_get($options, 'option_groups.3.values.0.color_label'),
        );
    }

    public function test_super_luxe_business_cards_receive_texture_options_and_standard_texture_galleries(): void
    {
        $businessCards = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);
        $product = Product::create([
            'name' => 'Super Luxe Business Cards',
            'slug' => 'super-luxe-business-cards',
            'product_category_id' => $businessCards->id,
            'product_config' => [
                'options' => [
                    'paper_finish' => ['values' => [['code' => 'matte']]],
                    'special_finish' => ['values' => [['code' => 'black_gold']]],
                ],
                'faq' => [['question' => 'Keep this FAQ', 'answer' => 'Yes']],
                'detail_sections' => [
                    'design_specifications' => ['heading' => 'Keep this design spec'],
                ],
            ],
        ]);

        (new BusinessCardProductOptionsSeeder)->run();

        $product->refresh();
        $config = $product->product_config;
        $textureCodes = array_map(
            fn (int $number): string => "inkpavo_j{$number}",
            range(1, 8),
        );
        $defaultGallery = [
            '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-01.png',
            '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-02.png',
            '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-03.png',
            '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-04.png',
        ];

        $this->assertSame(
            ['sizes', 'corners', 'texture', 'special_finish'],
            array_keys($config['options']),
        );
        $this->assertSame(['standard', 'square', 'custom'], data_get($config, 'options.sizes.values.*.code'));
        $this->assertSame(
            '/images/product-options/business-cards/swatches/standard-size.webp',
            data_get($config, 'options.sizes.values.0.swatch_image'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/square-size.webp',
            data_get($config, 'options.sizes.values.1.swatch_image'),
        );
        $this->assertSame(
            'max range: 2.1 - 3.5 inches',
            data_get($config, 'options.sizes.values.2.description'),
        );
        $this->assertSame(['square', 'rounded'], data_get($config, 'options.corners.values.*.code'));
        $this->assertSame($textureCodes, data_get($config, 'options.texture.values.*.code'));
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
            data_get($config, 'options.special_finish.values.*.code'),
        );
        $this->assertSame($defaultGallery, data_get($config, 'media.gallery'));
        $this->assertSame($defaultGallery[0], $product->featured_image);
        $this->assertSame($defaultGallery[0], data_get($config, 'product.featured_image'));
        $this->assertSame('Keep this FAQ', data_get($config, 'faq.0.question'));
        $this->assertSame('Keep this design spec', data_get($config, 'detail_sections.design_specifications.heading'));

        $rules = data_get($config, 'media.gallery_rules');
        $this->assertCount(18, $rules);
        $this->assertSame(
            ['sizes' => 'standard', 'texture' => 'inkpavo_j4'],
            data_get($rules, '4.match'),
        );
        $this->assertSame(
            '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j4.png',
            data_get($rules, '4.primary'),
        );
    }

    public function test_legacy_luxe_business_cards_are_normalized_to_the_same_contract(): void
    {
        $category = new ProductCategory([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);
        $contents = file_get_contents(base_path('content/product-options/business-cards/super-luxe-business-cards.json'));

        if ($contents === false) {
            $this->fail('The Super Luxe Business Cards legacy options file could not be read.');
        }

        $product = new Product([
            'name' => 'Super Luxe Business Cards',
            'slug' => 'super-luxe-business-cards',
            'product_options' => json_decode(
                $contents,
                true,
                512,
                JSON_THROW_ON_ERROR,
            ),
        ]);
        $product->setRelation('category', $category);

        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertTrue((bool) data_get($options, 'dynamic_options'));
        $this->assertSame(
            [
                '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-01.webp',
                '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-02.webp',
                '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-03.webp',
                '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-04.webp',
            ],
            data_get($options, 'galleries.0.images'),
        );
        $this->assertSame(
            ['sizes', 'corners', 'texture', 'special_finish'],
            array_column(data_get($options, 'option_groups', []), 'key'),
        );
        $this->assertSame(
            ['standard', 'square', 'custom'],
            array_column(data_get($options, 'option_groups.0.values', []), 'code'),
        );
        $this->assertSame(
            ['inkpavo_j1', 'inkpavo_j2', 'inkpavo_j3', 'inkpavo_j4', 'inkpavo_j5', 'inkpavo_j6', 'inkpavo_j7', 'inkpavo_j8'],
            array_column(data_get($options, 'option_groups.2.values', []), 'code'),
        );
        $this->assertSame(
            '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j6.webp',
            data_get($options, 'galleries.7.images.0'),
        );
    }

    public function test_super_standard_business_cards_receive_texture_options_and_corner_specific_galleries(): void
    {
        $businessCards = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);
        $product = Product::create([
            'name' => 'Super Standard Business Cards',
            'slug' => 'super-standard-business-cards',
            'product_category_id' => $businessCards->id,
            'product_config' => [
                'options' => [
                    'paper_finish' => ['values' => [['code' => 'matte']]],
                    'special_finish' => ['values' => [['code' => 'black_gold']]],
                ],
                'faq' => [['question' => 'Keep this FAQ', 'answer' => 'Yes']],
                'detail_sections' => [
                    'design_specifications' => ['heading' => 'Keep this design spec'],
                ],
            ],
        ]);

        (new BusinessCardProductOptionsSeeder)->run();

        $product->refresh();
        $config = $product->product_config;
        $textureCodes = [
            'j1_water_ripple_paper',
            'j2_cloth_texture_paper',
            'j3_eggshell_texture',
            'j4_high_grade_paper',
            'j5_pearlescent_paper',
            'j6_kraft_paper',
            'j7_absorbent_cotton_paper',
            'j8_pinhole_paper',
        ];
        $defaultGallery = [
            '/images/products/super-business-cards/super-business-cards-default-01.png',
            '/images/products/super-business-cards/super-business-cards-default-02.png',
            '/images/products/super-business-cards/super-business-cards-default-03.png',
            '/images/products/super-business-cards/super-business-cards-default-04.png',
        ];

        $this->assertSame(
            ['sizes', 'corners', 'texture', 'special_finish'],
            array_keys($config['options']),
        );
        $this->assertSame(['standard', 'square', 'custom'], data_get($config, 'options.sizes.values.*.code'));
        $this->assertSame(
            '/images/product-options/business-cards/swatches/standard-size.webp',
            data_get($config, 'options.sizes.values.0.swatch_image'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/square-size.webp',
            data_get($config, 'options.sizes.values.1.swatch_image'),
        );
        $this->assertSame(
            'max range: 2.1 - 3.5 inches',
            data_get($config, 'options.sizes.values.2.description'),
        );
        $this->assertSame(['square', 'rounded'], data_get($config, 'options.corners.values.*.code'));
        $this->assertSame($textureCodes, data_get($config, 'options.texture.values.*.code'));
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
            data_get($config, 'options.special_finish.values.*.code'),
        );
        $this->assertSame($defaultGallery, data_get($config, 'media.gallery'));
        $this->assertSame($defaultGallery[0], $product->featured_image);
        $this->assertSame('Keep this FAQ', data_get($config, 'faq.0.question'));
        $this->assertSame('Keep this design spec', data_get($config, 'detail_sections.design_specifications.heading'));

        $rules = data_get($config, 'media.gallery_rules');
        $this->assertSame(
            [
                '/images/products/super-business-cards/texture/j1-water-ripple-paper.png',
                '/images/products/super-business-cards/texture/j2-cloth-texture-paper.png',
                '/images/products/super-business-cards/texture/j3-eggshell-texture.png',
                '/images/products/super-business-cards/texture/j4-high-grade-paper.png',
                '/images/products/super-business-cards/texture/j5-pearlescent-paper.png',
                '/images/products/super-business-cards/texture/j6-kraft-paper.png',
                '/images/products/super-business-cards/texture/j7-absorbent-cotton-paper.png',
                '/images/products/super-business-cards/texture/j8-pinhole-paper.png',
            ],
            data_get($config, 'options.texture.values.*.swatch_image'),
        );
        $this->assertCount(42, $rules);
        $this->assertSame(
            ['sizes' => 'standard', 'corners' => 'rounded', 'texture' => 'j5_pearlescent_paper'],
            data_get($rules, '10.match'),
        );
        $this->assertSame(
            '/images/products/super-business-cards/super-business-cards-rounded-j5-pearlescent-paper.png',
            data_get($rules, '10.primary'),
        );

        $squareSizeRules = collect($rules)->filter(
            static fn (array $rule): bool => data_get($rule, 'match.sizes') === 'square',
        );

        $this->assertCount(16, $squareSizeRules);
        $this->assertSame(
            [
                'j1_water_ripple_paper_square_size_square' => '/images/products/super-business-cards/super-business-cards-square-j1-water-ripple-paper.png',
                'j1_water_ripple_paper_square_size_rounded' => '/images/products/super-business-cards/super-business-cards-square-rounded-j1-water-ripple-paper.png',
                'j2_cloth_texture_paper_square_size_square' => '/images/products/super-business-cards/super-business-cards-square-j2-cloth-texture-paper.png',
                'j2_cloth_texture_paper_square_size_rounded' => '/images/products/super-business-cards/super-business-cards-square-rounded-j2-cloth-texture-paper.png',
                'j3_eggshell_texture_square_size_square' => '/images/products/super-business-cards/super-business-cards-square-j3-eggshell-texture.png',
                'j3_eggshell_texture_square_size_rounded' => '/images/products/super-business-cards/super-business-cards-square-rounded-j3-eggshell-texture.png',
                'j4_high_grade_paper_square_size_square' => '/images/products/super-business-cards/super-business-cards-square-j4-high-grade-paper.png',
                'j4_high_grade_paper_square_size_rounded' => '/images/products/super-business-cards/super-business-cards-square-rounded-j4-high-grade-paper.png',
                'j5_pearlescent_paper_square_size_square' => '/images/products/super-business-cards/super-business-cards-square-j5-pearlescent-paper.png',
                'j5_pearlescent_paper_square_size_rounded' => '/images/products/super-business-cards/super-business-cards-square-rounded-j5-pearlescent-paper.png',
                'j6_kraft_paper_square_size_square' => '/images/products/super-business-cards/super-business-cards-square-j6-kraft-paper.png',
                'j6_kraft_paper_square_size_rounded' => '/images/products/super-business-cards/super-business-cards-square-rounded-j6-kraft-paper.png',
                'j7_absorbent_cotton_paper_square_size_square' => '/images/products/super-business-cards/super-business-cards-square-j7-absorbent-cotton-paper.png',
                'j7_absorbent_cotton_paper_square_size_rounded' => '/images/products/super-business-cards/super-business-cards-square-rounded-j7-absorbent-cotton-paper.png',
                'j8_pinhole_paper_square_size_square' => '/images/products/super-business-cards/super-business-cards-square-j8-pinhole-paper.png',
                'j8_pinhole_paper_square_size_rounded' => '/images/products/super-business-cards/super-business-cards-square-rounded-j8-pinhole-paper.png',
            ],
            $squareSizeRules->mapWithKeys(
                static fn (array $rule): array => [$rule['id'] => $rule['primary']],
            )->all(),
        );
    }

    public function test_legacy_super_business_cards_are_normalized_to_the_same_contract(): void
    {
        $category = new ProductCategory([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);
        $contents = file_get_contents(base_path('content/product-options/business-cards/super-standard-business-cards.json'));

        if ($contents === false) {
            $this->fail('The Super Standard Business Cards legacy options file could not be read.');
        }

        $product = new Product([
            'name' => 'Super Standard Business Cards',
            'slug' => 'super-standard-business-cards',
            'product_options' => json_decode(
                $contents,
                true,
                512,
                JSON_THROW_ON_ERROR,
            ),
        ]);
        $product->setRelation('category', $category);

        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertTrue((bool) data_get($options, 'dynamic_options'));
        $this->assertSame(
            ['sizes', 'corners', 'texture', 'special_finish'],
            array_column(data_get($options, 'option_groups', []), 'key'),
        );
        $this->assertSame(
            ['standard', 'square', 'custom'],
            array_column(data_get($options, 'option_groups.0.values', []), 'code'),
        );
        $this->assertSame(
            ['j1_water_ripple_paper', 'j2_cloth_texture_paper', 'j3_eggshell_texture', 'j4_high_grade_paper', 'j5_pearlescent_paper', 'j6_kraft_paper', 'j7_absorbent_cotton_paper', 'j8_pinhole_paper'],
            array_column(data_get($options, 'option_groups.2.values', []), 'code'),
        );
        $this->assertSame(
            '/images/products/super-business-cards/super-business-cards-rounded-j3-eggshell-texture.webp',
            data_get($options, 'galleries.7.images.0'),
        );
    }
}
