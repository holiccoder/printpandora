<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCardProductUpdatesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_updates_super_copy_and_default_gallery_primary(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        Product::create([
            'name' => 'Super Business Cards',
            'slug' => 'super-business-cards',
            'product_category_id' => $category->id,
            'description' => 'Old description',
            'featured_image' => '/images/old-super.png',
            'product_config' => [
                'product' => [
                    'subtitle' => 'Keep this subtitle',
                    'description' => 'Old description',
                    'featured_image' => '/images/old-super.png',
                ],
                'media' => [
                    'gallery' => [
                        '/images/old-super.png',
                        '/images/keep-this-gallery-image.png',
                    ],
                    'gallery_rules' => [
                        [
                            'id' => 'default',
                            'match' => [],
                            'images' => [
                                '/images/old-super.png',
                                '/images/keep-this-rule-image.png',
                            ],
                            'primary' => '/images/old-super.png',
                        ],
                        [
                            'id' => 'keep-this-rule',
                            'match' => ['texture' => 'keep'],
                            'images' => ['/images/keep-this-specific-image.png'],
                            'primary' => '/images/keep-this-specific-image.png',
                        ],
                    ],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_10_000001_update_super_business_card_description_and_classic_standard_sizes.php',
        );
        $migration->up();
        $migration->up();

        $product = Product::where('slug', 'super-business-cards')->firstOrFail();
        $expectedDescription = '<p>Made from approximately 130 lb uncoated cover stock, each sheet is about 16pt thick. With no coating on either side, this stock produces softer, more understated colors in print.</p><p><strong>Available finishing options:</strong></p><ul><li>Square or rounded corners</li><li>Hot foil stamping or custom die-cut shapes <em>(4–5 business days)</em></li></ul><p><em>*For hot foil stamping, two-sided finishing is charged at twice the one-sided price.</em></p>';
        $defaultImage = '/images/products/super-business-cards/super-business-cards-default-01.png';

        $this->assertSame($expectedDescription, $product->description);
        $this->assertSame('Keep this subtitle', data_get($product->product_config, 'product.subtitle'));
        $this->assertSame($expectedDescription, data_get($product->product_config, 'product.description'));
        $this->assertSame($defaultImage, $product->featured_image);
        $this->assertSame($defaultImage, data_get($product->product_config, 'product.featured_image'));
        $this->assertSame(
            [$defaultImage, '/images/keep-this-gallery-image.png'],
            data_get($product->product_config, 'media.gallery'),
        );
        $this->assertSame(
            [$defaultImage, '/images/keep-this-rule-image.png'],
            data_get($product->product_config, 'media.gallery_rules.0.images'),
        );
        $this->assertSame($defaultImage, data_get($product->product_config, 'media.gallery_rules.0.primary'));
        $this->assertSame(
            '/images/keep-this-specific-image.png',
            data_get($product->product_config, 'media.gallery_rules.1.primary'),
        );
    }

    public function test_migration_adds_classic_standard_custom_size_and_exact_descriptions(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        Product::create([
            'name' => 'Classic Standard Business Cards',
            'slug' => 'classic-standard-business-cards',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'sizes' => [
                        'label' => 'Size',
                        'type' => 'select',
                        'required' => true,
                        'default' => 'standard',
                        'values' => [
                            [
                                'code' => 'standard',
                                'label' => 'Standard',
                                'width' => '2.0',
                                'height' => '3.5',
                                'description' => 'Old standard description',
                                'swatch_image' => '/images/old-standard.png',
                            ],
                            [
                                'code' => 'square',
                                'label' => 'Square',
                                'width' => '2.16',
                                'height' => '3.3',
                                'description' => 'Old square description',
                                'swatch_image' => '/images/old-square.png',
                            ],
                        ],
                    ],
                ],
                'detail_sections' => [
                    'design_specifications' => ['heading' => 'Keep this section'],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_10_000001_update_super_business_card_description_and_classic_standard_sizes.php',
        );
        $migration->up();
        $migration->up();

        $product = Product::where('slug', 'classic-standard-business-cards')->firstOrFail();
        $sizes = data_get($product->product_config, 'options.sizes.values');

        $this->assertSame(['standard', 'square', 'custom'], array_column($sizes, 'code'));
        $this->assertSame('2.0 x 3.5 inches', data_get($sizes, '0.description'));
        $this->assertSame('2.5 x 2.5 inches', data_get($sizes, '1.description'));
        $this->assertSame('2.1 - 3.5 inches', data_get($sizes, '2.description'));
        $this->assertSame(
            '/images/product-options/business-cards/swatches/standard-size.webp',
            data_get($sizes, '0.swatch_image'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/square-size.webp',
            data_get($sizes, '1.swatch_image'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/custom-size.webp',
            data_get($sizes, '2.swatch_image'),
        );
        $this->assertSame('2.16', data_get($sizes, '1.width'));
        $this->assertSame('3.3', data_get($sizes, '1.height'));
        $this->assertSame(
            'Keep this section',
            data_get($product->product_config, 'detail_sections.design_specifications.heading'),
        );
    }

    public function test_migration_removes_classic_standard_print_code_and_drilling_options(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        Product::create([
            'name' => 'Classic Standard Business Cards',
            'slug' => 'classic-standard-business-cards',
            'product_category_id' => $category->id,
            'product_options' => [
                'sizes' => [
                    ['code' => 'standard'],
                ],
                'print_code' => [
                    ['code' => 'no_print_code'],
                ],
                'drill' => [
                    ['code' => 'no_drilling'],
                ],
            ],
            'product_config' => [
                'options' => [
                    'sizes' => [
                        'values' => [
                            ['code' => 'standard'],
                        ],
                    ],
                    'print_code' => [
                        'default' => 'no_print_code',
                        'values' => [['code' => 'no_print_code']],
                    ],
                    'drill' => [
                        'default' => 'no_drilling',
                        'values' => [['code' => 'no_drilling']],
                    ],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_14_000002_remove_classic_standard_print_code_and_drilling_options.php',
        );
        $migration->up();
        $migration->up();

        $product = Product::where('slug', 'classic-standard-business-cards')->firstOrFail();

        $this->assertArrayNotHasKey('print_code', $product->product_config['options']);
        $this->assertArrayNotHasKey('drill', $product->product_config['options']);
        $this->assertSame(
            ['standard'],
            data_get($product->product_config, 'options.sizes.values.*.code'),
        );
        $this->assertArrayNotHasKey('print_code', $product->product_options);
        $this->assertArrayNotHasKey('drill', $product->product_options);
        $this->assertSame(
            ['standard'],
            data_get($product->product_options, 'sizes.*.code'),
        );
    }

    public function test_migration_adds_basic_pvc_design_guideline_downloads_without_replacing_existing_specs(): void
    {
        $category = ProductCategory::create([
            'name' => 'PVC Business Cards',
            'slug' => 'pvc-business-cards',
        ]);

        Product::create([
            'name' => 'Basic PVC Card',
            'slug' => 'basic-pvc-card',
            'product_category_id' => $category->id,
            'product_config' => [
                'detail_sections' => [
                    'design_specifications' => [
                        'heading' => 'Keep this heading',
                        'diagram' => [
                            'trim' => [
                                'dimensions' => 'Keep this diagram',
                            ],
                        ],
                        'downloads' => [],
                    ],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_10_000002_add_basic_pvc_design_guideline_downloads.php',
        );
        $migration->up();
        $migration->up();

        $product = Product::where('slug', 'basic-pvc-card')->firstOrFail();

        $this->assertSame(
            ['pdf', 'illustrator', 'indesign', 'jpeg'],
            array_column(data_get($product->product_config, 'detail_sections.design_specifications.downloads'), 'id'),
        );
        $this->assertSame(
            'Keep this heading',
            data_get($product->product_config, 'detail_sections.design_specifications.heading'),
        );
        $this->assertSame(
            'Keep this diagram',
            data_get($product->product_config, 'detail_sections.design_specifications.diagram.trim.dimensions'),
        );
    }

    public function test_migration_uses_standard_pvc_finish_swatches_for_basic_and_premium(): void
    {
        $category = ProductCategory::create([
            'name' => 'PVC Business Cards',
            'slug' => 'pvc-business-cards',
        ]);

        Product::create([
            'name' => 'Basic PVC Card',
            'slug' => 'basic-pvc-card',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'paper_finish' => [
                        'values' => [
                            ['code' => 'matte', 'swatch_image' => '/images/old-matte.png'],
                            ['code' => 'gloss', 'swatch_image' => '/images/old-gloss.png'],
                            ['code' => 'frosted', 'swatch_image' => '/images/old-frosted.png'],
                        ],
                    ],
                ],
            ],
        ]);
        Product::create([
            'name' => 'Premium PVC Card',
            'slug' => 'premium-pvc-card',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'print_code' => [
                        'values' => [['code' => 'print_code']],
                    ],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_10_000003_use_standard_pvc_finish_swatches_for_basic_and_premium.php',
        );
        $migration->up();
        $migration->up();

        $expectedSwatches = [
            '/images/products/pvc/standard-pvc-matte.png',
            '/images/products/pvc/standard-pvc-gloss.png',
            '/images/products/pvc/standard-pvc-frosted.png',
        ];

        foreach (['basic-pvc-card', 'premium-pvc-card'] as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();

            $this->assertSame(
                $expectedSwatches,
                data_get($product->product_config, 'options.paper_finish.values.*.swatch_image'),
            );
        }

        $this->assertSame(
            ['print_code'],
            data_get(
                Product::where('slug', 'premium-pvc-card')->firstOrFail()->product_config,
                'options.print_code.values.*.code',
            ),
        );
    }

    public function test_migration_updates_pvc_print_code_and_signature_stripe_images(): void
    {
        $category = ProductCategory::create([
            'name' => 'PVC Business Cards',
            'slug' => 'pvc-business-cards',
        ]);

        Product::create([
            'name' => 'Basic PVC Card',
            'slug' => 'basic-pvc-card',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'print_code' => [
                        'values' => [
                            ['code' => 'no_print_code', 'swatch_image' => '/images/old-none.png'],
                            ['code' => 'print_code', 'swatch_image' => '/images/old-print.png'],
                        ],
                    ],
                ],
                'media' => [
                    'gallery_rules' => [
                        [
                            'id' => 'default',
                            'match' => [],
                            'images' => ['/images/default.png'],
                            'primary' => '/images/default.png',
                        ],
                        [
                            'id' => 'matte_gallery',
                            'match' => ['paper_finish' => 'matte'],
                            'images' => ['/images/matte.png'],
                            'primary' => '/images/matte.png',
                        ],
                    ],
                ],
            ],
        ]);
        Product::create([
            'name' => 'Standard PVC Card',
            'slug' => 'standard-pvc-card',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'print_code_or_signature_stripe' => [
                        'values' => [
                            ['code' => 'no_print_code_or_signature_stripe'],
                            ['code' => 'print_code', 'swatch_image' => '/images/old-print.png'],
                            ['code' => 'signature_stripe', 'swatch_image' => '/images/old-signature.png'],
                        ],
                    ],
                ],
                'media' => [
                    'gallery_rules' => [
                        [
                            'id' => 'default',
                            'match' => [],
                            'images' => ['/images/default.png'],
                            'primary' => '/images/default.png',
                        ],
                        [
                            'id' => 'old_signature_gallery',
                            'match' => ['print_code_or_signature_stripe' => 'signature_stripe'],
                            'images' => ['/images/old-signature.png'],
                            'primary' => '/images/old-signature.png',
                        ],
                    ],
                ],
            ],
        ]);
        Product::create([
            'name' => 'Premium PVC Card',
            'slug' => 'premium-pvc-card',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'print_code' => [
                        'values' => [['code' => 'print_code']],
                    ],
                ],
                'media' => [
                    'gallery_rules' => [
                        [
                            'id' => 'default',
                            'match' => [],
                            'images' => ['/images/default.png'],
                            'primary' => '/images/default.png',
                        ],
                    ],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_10_000004_update_pvc_print_code_and_signature_images.php',
        );
        $migration->up();
        $migration->up();

        $expected = [
            'basic-pvc-card' => [
                'option_path' => 'options.print_code.values.*.swatch_image',
                'swatches' => [
                    '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
                    '/images/products/pvc/pvc-print-code.png',
                ],
                'gallery_ids' => ['print_code_gallery'],
            ],
            'standard-pvc-card' => [
                'option_path' => 'options.print_code_or_signature_stripe.values.*.swatch_image',
                'swatches' => [
                    '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
                    '/images/products/pvc/pvc-print-code.png',
                    '/images/products/pvc/pvc-signature-stripe.png',
                ],
                'gallery_ids' => ['print_code_gallery', 'signature_stripe_gallery'],
            ],
            'premium-pvc-card' => [
                'option_path' => 'options.print_code.values.*.swatch_image',
                'swatches' => [
                    '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
                    '/images/products/pvc/pvc-print-code.png',
                ],
                'gallery_ids' => ['print_code_gallery'],
            ],
        ];

        foreach ($expected as $slug => $expectation) {
            $product = Product::where('slug', $slug)->firstOrFail();

            $this->assertSame(
                $expectation['swatches'],
                data_get($product->product_config, $expectation['option_path']),
            );

            foreach ($expectation['gallery_ids'] as $galleryId) {
                $rule = collect(data_get($product->product_config, 'media.gallery_rules', []))
                    ->firstWhere('id', $galleryId);

                $this->assertSame(
                    $galleryId === 'signature_stripe_gallery'
                        ? '/images/products/pvc/pvc-signature-stripe.png'
                        : '/images/products/pvc/pvc-print-code.png',
                    data_get($rule, 'primary'),
                );
            }
        }

        $basicRules = data_get(
            Product::where('slug', 'basic-pvc-card')->firstOrFail()->product_config,
            'media.gallery_rules',
        );
        $this->assertSame(
            '/images/matte.png',
            data_get(collect($basicRules)->firstWhere('id', 'matte_gallery'), 'primary'),
        );
    }
}
