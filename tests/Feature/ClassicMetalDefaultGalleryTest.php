<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ClassicMetalDefaultGalleryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<int, string>
     */
    private const DEFAULT_GALLERY = [
        '/images/products/metal/classic-metal-business-cards-01.png',
        '/images/products/metal/classic-metal-business-cards-02.png',
        '/images/products/metal/classic-metal-business-cards-03.png',
        '/images/products/metal/classic-metal-business-cards-04.png',
    ];

    public function test_migration_updates_the_classic_metal_default_gallery_idempotently(): void
    {
        $category = ProductCategory::create([
            'name' => 'Metal Business Cards',
            'slug' => 'metal-business-cards',
        ]);
        $oldGallery = [
            self::DEFAULT_GALLERY[3],
            self::DEFAULT_GALLERY[1],
            self::DEFAULT_GALLERY[2],
            self::DEFAULT_GALLERY[0],
        ];

        Product::create([
            'name' => 'Classic Metal Business Cards',
            'slug' => 'classic-metal-business-cards',
            'featured_image' => $oldGallery[0],
            'product_category_id' => $category->id,
            'product_config' => [
                'product' => ['featured_image' => $oldGallery[0]],
                'media' => [
                    'gallery' => $oldGallery,
                    'gallery_rules' => [[
                        'id' => 'default',
                        'match' => [],
                        'images' => $oldGallery,
                        'primary' => $oldGallery[0],
                    ]],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_17_000005_update_classic_metal_default_gallery.php',
        );
        $migration->up();
        $migration->up();

        $product = Product::where('slug', 'classic-metal-business-cards')->firstOrFail();
        $config = $product->product_config;
        $defaultRule = collect(data_get($config, 'media.gallery_rules', []))
            ->firstWhere('id', 'default');

        $this->assertSame(self::DEFAULT_GALLERY, data_get($config, 'media.gallery'));
        $this->assertSame(self::DEFAULT_GALLERY, data_get($defaultRule, 'images'));
        $this->assertSame(self::DEFAULT_GALLERY[0], data_get($defaultRule, 'primary'));
        $this->assertSame(self::DEFAULT_GALLERY[0], $product->featured_image);
        $this->assertSame(self::DEFAULT_GALLERY[0], data_get($config, 'product.featured_image'));
    }

    public function test_the_four_classic_metal_gallery_assets_exist_with_webp_derivatives(): void
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
