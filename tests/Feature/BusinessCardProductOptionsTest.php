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
            'name' => 'Classic Quality Business Cards',
            'slug' => 'classic-quality-business-cards',
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
            ['shattered_glass_film', 'holographic_film', 'starlight_film', 'holographic_star_film', 'soft_touch_film'],
            data_get($quality->product_config, 'options.texture.values.*.code'),
        );
        $this->assertSame(
            [
                '/images/product-options/business-cards/swatches/quality/shattered-glass-film.png',
                '/images/product-options/business-cards/swatches/quality/holographic-film.png',
                '/images/product-options/business-cards/swatches/quality/starlight-film.png',
                '/images/product-options/business-cards/swatches/quality/holographic-star-film.png',
                '/images/product-options/business-cards/swatches/quality/soft-touch-film.png',
            ],
            data_get($quality->product_config, 'options.texture.values.*.swatch_image'),
        );
        $this->assertContains(
            'cold_bright_gold',
            data_get($quality->product_config, 'options.special_finish.values.*.code'),
        );
        $this->assertSame(['/images/quality.jpg'], data_get($quality->product_config, 'media.gallery'));
        $this->assertSame('Keep this FAQ', data_get($quality->product_config, 'faq.0.question'));
        $this->assertSame('Keep this design spec', data_get($quality->product_config, 'detail_sections.design_specifications.heading'));

        $this->assertSame(
            ['matte', 'gloss', 'frosted'],
            data_get(Product::where('slug', 'basic-pvc-card')->firstOrFail()->product_config, 'options.paper_finish.values.*.code'),
        );
        $expectedPvcSwatches = [
            'basic-pvc-card' => [
                '/images/products/pvc/standard-pvc-matte.png',
                '/images/products/pvc/standard-pvc-gloss.png',
                '/images/products/pvc/standard-pvc-frosted.png',
            ],
            'standard-pvc-card' => [
                '/images/products/pvc/standard-pvc-matte.png',
                '/images/products/pvc/standard-pvc-gloss.png',
                '/images/products/pvc/standard-pvc-frosted.png',
            ],
            'premium-pvc-card' => [
                '/images/products/pvc/standard-pvc-matte.png',
                '/images/products/pvc/standard-pvc-gloss.png',
                '/images/products/pvc/standard-pvc-frosted.png',
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

            $expectedFinishImages = [
                '/images/products/pvc/standard-pvc-matte.webp',
                '/images/products/pvc/standard-pvc-gloss.webp',
                '/images/products/pvc/standard-pvc-frosted.webp',
            ];

            $this->assertSame(
                $expectedFinishImages,
                data_get($paperFinishGroup, 'values.*.swatch_image'),
            );

            $expectedGalleryPrimaries = $slug === 'standard-pvc-card'
                ? [
                    'matte_gallery' => '/images/products/pvc/standard-pvc-matte.webp',
                    'gloss_gallery' => '/images/products/pvc/standard-pvc-gloss.webp',
                    'frosted_gallery' => '/images/products/pvc/standard-pvc-frosted.webp',
                ]
                : [
                    'matte_gallery' => '/images/products/pvc/matte-pvc-main.webp',
                    'gloss_gallery' => '/images/products/pvc/gloss-pvc-main.webp',
                    'frosted_gallery' => '/images/products/pvc/frosted-pvc-main.webp',
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
                    'print_code_gallery' => '/images/products/pvc/pvc-print-code.webp',
                    'signature_stripe_gallery' => '/images/products/pvc/pvc-signature-stripe.webp',
                ]
                : [
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
            ['laser_engraving', 'color_printing', 'plating', 'nfc'],
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
                        '/images/product-options/business-cards/swatches/metal/nfc.png',
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
                '/images/products/metal/classic-metal-business-cards-04.png',
                '/images/products/metal/classic-metal-business-cards-02.png',
                '/images/products/metal/classic-metal-business-cards-03.png',
                '/images/products/metal/classic-metal-business-cards-01.png',
            ],
            'premium-metal-business-cards' => [
                '/images/products/metal/premium-metal-business-cards-02.png',
                '/images/products/metal/premium-metal-business-cards-01.png',
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

    public function test_classic_solid_keeps_database_pricing_and_imports_option_gallery_data(): void
    {
        $businessCards = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        Product::create([
            'name' => 'Classic Solid Business Cards',
            'slug' => 'classic-solid-business-cards',
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

        $product = Product::where('slug', 'classic-solid-business-cards')->firstOrFail();
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

        foreach ($slugs as $slug) {
            Product::create([
                'name' => $slug,
                'slug' => $slug,
                'product_category_id' => $cotton->id,
                'product_config' => [
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

            $this->assertSame(['corners', 'with_nfc'], array_keys($options));
            $this->assertSame(['square', 'rounded'], data_get($options, 'corners.values.*.code'));
            $this->assertSame(['no_nfc', 'with_nfc'], data_get($options, 'with_nfc.values.*.code'));
            $this->assertSame(
                '/images/product-options/business-cards/swatches/no-nfc-card.png',
                data_get($options, 'with_nfc.values.0.swatch_image'),
            );
            $this->assertSame($gallery, data_get($product->product_config, 'media.gallery'));
            $this->assertSame($gallery[0], $product->featured_image);
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
            ['corners', 'with_nfc'],
            array_column(data_get($options, 'option_groups', []), 'key'),
        );
        $this->assertSame(
            ['square', 'rounded'],
            array_column(data_get($options, 'option_groups.0.values', []), 'code'),
        );
        $this->assertSame(
            ['no_nfc', 'with_nfc'],
            array_column(data_get($options, 'option_groups.1.values', []), 'code'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/no-nfc-card.png',
            data_get($options, 'option_groups.1.values.0.swatch_image'),
        );
    }

    public function test_luxe_business_cards_receive_texture_options_and_standard_texture_galleries(): void
    {
        $businessCards = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);
        $product = Product::create([
            'name' => 'Luxe Business Cards',
            'slug' => 'luxe-business-cards',
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
            '/images/products/luxe-business-cards/luxe-business-cards-standard-01.png',
            '/images/products/luxe-business-cards/luxe-business-cards-standard-02.png',
            '/images/products/luxe-business-cards/luxe-business-cards-standard-03.png',
            '/images/products/luxe-business-cards/luxe-business-cards-standard-04.png',
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
        $contents = file_get_contents(base_path('content/product-options/business-cards/luxe-business-cards.json'));

        if ($contents === false) {
            $this->fail('The Luxe Business Cards legacy options file could not be read.');
        }

        $product = new Product([
            'name' => 'Luxe Business Cards',
            'slug' => 'luxe-business-cards',
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
            ['inkpavo_j1', 'inkpavo_j2', 'inkpavo_j3', 'inkpavo_j4', 'inkpavo_j5', 'inkpavo_j6', 'inkpavo_j7', 'inkpavo_j8'],
            array_column(data_get($options, 'option_groups.2.values', []), 'code'),
        );
        $this->assertSame(
            '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j6.webp',
            data_get($options, 'galleries.7.images.0'),
        );
    }

    public function test_super_business_cards_receive_texture_options_and_corner_specific_galleries(): void
    {
        $businessCards = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);
        $product = Product::create([
            'name' => 'Super Business Cards',
            'slug' => 'super-business-cards',
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
        $this->assertCount(26, $rules);
        $this->assertSame(
            ['sizes' => 'standard', 'corners' => 'rounded', 'texture' => 'j5_pearlescent_paper'],
            data_get($rules, '10.match'),
        );
        $this->assertSame(
            '/images/products/super-business-cards/super-business-cards-rounded-j5-pearlescent-paper.png',
            data_get($rules, '10.primary'),
        );
    }

    public function test_legacy_super_business_cards_are_normalized_to_the_same_contract(): void
    {
        $category = new ProductCategory([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);
        $contents = file_get_contents(base_path('content/product-options/business-cards/super-business-cards.json'));

        if ($contents === false) {
            $this->fail('The Super Business Cards legacy options file could not be read.');
        }

        $product = new Product([
            'name' => 'Super Business Cards',
            'slug' => 'super-business-cards',
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
