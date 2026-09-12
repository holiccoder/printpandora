<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductConfigurationService;
use App\Support\ProductImagePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductConfigurationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_storefront_images_use_originals_until_webp_derivatives_are_ready(): void
    {
        Storage::fake('public');

        $sourcePath = ProductImagePolicy::ORIGINALS_DIRECTORY.'/product-galleries/legacy.png';
        $webpPath = 'product-galleries/legacy.webp';
        $product = new Product([
            'name' => 'Legacy image product',
            'slug' => 'legacy-image-product',
            'product_options' => [
                'finish' => [[
                    'name' => 'Matte',
                    'code' => 'matte',
                    'swatch_image' => $sourcePath,
                ]],
                'galleries' => [[
                    'id' => 'default',
                    'images' => [$sourcePath, 'https://cdn.example.com/legacy.jpg'],
                ]],
            ],
        ]);
        $product->setRelation('category', new ProductCategory(['slug' => 'other']));

        Storage::disk('public')->put($sourcePath, 'original');

        $service = app(ProductConfigurationService::class);
        $processing = $service->storefrontOptions($product);

        $this->assertSame('/storage/'.$sourcePath, data_get($processing, 'finish.0.swatch_image'));
        $this->assertSame('/storage/'.$sourcePath, data_get($processing, 'galleries.0.images.0'));
        $this->assertSame(
            'https://cdn.example.com/legacy.jpg',
            data_get($processing, 'galleries.0.images.1'),
        );

        Storage::disk('public')->put($webpPath, 'webp');

        $ready = $service->storefrontOptions($product);

        $this->assertSame('/storage/'.$webpPath, data_get($ready, 'finish.0.swatch_image'));
        $this->assertSame('/storage/'.$webpPath, data_get($ready, 'galleries.0.images.0'));
    }

    public function test_product_json_form_state_contains_only_options_and_gallery_data(): void
    {
        $product = new Product([
            'name' => 'Classic Standard Business Cards',
            'slug' => 'classic-standard-business-cards',
            'description' => '<p>Product description</p>',
        ]);
        $product->setRelation('category', new ProductCategory(['slug' => 'business-cards']));

        $state = app(ProductConfigurationService::class)->formState($product);

        $this->assertSame('Classic Standard Business Cards', data_get($state, 'product.name'));
        $this->assertNull(data_get($state, 'product.subtitle'));
        $this->assertSame('fixed_tiers', data_get($state, 'pricing.mode'));
        $this->assertSame([], data_get($state, 'pricing.scenarios'));
        $this->assertSame([], data_get($state, 'pricing.quantity_price_table'));
        $this->assertSame([], data_get($state, 'pricing.rules'));
        $this->assertCount(7, $state['options']);
        $this->assertCount(24, $state['media']['gallery_rules']);
        $this->assertSame([], $state['faq']);
        $this->assertArrayNotHasKey('detail_sections', $state);
    }

    public function test_canonical_data_is_adapted_for_the_existing_storefront(): void
    {
        $product = new Product([
            'name' => 'Database Only Business Card',
            'slug' => 'database-only-business-card',
            'product_config' => [
                'schema_version' => 1,
                'product' => [
                    'name' => 'Classic Standard Business Cards',
                    'subtitle' => 'Short description',
                ],
                'options' => [
                    'sizes' => [
                        'values' => [
                            ['code' => 'standard', 'label' => 'Standard'],
                        ],
                    ],
                ],
                'media' => [
                    'gallery_rules' => [
                        [
                            'id' => 'default',
                            'match' => [],
                            'images' => ['/images/products/example.jpg'],
                        ],
                    ],
                ],
                'pricing' => [
                    'mode' => 'rule_based',
                    'scenarios' => [
                        'rectangle' => [
                            'base_price_per_card' => 0.065,
                            'start_quantity' => 200,
                            'quantity_discounts_percent' => ['200' => 0],
                            'processes' => [],
                        ],
                    ],
                ],
                'faq' => [],
                'detail_sections' => ['design_specifications' => ['heading' => 'Keep me']],
            ],
        ]);
        $product->setRelation('category', new ProductCategory(['slug' => 'business-cards']));

        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertIsArray($options);
        $this->assertTrue(data_get($options, 'dynamic_options'));
        $this->assertSame('sizes', data_get($options, 'option_groups.0.key'));
        $this->assertSame('Standard', data_get($options, 'option_groups.0.values.0.name'));
        $this->assertSame('Standard', data_get($options, 'sizes.0.name'));
        $this->assertSame('/images/products/example.jpg', data_get($options, 'galleries.0.images.0'));
        $this->assertSame(0.065, data_get($options, 'pricing_data.rectangle.basePrice'));
        $this->assertSame('Keep me', data_get($options, 'detail_sections.design_specifications.heading'));
    }

    public function test_product_json_only_supplies_detail_options_and_galleries(): void
    {
        $product = new Product([
            'name' => 'Database Product Name',
            'slug' => 'classic-special-business-cards',
            'subtitle' => '<p>Database subtitle</p>',
            'price_line' => 'Database price line',
            'description' => '<p>Database description</p>',
            'product_config' => [
                'product' => [
                    'subtitle' => '<p>Stale file subtitle</p>',
                ],
                'options' => [
                    'sizes' => [
                        'values' => [
                            ['code' => 'database-size', 'label' => 'Database size'],
                        ],
                    ],
                ],
                'media' => [
                    'gallery' => ['/images/database-gallery.jpg'],
                ],
                'pricing' => [
                    'mode' => 'fixed_tiers',
                    'quantity_price_table' => [[
                        'quantity' => '50',
                        'price_per_card' => '0.42',
                        'pack_price' => '21',
                        'pack_original_price' => '',
                        'is_recommended' => true,
                    ]],
                ],
                'faq' => [[
                    'question' => 'Database FAQ',
                    'answer' => 'Database answer',
                ]],
                'detail_sections' => [
                    'design_service_banner' => ['heading' => 'Database detail'],
                ],
            ],
        ]);
        $product->setRelation('category', new ProductCategory(['slug' => 'classic-business-cards']));

        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertSame('<p>Database subtitle</p>', data_get($options, 'subtitle'));
        $this->assertSame('Database price line', data_get($options, 'starting_price_text'));
        $this->assertNotSame(
            'database-size',
            data_get($options, 'option_groups.0.values.0.code'),
        );
        $this->assertNotSame(
            '/images/database-gallery.jpg',
            data_get($options, 'galleries.0.images.0'),
        );
        $this->assertSame('50', data_get($options, 'quantity_price_table.0.quantity'));
        $this->assertSame('Database FAQ', data_get($options, 'detail_sections.faq.items.0.question'));
        $this->assertSame(
            'Database detail',
            data_get($options, 'detail_sections.design_service_banner.heading'),
        );
    }

    public function test_database_detail_sections_keep_product_specific_data_except_shared_cross_sell_sections(): void
    {
        $product = new Product([
            'name' => 'Configured business card',
            'slug' => 'configured-business-card',
            'product_config' => [
                'detail_sections' => [
                    'design_specifications' => [
                        'heading' => 'Product-specific specifications',
                    ],
                    'design_service_banner' => [
                        'heading' => 'Stale banner copy',
                    ],
                    'paper_stocks' => [
                        'heading' => 'Stale paper copy',
                        'items' => [],
                    ],
                    'more_good_stuff' => [
                        'heading' => 'Stale cross-sell content',
                    ],
                    'faq' => [
                        'heading' => 'Keep this FAQ content',
                    ],
                ],
            ],
        ]);
        $product->setRelation('category', new ProductCategory(['slug' => 'business-cards']));

        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertSame(
            'Product-specific specifications',
            data_get($options, 'detail_sections.design_specifications.heading'),
        );
        $this->assertSame(
            'Need help designing your Business Cards?',
            data_get($options, 'detail_sections.design_service_banner.heading'),
        );
        $this->assertSame(
            '/images/product-detail/business-design-banner.webp',
            data_get($options, 'detail_sections.design_service_banner.image_url'),
        );
        $this->assertSame(
            'Check out our other paper stocks',
            data_get($options, 'detail_sections.paper_stocks.heading'),
        );
        $this->assertCount(4, data_get($options, 'detail_sections.paper_stocks.items'));
        $this->assertSame(
            'Even more good stuff',
            data_get($options, 'detail_sections.more_good_stuff.heading'),
        );
        $this->assertSame('Keep this FAQ content', data_get($options, 'detail_sections.faq.heading'));
    }

    public function test_business_card_cross_sell_sections_always_use_shared_content_for_pvc_products(): void
    {
        $product = new Product([
            'name' => 'Basic PVC Card',
            'slug' => 'basic-pvc-card',
            'product_config' => [
                'detail_sections' => [
                    'design_specifications' => [
                        'heading' => 'Keep PVC specifications',
                    ],
                    'design_service_banner' => [
                        'image_url' => '/images/stale-banner.png',
                    ],
                    'paper_stocks' => [
                        'items' => [
                            ['image_url' => '/images/stale-paper.png'],
                        ],
                    ],
                    'more_good_stuff' => [
                        'items' => [
                            ['image_url' => '/images/stale-cross-sell.png'],
                        ],
                    ],
                    'faq' => [
                        'heading' => 'Keep PVC FAQ',
                    ],
                ],
            ],
        ]);
        $businessCards = new ProductCategory([
            'id' => 1,
            'slug' => 'business-cards',
        ]);
        $pvcCards = new ProductCategory([
            'slug' => 'pvc-business-cards',
            'parent_id' => 1,
        ]);
        $pvcCards->setRelation('parent', $businessCards);
        $product->setRelation('category', $pvcCards);

        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertSame(
            '/images/product-detail/business-design-banner.webp',
            data_get($options, 'detail_sections.design_service_banner.image_url'),
        );
        $this->assertSame(
            '/images/product-detail/paper-stocks/premium-paper.webp',
            data_get($options, 'detail_sections.paper_stocks.items.0.image_url'),
        );
        $this->assertSame(
            '/images/product-detail/even-more/classic-metal-business-cards.webp',
            data_get($options, 'detail_sections.more_good_stuff.items.0.image_url'),
        );
        $this->assertSame(
            'Metal Business cards',
            data_get($options, 'detail_sections.more_good_stuff.items.0.name'),
        );
        $this->assertSame(
            'Metal Business cards →',
            data_get($options, 'detail_sections.more_good_stuff.items.0.link_label'),
        );
        $this->assertSame(
            '/business-cards/classic-metal',
            data_get($options, 'detail_sections.more_good_stuff.items.0.href'),
        );
        $this->assertSame(
            ['Stickers and Labels', 'Postcards', 'Flyers and Brochures'],
            array_slice(data_get($options, 'detail_sections.more_good_stuff.items.*.name'), 1),
        );
        $this->assertSame(
            ['/stickers-and-labels', '/postcards', '/flyers-and-brochures'],
            array_slice(data_get($options, 'detail_sections.more_good_stuff.items.*.href'), 1),
        );
        $this->assertSame(
            ['Stickers and Labels →', 'Postcards →', 'Flyers and Brochures →'],
            array_slice(data_get($options, 'detail_sections.more_good_stuff.items.*.link_label'), 1),
        );
        $this->assertSame(
            'Keep PVC specifications',
            data_get($options, 'detail_sections.design_specifications.heading'),
        );
        $this->assertSame(
            'Keep PVC FAQ',
            data_get($options, 'detail_sections.faq.heading'),
        );
    }

    public function test_basic_pvc_design_guideline_downloads_match_shared_business_card_content(): void
    {
        $product = new Product([
            'name' => 'Basic PVC Card',
            'slug' => 'basic-pvc-card',
            'product_config' => [
                'detail_sections' => [
                    'design_specifications' => [
                        'heading' => 'Design Specifications',
                        'downloads' => [],
                    ],
                ],
            ],
        ]);
        $businessCards = new ProductCategory([
            'id' => 1,
            'slug' => 'business-cards',
        ]);
        $pvcCards = new ProductCategory([
            'slug' => 'pvc-business-cards',
            'parent_id' => 1,
        ]);
        $pvcCards->setRelation('parent', $businessCards);
        $product->setRelation('category', $pvcCards);

        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertSame(
            ['pdf', 'illustrator', 'indesign', 'jpeg'],
            data_get($options, 'detail_sections.design_specifications.downloads.*.id'),
        );
    }

    public function test_database_legacy_product_data_gets_shared_cross_sell_sections(): void
    {
        $product = new Product([
            'name' => 'Basic PVC Card',
            'slug' => 'basic-pvc-card',
            'product_options' => [
                'detail_sections' => [
                    'design_specifications' => [
                        'heading' => 'PVC Design Specifications',
                        'diagram' => [
                            'safe_area' => [
                                'dimensions' => '3.38" x 1.88"',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $businessCards = new ProductCategory(['slug' => 'business-cards']);
        $pvcCards = new ProductCategory([
            'slug' => 'pvc-business-cards',
            'parent_id' => 1,
        ]);
        $pvcCards->setRelation('parent', $businessCards);
        $product->setRelation('category', $pvcCards);

        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertSame(
            'PVC Design Specifications',
            data_get($options, 'detail_sections.design_specifications.heading'),
        );
        $this->assertSame(
            '3.38" x 1.88"',
            data_get($options, 'detail_sections.design_specifications.diagram.safe_area.dimensions'),
        );
        $this->assertSame(
            'Need help designing your Business Cards?',
            data_get($options, 'detail_sections.design_service_banner.heading'),
        );
        $this->assertSame(
            'Check out our other paper stocks',
            data_get($options, 'detail_sections.paper_stocks.heading'),
        );
        $this->assertSame(
            'Even more good stuff',
            data_get($options, 'detail_sections.more_good_stuff.heading'),
        );
    }

    public function test_direct_business_card_category_receives_shared_product_sections(): void
    {
        $product = new Product([
            'name' => 'Test Product 2',
            'slug' => 'test-product-2',
            'product_config' => [
                'detail_sections' => [],
            ],
        ]);
        $product->setRelation('category', new ProductCategory(['slug' => 'business-cards']));

        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertSame(
            'Need help designing your Business Cards?',
            data_get($options, 'detail_sections.design_service_banner.heading'),
        );
        $this->assertSame(
            'Check out our other paper stocks',
            data_get($options, 'detail_sections.paper_stocks.heading'),
        );
        $this->assertSame(
            'Even more good stuff',
            data_get($options, 'detail_sections.more_good_stuff.heading'),
        );
    }

    public function test_review_modal_gang_run_feature_is_limited_to_classic_and_pvc_products(): void
    {
        $businessCards = new ProductCategory(['slug' => 'business-cards']);
        $pvcCards = new ProductCategory(['slug' => 'pvc-business-cards']);
        $cases = [
            'classic-standard-business-cards' => [$businessCards, true],
            'classic-special-business-cards' => [$businessCards, true],
            'basic-pvc-card' => [$pvcCards, true],
            'standard-pvc-card' => [$pvcCards, true],
            'premium-pvc-card' => [$pvcCards, true],
            'custom-pvc-card' => [$pvcCards, true],
            'basic-cotton-business-card' => [$businessCards, false],
            'classic-quality-business-cards' => [$businessCards, false],
            'classic-solid-business-cards' => [$businessCards, false],
            'super-business-cards' => [$businessCards, false],
            'classic-metal-business-cards' => [$businessCards, false],
        ];

        foreach ($cases as $slug => [$category, $expected]) {
            $product = new Product([
                'name' => $slug,
                'slug' => $slug,
                'product_config' => ['detail_sections' => []],
            ]);
            $product->setRelation('category', $category);

            $options = app(ProductConfigurationService::class)->storefrontOptions($product);

            $this->assertIsArray($options, $slug);
            $this->assertSame($expected, data_get($options, 'show_gang_run_printing'), $slug);
        }
    }

    public function test_non_business_card_categories_do_not_receive_shared_sections(): void
    {
        $product = new Product([
            'name' => 'Generic product',
            'slug' => 'generic-product',
            'product_config' => [
                'detail_sections' => [
                    'design_specifications' => [
                        'heading' => 'Generic specifications',
                    ],
                ],
            ],
        ]);
        $product->setRelation('category', new ProductCategory(['slug' => 'flyers-brochures']));

        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertArrayNotHasKey('design_service_banner', data_get($options, 'detail_sections', []));
        $this->assertArrayNotHasKey('paper_stocks', data_get($options, 'detail_sections', []));
    }

    public function test_default_gallery_is_exposed_alongside_conditional_galleries(): void
    {
        $product = new Product([
            'name' => 'Configured product',
            'slug' => 'configured-product',
            'product_config' => [
                'options' => [],
                'media' => [
                    'gallery' => [
                        'product-galleries/default-front.jpg',
                        'product-galleries/default-back.jpg',
                    ],
                    'gallery_rules' => [
                        [
                            'id' => 'special-rule',
                            'match' => ['finish' => 'special'],
                            'primary' => 'product-galleries/special.jpg',
                            'images' => ['product-galleries/special.jpg'],
                        ],
                    ],
                ],
            ],
        ]);
        $product->setRelation('category', new ProductCategory(['slug' => 'business-cards']));

        $options = app(ProductConfigurationService::class)->storefrontOptions($product);

        $this->assertSame('default', data_get($options, 'galleries.0.id'));
        $this->assertTrue(data_get($options, 'galleries.0.is_default'));
        $this->assertSame(
            '/storage/product-galleries/default-front.jpg',
            data_get($options, 'galleries.0.images.0'),
        );
        $this->assertSame('special-rule', data_get($options, 'galleries.1.id'));
    }

    public function test_saving_form_state_keeps_detail_sections_and_syncs_product_projection(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        $product = Product::create([
            'name' => 'Original name',
            'slug' => 'classic-standard-business-cards',
            'description' => '<p>Original description</p>',
            'product_category_id' => $category->id,
            'is_active' => true,
            'product_config' => [
                'schema_version' => 1,
                'product' => ['name' => 'Original name'],
                'options' => [],
                'media' => [],
                'pricing' => ['mode' => 'fixed_tiers'],
                'faq' => [],
                'detail_sections' => [
                    'design_specifications' => ['heading' => 'Preserve this section'],
                ],
            ],
        ]);

        $service = app(ProductConfigurationService::class);
        $state = $service->formState($product);
        $state['product']['name'] = 'Updated name';
        $state['product']['subtitle'] = 'Updated subtitle';

        $saved = $service->save($product, $state);

        $this->assertSame('Updated name', $saved->name);
        $this->assertSame('Updated subtitle', $saved->subtitle);
        $this->assertSame('Updated name', data_get($saved->product_config, 'product.name'));
        $this->assertSame(
            'Preserve this section',
            data_get($saved->product_config, 'detail_sections.design_specifications.heading'),
        );
    }

    public function test_main_product_form_state_converts_legacy_pricing_to_condition_json_rules(): void
    {
        $product = new Product([
            'name' => 'Classic Standard Business Cards',
            'slug' => 'classic-standard-business-cards',
            'product_config' => [
                'product' => [
                    'name' => 'Classic Standard Business Cards',
                ],
                'options' => [
                    'sizes' => [
                        'values' => [
                            ['code' => 'standard', 'label' => 'Standard'],
                            ['code' => 'square', 'label' => 'Square'],
                        ],
                    ],
                ],
                'pricing' => [
                    'mode' => 'rule_based',
                    'scenarios' => [
                        'rectangle' => [
                            'package_name' => '300g coating + UV',
                            'base_price_per_card' => 0.14,
                            'start_quantity' => 200,
                            'quantity_discounts_percent' => ['200' => 0],
                            'processes' => [],
                        ],
                    ],
                ],
            ],
        ]);
        $product->setRelation('category', new ProductCategory(['slug' => 'business-cards']));

        $state = app(ProductConfigurationService::class)->resourceFormState($product);

        $this->assertCount(1, data_get($state, 'product_config.options'));
        $this->assertCount(1, data_get($state, 'product_config.pricing.rules'));
        $this->assertSame(
            'standard',
            data_get($state, 'product_config.pricing.rules.0.match_conditions.0.value'),
        );
        $this->assertSame(
            '300g coating + UV',
            data_get(json_decode(data_get($state, 'product_config.pricing.rules.0.pricing_json'), true), 'packageName'),
        );
    }

    public function test_main_product_form_saves_dynamic_options_gallery_rules_and_pricing_json(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        $product = Product::create([
            'name' => 'Original product',
            'slug' => 'original-product',
            'product_category_id' => $category->id,
            'is_active' => true,
            'product_config' => [
                'detail_sections' => ['keep' => true],
            ],
        ]);

        $saved = app(ProductConfigurationService::class)->saveResource($product, [
            'name' => 'Updated product',
            'slug' => 'updated-product',
            'subtitle' => '<p>Short description</p>',
            'meta_description' => 'Meta description',
            'product_config' => [
                'options' => [
                    [
                        'key' => 'paper_finish',
                        'label' => 'Paper Finish',
                        'type' => 'select',
                        'values' => [
                            [
                                'label' => 'Matte',
                                'code' => 'matte',
                                'description' => 'Smooth finish',
                            ],
                        ],
                    ],
                ],
                'media' => [
                    'gallery' => ['product-galleries/default.jpg'],
                    'gallery_rules' => [
                        [
                            'id' => 'matte-rule',
                            'match_conditions' => [
                                ['option' => 'paper_finish', 'value' => 'matte'],
                            ],
                            'primary' => 'product-galleries/matte.jpg',
                        ],
                    ],
                ],
                'pricing' => [
                    'rules' => [
                        [
                            'id' => 'matte-price',
                            'match_conditions' => [
                                ['option' => 'paper_finish', 'value' => 'matte'],
                            ],
                            'pricing_json' => json_encode([
                                'packageName' => 'Matte pricing',
                                'basePrice' => 0.14,
                                'startQuantity' => 200,
                                'paperRates' => ['200' => 0, '500' => 20],
                                'processes' => [],
                            ], JSON_THROW_ON_ERROR),
                        ],
                    ],
                ],
                'faq' => [
                    ['question' => 'Question?', 'answer' => 'Answer?'],
                ],
            ],
        ]);

        $this->assertSame('Updated product', $saved->name);
        $this->assertSame('updated-product', $saved->slug);
        $this->assertSame('Meta description', $saved->meta_description);
        $this->assertSame('matte', data_get($saved->product_config, 'options.paper_finish.default'));
        $this->assertSame('product-galleries/matte.jpg', data_get($saved->product_config, 'media.gallery_rules.0.primary'));
        $this->assertSame('Matte pricing', data_get($saved->product_config, 'pricing.rules.0.pricing.packageName'));
        $this->assertTrue(data_get($saved->product_config, 'detail_sections.keep'));
    }
}
