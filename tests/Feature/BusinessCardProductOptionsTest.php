<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\PricingService;
use App\Services\ProductConfigurationService;
use Database\Seeders\BusinessCardProductOptionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCardProductOptionsTest extends TestCase
{
    use RefreshDatabase;

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
            ['shattered_glass_film', 'holographic_film', 'starlight_film', 'holographic_star_film', 'soft_touch_film'],
            data_get($quality->product_config, 'options.texture.values.*.code'),
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
        $this->assertSame(
            ['no_print_code_or_signature_stripe', 'print_code', 'signature_stripe'],
            data_get(Product::where('slug', 'standard-pvc-card')->firstOrFail()->product_config, 'options.print_code_or_signature_stripe.values.*.code'),
        );
        $this->assertSame(
            ['no_print_code', 'print_code'],
            data_get(Product::where('slug', 'premium-pvc-card')->firstOrFail()->product_config, 'options.print_code.values.*.code'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
            data_get(Product::where('slug', 'basic-pvc-card')->firstOrFail()->product_config, 'options.print_code.values.0.swatch_image'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/pvc-print-code.png',
            data_get(Product::where('slug', 'basic-pvc-card')->firstOrFail()->product_config, 'options.print_code.values.1.swatch_image'),
        );

        $expectedPvcPricing = [
            'basic-pvc-card' => 0.6,
            'standard-pvc-card' => 1.6,
            'premium-pvc-card' => 10,
        ];

        foreach ($expectedPvcPricing as $slug => $basePrice) {
            $config = Product::where('slug', $slug)->firstOrFail()->product_config;

            $this->assertSame('rule_based', data_get($config, 'pricing.mode'));
            $this->assertSame($basePrice, data_get($config, 'pricing.scenarios.rectangle.base_price_per_card'));
            $this->assertSame(50, data_get($config, 'pricing.scenarios.rectangle.start_quantity'));
        }

        $pricing = app(PricingService::class);

        $this->assertSame(
            30.0,
            $pricing->calculate(Product::where('slug', 'basic-pvc-card')->firstOrFail()->id, [
                'quantity' => '50',
                'paper_finish' => 'matte',
                'print_code' => 'no_print_code',
            ]),
        );
        $this->assertSame(
            43.0,
            $pricing->calculate(Product::where('slug', 'basic-pvc-card')->firstOrFail()->id, [
                'quantity' => '50',
                'paper_finish' => 'matte',
                'print_code' => 'print_code',
            ]),
        );
        $this->assertSame(
            80.0,
            $pricing->calculate(Product::where('slug', 'standard-pvc-card')->firstOrFail()->id, [
                'quantity' => '50',
                'paper_finish' => 'matte',
                'print_code_or_signature_stripe' => 'no_print_code_or_signature_stripe',
            ]),
        );
        $this->assertSame(
            500.0,
            $pricing->calculate(Product::where('slug', 'premium-pvc-card')->firstOrFail()->id, [
                'quantity' => '50',
                'print_code' => 'no_print_code',
            ]),
        );
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

        foreach (['classic-metal-business-cards', 'premium-metal-business-cards', 'luxe-metal-business-cards'] as $slug) {
            $config = Product::where('slug', $slug)->firstOrFail()->product_config;

            foreach ($expectedMetalSwatches as $group => $swatches) {
                $this->assertSame($swatches, data_get($config, "options.{$group}.values.*.swatch_image"));
            }

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

            $expectedBasePrices = [
                'classic-metal-business-cards' => ['0_3_mm' => 4.6, '0_5_mm' => 5.4],
                'premium-metal-business-cards' => ['0_3_mm' => 5.6, '0_5_mm' => 6.4],
                'luxe-metal-business-cards' => ['0_3_mm' => 7.2, '0_5_mm' => 8],
            ];

            $this->assertSame('rule_based', data_get($config, 'pricing.mode'));
            $this->assertSame([], data_get($config, 'pricing.scenarios'));
            $this->assertSame(
                ['0_3_mm', '0_5_mm'],
                data_get($config, 'pricing.rules.*.match.thickness'),
            );
            $this->assertSame(
                [$expectedBasePrices[$slug]['0_3_mm'], $expectedBasePrices[$slug]['0_5_mm']],
                data_get($config, 'pricing.rules.*.pricing.basePrice'),
            );
            $this->assertSame(
                [
                    ['print_code_or_magnetic_stripe', 'special_finish'],
                    ['print_code_or_magnetic_stripe', 'special_finish'],
                ],
                array_map(
                    fn (array $rule): array => data_get($rule, 'pricing.processes.*.code'),
                    data_get($config, 'pricing.rules', []),
                ),
            );
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

        $pricing = app(PricingService::class);
        $classic = Product::where('slug', 'classic-metal-business-cards')->firstOrFail();
        $premium = Product::where('slug', 'premium-metal-business-cards')->firstOrFail();

        $this->assertSame(
            230.0,
            $pricing->calculate($classic->id, [
                'quantity' => '50',
                'thickness' => '0_3_mm',
                'sizes' => '89x51_mm',
                'print_code_or_magnetic_stripe' => 'no_print_code_or_magnetic_stripe',
            ]),
        );
        $this->assertSame(
            280.0,
            $pricing->calculate($classic->id, [
                'quantity' => '50',
                'thickness' => '0_3_mm',
                'sizes' => '89x51_mm',
                'print_code_or_magnetic_stripe' => 'print_code',
            ]),
        );
        $this->assertSame(
            470.0,
            $pricing->calculate($premium->id, [
                'quantity' => '50',
                'thickness' => '0_3_mm',
                'sizes' => '89x51_mm',
                'print_code_or_magnetic_stripe' => 'print_code',
                'special_finish' => 'laser_engraving',
            ]),
        );
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
        $this->assertCount(9, $rules);
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
        $this->assertCount(17, $rules);
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
