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
}
