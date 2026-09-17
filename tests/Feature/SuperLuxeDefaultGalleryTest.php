<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperLuxeDefaultGalleryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<int, string>
     */
    private const DEFAULT_GALLERY = [
        '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-01.png',
        '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-02.png',
        '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-03.png',
        '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-04.png',
    ];

    public function test_migration_updates_the_super_luxe_default_gallery_idempotently(): void
    {
        $category = ProductCategory::create([
            'name' => 'Super Business Cards',
            'slug' => 'super-business-cards',
        ]);
        $oldGallery = [
            '/images/products/luxe-business-cards/luxe-business-cards-standard-01.png',
            '/images/products/luxe-business-cards/luxe-business-cards-standard-02.png',
            '/images/products/luxe-business-cards/luxe-business-cards-standard-03.png',
            '/images/products/luxe-business-cards/luxe-business-cards-standard-04.png',
        ];
        $textureImage = '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j1.png';

        Product::create([
            'name' => 'Super Luxe Business Cards',
            'slug' => 'super-luxe-business-cards',
            'featured_image' => $oldGallery[0],
            'product_category_id' => $category->id,
            'product_config' => [
                'product' => [
                    'featured_image' => $oldGallery[0],
                ],
                'media' => [
                    'gallery' => $oldGallery,
                    'gallery_rules' => [
                        [
                            'id' => 'default',
                            'match' => [],
                            'images' => $oldGallery,
                            'primary' => $oldGallery[0],
                        ],
                        [
                            'id' => 'inkpavo_j1',
                            'match' => [
                                'sizes' => 'standard',
                                'texture' => 'inkpavo_j1',
                            ],
                            'images' => [$textureImage],
                            'primary' => $textureImage,
                        ],
                    ],
                ],
                'faq' => [
                    ['question' => 'Keep this FAQ', 'answer' => 'Yes'],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_18_000001_update_super_luxe_default_gallery.php',
        );
        $migration->up();
        $migration->up();

        $product = Product::where('slug', 'super-luxe-business-cards')->firstOrFail();
        $config = $product->product_config;
        $defaultRule = collect(data_get($config, 'media.gallery_rules', []))
            ->firstWhere('id', 'default');

        $this->assertSame(self::DEFAULT_GALLERY, data_get($config, 'media.gallery'));
        $this->assertSame(self::DEFAULT_GALLERY, data_get($defaultRule, 'images'));
        $this->assertSame(self::DEFAULT_GALLERY[0], data_get($defaultRule, 'primary'));
        $this->assertSame(self::DEFAULT_GALLERY[0], $product->featured_image);
        $this->assertSame(self::DEFAULT_GALLERY[0], data_get($config, 'product.featured_image'));
        $this->assertSame($textureImage, data_get($config, 'media.gallery_rules.1.primary'));
        $this->assertSame('Keep this FAQ', data_get($config, 'faq.0.question'));
    }

    public function test_the_four_super_luxe_gallery_assets_exist_with_webp_derivatives(): void
    {
        foreach (self::DEFAULT_GALLERY as $image) {
            $relativePath = ltrim($image, '/');
            $webpPath = preg_replace('/\.(?:jpe?g|png)$/i', '.webp', $relativePath);

            $this->assertNotFalse($webpPath);
            $this->assertFileExists(public_path($relativePath));
            $this->assertFileExists(public_path((string) $webpPath));
        }
    }
}
