<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\ClassicStandardBusinessCardGallery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassicStandardBusinessCardGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_replaces_classic_standard_gallery_rules_idempotently(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);
        $oldImage = '/images/old-classic-standard.png';
        $foilImage = '/images/foil-classic-standard.png';
        $keptImage = '/images/kept-classic-standard.png';

        Product::create([
            'name' => 'Classic Standard Business Cards',
            'slug' => ClassicStandardBusinessCardGallery::PRODUCT_SLUG,
            'featured_image' => $oldImage,
            'product_category_id' => $category->id,
            'product_config' => [
                'product' => ['featured_image' => $oldImage],
                'media' => [
                    'gallery' => [$oldImage],
                    'gallery_rules' => [
                        [
                            'id' => 'default',
                            'match' => [],
                            'images' => [$oldImage],
                            'primary' => $oldImage,
                        ],
                        [
                            'id' => 'old-matte',
                            'match' => [
                                'paper_finish' => 'Matte',
                                'corners' => 'Square',
                                'special_finish' => 'no special finish',
                            ],
                            'images' => [$oldImage],
                            'primary' => $oldImage,
                        ],
                        [
                            'id' => 'old-uv',
                            'match' => [
                                'corners' => 'Rounded',
                                'special_finish' => 'no_special_finish',
                                'uv_finish' => 'single_side_uv',
                            ],
                            'images' => [$oldImage],
                            'primary' => $oldImage,
                        ],
                        [
                            'id' => 'keep-foil',
                            'match' => ['special_finish' => 'gold foil'],
                            'images' => [$foilImage],
                            'primary' => $foilImage,
                        ],
                        [
                            'id' => 'keep-custom',
                            'match' => ['texture' => 'linen'],
                            'images' => [$keptImage],
                            'primary' => $keptImage,
                        ],
                    ],
                ],
            ],
        ]);

        $migration = require base_path(
            'database/migrations/2026_09_18_000002_update_classic_standard_default_gallery.php',
        );
        $migration->up();
        $migration->up();

        $product = Product::where('slug', ClassicStandardBusinessCardGallery::PRODUCT_SLUG)->firstOrFail();
        $config = $product->product_config;
        $galleryRules = data_get($config, 'media.gallery_rules', []);
        $rules = collect(is_array($galleryRules) ? $galleryRules : []);

        $this->assertSame(
            ClassicStandardBusinessCardGallery::DEFAULT_GALLERY,
            data_get($config, 'media.gallery'),
        );
        $this->assertSame(
            ClassicStandardBusinessCardGallery::DEFAULT_GALLERY[0],
            $product->featured_image,
        );
        $this->assertSame(
            ClassicStandardBusinessCardGallery::DEFAULT_GALLERY[0],
            data_get($config, 'product.featured_image'),
        );

        foreach (ClassicStandardBusinessCardGallery::rules() as $expectedRule) {
            $rule = $rules->firstWhere('id', $expectedRule['id']);

            $this->assertSame($expectedRule['match'], data_get($rule, 'match'));
            $this->assertSame($expectedRule['images'], data_get($rule, 'images'));
            $this->assertSame($expectedRule['primary'], data_get($rule, 'primary'));
        }

        $this->assertSame([$foilImage], data_get($rules->firstWhere('id', 'keep-foil'), 'images'));
        $this->assertSame([$keptImage], data_get($rules->firstWhere('id', 'keep-custom'), 'images'));
        $this->assertNull($rules->firstWhere('id', 'old-matte'));
        $this->assertNull($rules->firstWhere('id', 'old-uv'));
    }

    public function test_all_classic_standard_gallery_assets_exist_with_webp_derivatives(): void
    {
        $images = collect(ClassicStandardBusinessCardGallery::DEFAULT_GALLERY)
            ->merge(collect(ClassicStandardBusinessCardGallery::rules())->flatMap(
                static fn (array $rule): array => $rule['images'],
            ))
            ->unique()
            ->values();

        $this->assertCount(16, $images);

        foreach ($images as $image) {
            $relativePath = ltrim($image, '/');
            $webpPath = preg_replace('/\.(?:jpe?g|png)$/i', '.webp', $relativePath);

            $this->assertNotFalse($webpPath);
            $this->assertFileExists(public_path($relativePath));
            $this->assertFileExists(public_path((string) $webpPath));
        }
    }
}
