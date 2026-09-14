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

    public function test_migration_removes_special_finish_on_sides_from_all_product_option_sources(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        foreach ([
            [
                'slug' => 'classic-standard-business-cards',
                'product_config' => [
                    'options' => [
                        'sizes' => ['values' => [['code' => 'standard']]],
                        'special_finish_on_sides' => [
                            'default' => 'one_side',
                            'values' => [['code' => 'one_side']],
                        ],
                    ],
                ],
                'product_options' => [
                    'sizes' => [['code' => 'standard']],
                    'special_finish_on_sides' => [['code' => 'one_side']],
                ],
            ],
            [
                'slug' => 'standard-pvc-card',
                'product_config' => [
                    'options' => [
                        'paper_finish' => ['values' => [['code' => 'matte']]],
                        'special_finish_on_sides' => [
                            'default' => 'both_sides',
                            'values' => [['code' => 'both_sides']],
                        ],
                    ],
                ],
                'product_options' => null,
            ],
            [
                'slug' => 'classic-special-business-cards',
                'product_config' => [],
                'product_options' => [
                    'special_finish_on_sides' => [['code' => 'both_sides']],
                    'texture' => [['code' => 'linen_paper']],
                ],
            ],
        ] as $definition) {
            Product::create([
                'name' => str_replace('-', ' ', $definition['slug']),
                'slug' => $definition['slug'],
                'product_category_id' => $category->id,
                'product_config' => $definition['product_config'],
                'product_options' => $definition['product_options'],
            ]);
        }

        $migration = require base_path(
            'database/migrations/2026_09_14_000003_remove_special_finish_on_sides_options.php',
        );
        $migration->up();
        $migration->up();

        foreach ([
            'classic-standard-business-cards',
            'standard-pvc-card',
            'classic-special-business-cards',
        ] as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();

            $this->assertArrayNotHasKey(
                'special_finish_on_sides',
                $product->product_config['options'] ?? [],
                $slug.' canonical options',
            );
            $this->assertArrayNotHasKey(
                'special_finish_on_sides',
                $product->product_options ?? [],
                $slug.' legacy options',
            );
        }

        $this->assertSame(
            ['standard'],
            data_get(
                Product::where('slug', 'classic-standard-business-cards')->firstOrFail()->product_config,
                'options.sizes.values.*.code',
            ),
        );
        $this->assertSame(
            [['code' => 'linen_paper']],
            Product::where('slug', 'classic-special-business-cards')->firstOrFail()->product_options['texture'],
        );
    }

    public function test_migration_renames_no_special_finish_labels(): void
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
                    'special_finish' => [
                        'values' => [
                            ['code' => 'no_special_finish', 'label' => 'No special finish'],
                            ['code' => 'bright_gold', 'label' => 'Bright Gold'],
                        ],
                    ],
                ],
            ],
            'product_options' => [
                'special_finish' => [
                    ['code' => 'no_special_finish', 'name' => 'No special finish'],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_14_000008_rename_no_special_finish_option_label.php',
        );
        $migration->up();
        $migration->up();

        $product = Product::where('slug', 'classic-standard-business-cards')->firstOrFail();

        $this->assertSame(
            'No finish',
            data_get($product->product_config, 'options.special_finish.values.0.label'),
        );
        $this->assertSame(
            'No finish',
            data_get($product->product_options, 'special_finish.0.name'),
        );
    }

    public function test_migration_replaces_classic_special_pin_hole_texture_with_matte(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        Product::create([
            'name' => 'Classic Special Business Cards',
            'slug' => 'classic-special-business-cards',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'texture' => [
                        'label' => 'Texture',
                        'type' => 'select',
                        'required' => true,
                        'default' => 'pin_hole_paper',
                        'values' => [
                            [
                                'code' => 'pin_hole_paper',
                                'label' => 'Pin-hole Paper',
                                'description' => '',
                                'swatch_image' => '/images/products/classic-special-business-cards/texture/pin-hole-paper.png',
                            ],
                            ['code' => 'water_ripple_paper', 'label' => 'Water Ripple Paper'],
                        ],
                    ],
                ],
            ],
            'product_options' => [
                'texture' => [
                    [
                        'name' => 'Pin-hole Paper',
                        'code' => 'pin_hole_paper',
                        'description' => '',
                        'swatch_image' => '/images/products/classic-special-business-cards/texture/pin-hole-paper.png',
                    ],
                    ['name' => 'Linen Paper', 'code' => 'linen_paper'],
                ],
            ],
        ]);

        Product::create([
            'name' => 'Super Business Cards',
            'slug' => 'super-business-cards',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'texture' => [
                        'default' => 'j8_pinhole_paper',
                        'values' => [['code' => 'j8_pinhole_paper']],
                    ],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_14_000007_remove_classic_special_pin_hole_texture_option.php',
        );
        $migration->up();
        $migration->up();

        $product = Product::where('slug', 'classic-special-business-cards')->firstOrFail();
        $canonicalTexture = data_get($product->product_config, 'options.texture');

        $this->assertSame('matte', $canonicalTexture['default']);
        $this->assertSame(
            ['matte', 'water_ripple_paper'],
            array_column($canonicalTexture['values'], 'code'),
        );
        $this->assertSame('Matte', data_get($canonicalTexture, 'values.0.label'));
        $this->assertSame(
            '/images/product-options/business-cards/laminates/matte-526x251.jpg',
            data_get($canonicalTexture, 'values.0.swatch_image'),
        );
        $this->assertSame(
            ['matte', 'linen_paper'],
            array_column($product->product_options['texture'], 'code'),
        );
        $this->assertSame('Matte', data_get($product->product_options, 'texture.0.name'));
        $this->assertNotContains(
            'pin_hole_paper',
            array_column($canonicalTexture['values'], 'code'),
        );
        $this->assertNotContains(
            'pin_hole_paper',
            array_column($product->product_options['texture'], 'code'),
        );

        $superTexture = Product::where('slug', 'super-business-cards')
            ->firstOrFail()
            ->product_config['options']['texture'];
        $this->assertSame('j8_pinhole_paper', $superTexture['default']);
        $this->assertSame(['j8_pinhole_paper'], array_column($superTexture['values'], 'code'));
    }

    public function test_migration_makes_hot_and_cold_foil_groups_multi_select(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        Product::create([
            'name' => 'Classic Quality Business Cards',
            'slug' => 'classic-quality-business-cards',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'special_finish' => [
                        'label' => 'Special Finish',
                        'type' => 'select',
                        'default' => 'no_special_finish',
                        'values' => [
                            ['code' => 'no_special_finish', 'label' => 'No finish'],
                            ['code' => 'bright_gold', 'label' => 'Bright Gold', 'description' => 'Bright Gold hot foil.'],
                            ['code' => 'cold_blue_gold', 'label' => 'Cold Blue Gold'],
                        ],
                    ],
                ],
            ],
        ]);

        Product::create([
            'name' => 'Premium Metal Business Cards',
            'slug' => 'premium-metal-business-cards',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'special_finish' => [
                        'label' => 'Special Finish',
                        'type' => 'select',
                        'default' => 'laser_engraving',
                        'values' => [
                            ['code' => 'laser_engraving', 'label' => 'Laser Engraving'],
                            ['code' => 'nfc', 'label' => 'NFC'],
                        ],
                    ],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_14_000006_make_foil_options_multi_select.php',
        );
        $migration->up();
        $migration->up();

        $this->assertSame(
            'multi_select',
            data_get(
                Product::where('slug', 'classic-quality-business-cards')->firstOrFail()->product_config,
                'options.special_finish.type',
            ),
        );
        $this->assertSame(
            'select',
            data_get(
                Product::where('slug', 'premium-metal-business-cards')->firstOrFail()->product_config,
                'options.special_finish.type',
            ),
        );
    }

    public function test_migration_moves_classic_quality_finish_options_to_texture(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        $qualityTextureValues = [
            ['code' => 'shattered_glass_film', 'label' => 'Shattered Glass Film'],
            ['code' => 'holographic_film', 'label' => 'Holographic Film'],
            ['code' => 'starlight_film', 'label' => 'Starlight Film'],
            ['code' => 'holographic_star_film', 'label' => 'Holographic Star Film'],
            ['code' => 'soft_touch_film', 'label' => 'Soft-Touch Film'],
            ['code' => 'uv', 'label' => '3D UV'],
        ];

        $quality = Product::create([
            'name' => 'Classic Quality Business Cards',
            'slug' => 'classic-quality-business-cards',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'texture' => [
                        'label' => 'Texture',
                        'type' => 'select',
                        'required' => true,
                        'default' => 'shattered_glass_film',
                        'values' => $qualityTextureValues,
                    ],
                    'paper_finish' => [
                        'label' => 'Paper Finish',
                        'type' => 'select',
                        'default' => 'matte',
                        'values' => [
                            ['code' => 'matte', 'label' => 'Matte'],
                            ['code' => 'gloss', 'label' => 'Gloss'],
                            ['code' => 'uv', 'label' => '3D UV'],
                        ],
                    ],
                ],
                'media' => [
                    'gallery_rules' => [
                        [
                            'id' => 'matte-gallery',
                            'match' => [
                                'sizes' => 'Standard',
                                'paper_finish' => 'Matte',
                                'corners' => 'Square',
                            ],
                            'images' => ['/images/matte-gallery.png'],
                            'primary' => '/images/matte-gallery.png',
                        ],
                        [
                            'id' => 'gloss-gallery',
                            'match' => [
                                'sizes' => 'Standard',
                                'paper_finish' => 'Gloss',
                                'corners' => 'Square',
                            ],
                            'images' => ['/images/gloss-gallery.png'],
                            'primary' => '/images/gloss-gallery.png',
                        ],
                        [
                            'id' => 'keep-this-gallery',
                            'match' => ['special_finish' => 'bright_gold'],
                            'images' => ['/images/keep-this-gallery.png'],
                            'primary' => '/images/keep-this-gallery.png',
                        ],
                    ],
                ],
            ],
            'product_options' => [
                'texture' => [
                    ['name' => 'Shattered Glass Film', 'code' => 'shattered_glass_film'],
                    ['name' => 'Holographic Film', 'code' => 'holographic_film'],
                    ['name' => 'Starlight Film', 'code' => 'starlight_film'],
                    ['name' => 'Holographic Star Film', 'code' => 'holographic_star_film'],
                    ['name' => 'Soft-Touch Film', 'code' => 'soft_touch_film'],
                    ['name' => '3D UV', 'code' => 'uv'],
                ],
                'paper_finish' => [
                    ['name' => 'Matte', 'code' => 'matte'],
                    ['name' => 'Gloss', 'code' => 'gloss'],
                    ['name' => '3D UV', 'code' => 'uv'],
                ],
                'galleries' => [
                    [
                        'id' => 'matte-gallery',
                        'match' => [
                            'sizes' => 'Standard',
                            'paper_finish' => 'Matte',
                        ],
                        'images' => ['/images/matte-gallery.png'],
                    ],
                    [
                        'id' => 'gloss-gallery',
                        'match' => [
                            'sizes' => 'Standard',
                            'paper_finish' => 'Gloss',
                        ],
                        'images' => ['/images/gloss-gallery.png'],
                    ],
                ],
            ],
        ]);

        $otherProduct = Product::create([
            'name' => 'Classic Standard Business Cards',
            'slug' => 'classic-standard-business-cards',
            'product_category_id' => $category->id,
            'product_config' => [
                'options' => [
                    'paper_finish' => [
                        'values' => [['code' => 'matte']],
                    ],
                ],
            ],
            'product_options' => [
                'paper_finish' => [['name' => 'Matte', 'code' => 'matte']],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_14_000009_move_classic_quality_finish_options_to_texture.php',
        );
        $migration->up();
        $quality->refresh();
        $afterFirstConfig = $quality->product_config;
        $afterFirstLegacy = $quality->product_options;

        $migration->up();
        $quality->refresh();

        $this->assertSame($afterFirstConfig, $quality->product_config);
        $this->assertSame($afterFirstLegacy, $quality->product_options);
        $this->assertArrayNotHasKey('paper_finish', $quality->product_config['options']);
        $this->assertSame(
            [
                'shattered_glass_film',
                'holographic_film',
                'starlight_film',
                'holographic_star_film',
                'soft_touch_film',
                'matte',
                'gloss',
            ],
            data_get($quality->product_config, 'options.texture.values.*.code'),
        );
        $this->assertSame(
            'shattered_glass_film',
            data_get($quality->product_config, 'options.texture.default'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/matte-paper-finish.webp',
            data_get($quality->product_config, 'options.texture.values.5.swatch_image'),
        );
        $this->assertSame(
            '/images/product-options/business-cards/swatches/gloss-paper-finish.webp',
            data_get($quality->product_config, 'options.texture.values.6.swatch_image'),
        );
        $this->assertNotContains(
            'uv',
            data_get($quality->product_config, 'options.texture.values.*.code'),
        );

        $canonicalMatteGallery = collect(data_get($quality->product_config, 'media.gallery_rules'))
            ->firstWhere('id', 'matte-gallery');
        $this->assertSame('matte', data_get($canonicalMatteGallery, 'match.texture'));
        $this->assertArrayNotHasKey('paper_finish', $canonicalMatteGallery['match']);
        $this->assertSame(
            ['special_finish' => 'bright_gold'],
            data_get(
                collect(data_get($quality->product_config, 'media.gallery_rules'))
                    ->firstWhere('id', 'keep-this-gallery'),
                'match',
            ),
        );

        $this->assertArrayNotHasKey('paper_finish', $quality->product_options);
        $this->assertSame(
            [
                'shattered_glass_film',
                'holographic_film',
                'starlight_film',
                'holographic_star_film',
                'soft_touch_film',
                'matte',
                'gloss',
            ],
            data_get($quality->product_options, 'texture.*.code'),
        );
        $this->assertNotContains('uv', data_get($quality->product_options, 'texture.*.code'));
        $this->assertSame(
            '/images/product-options/business-cards/swatches/matte-paper-finish.webp',
            data_get($quality->product_options, 'texture.5.swatch_image'),
        );
        $legacyMatteGallery = collect(data_get($quality->product_options, 'galleries'))
            ->firstWhere('id', 'matte-gallery');
        $this->assertSame('matte', data_get($legacyMatteGallery, 'match.texture'));
        $this->assertArrayNotHasKey('paper_finish', $legacyMatteGallery['match']);

        $this->assertArrayHasKey(
            'paper_finish',
            $otherProduct->fresh()->product_config['options'],
        );
        $this->assertArrayHasKey(
            'paper_finish',
            $otherProduct->fresh()->product_options,
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

    public function test_migration_makes_no_print_code_swatch_primary_for_all_pvc_cards(): void
    {
        $category = ProductCategory::create([
            'name' => 'PVC Business Cards',
            'slug' => 'pvc-business-cards',
        ]);

        $definitions = [
            'basic-pvc-card' => [
                'option_key' => 'print_code',
                'no_print_code' => 'no_print_code',
                'values' => ['no_print_code', 'print_code'],
            ],
            'standard-pvc-card' => [
                'option_key' => 'print_code_or_signature_stripe',
                'no_print_code' => 'no_print_code_or_signature_stripe',
                'values' => [
                    'no_print_code_or_signature_stripe',
                    'print_code',
                    'signature_stripe',
                ],
            ],
            'premium-pvc-card' => [
                'option_key' => 'print_code',
                'no_print_code' => 'no_print_code',
                'values' => ['no_print_code', 'print_code'],
            ],
        ];

        foreach ($definitions as $slug => $definition) {
            Product::create([
                'name' => $slug,
                'slug' => $slug,
                'product_category_id' => $category->id,
                'product_config' => [
                    'options' => [
                        $definition['option_key'] => [
                            'values' => array_map(
                                static fn (string $code): array => ['code' => $code],
                                $definition['values'],
                            ),
                        ],
                    ],
                    'media' => [
                        'gallery_rules' => [
                            [
                                'id' => 'default',
                                'match' => [],
                                'images' => ['/images/products/pvc/pvc-01.jpg'],
                                'primary' => '/images/products/pvc/pvc-01.jpg',
                            ],
                            [
                                'id' => 'legacy_no_print_code_gallery',
                                'match' => [$definition['option_key'] => $definition['no_print_code']],
                                'images' => ['/images/old-no-print-code.png'],
                                'primary' => '/images/old-no-print-code.png',
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
        }

        $migration = require base_path(
            'database/migrations/2026_09_14_000011_make_pvc_no_print_code_swatch_primary.php',
        );
        $migration->up();
        $migration->up();

        $image = '/images/product-options/business-cards/swatches/pvc-no-print-code.png';

        foreach (array_keys($definitions) as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();
            $rules = data_get($product->product_config, 'media.gallery_rules');
            $noPrintRule = collect($rules)->firstWhere('id', 'no_print_code_gallery');

            $this->assertSame([$image], data_get($noPrintRule, 'images'));
            $this->assertSame($image, data_get($noPrintRule, 'primary'));
            $this->assertCount(
                1,
                collect($rules)->filter(
                    fn (mixed $rule): bool => data_get($rule, 'id') === 'no_print_code_gallery',
                ),
            );
            $this->assertSame(
                '/images/matte.png',
                data_get(collect($rules)->firstWhere('id', 'matte_gallery'), 'primary'),
            );
        }
    }
}
