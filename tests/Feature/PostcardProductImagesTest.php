<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductConfigurationService;
use App\Services\ProductImageResolver;
use App\Support\PostcardProductImageCatalog;
use Database\Seeders\PostcardProductImageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostcardProductImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_card_image_seeder_restores_each_original_default_gallery(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        $originalGalleries = [
            'classic-standard-business-cards' => [
                '/images/products/classic-standard-business-cards/classic-standard-business-cards-default-01.png',
                '/images/products/classic-standard-business-cards/classic-standard-business-cards-default-02.png',
                '/images/products/classic-standard-business-cards/classic-standard-business-cards-default-03.png',
                '/images/products/classic-standard-business-cards/classic-standard-business-cards-default-04.png',
            ],
            'classic-special-business-cards' => [
                '/images/classic-special-business-cards/default01.png',
                '/images/classic-special-business-cards/default02.png',
                '/images/classic-special-business-cards/default03.png',
                '/images/classic-special-business-cards/default04.png',
            ],
            'super-standard-business-cards' => [
                '/images/products/super-business-cards/super-business-cards-default-01.png',
                '/images/products/super-business-cards/super-business-cards-default-02.png',
                '/images/products/super-business-cards/super-business-cards-default-03.png',
                '/images/products/super-business-cards/super-business-cards-default-04.png',
            ],
            'super-luxe-business-cards' => [
                '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-01.png',
                '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-02.png',
                '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-03.png',
                '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-04.png',
            ],
            'standard-quality-business-cards' => [
                '/images/products/standard-quality-business-cards/default-01.png',
                '/images/products/standard-quality-business-cards/default-02.png',
                '/images/products/standard-quality-business-cards/default-03.png',
                '/images/products/standard-quality-business-cards/default-04.png',
            ],
            'solid-quality-business-cards' => [
                '/images/products/solid-quality-business-cards/default-01.png',
                '/images/products/solid-quality-business-cards/default-02.png',
                '/images/products/solid-quality-business-cards/default-03.png',
                '/images/products/solid-quality-business-cards/default-04.png',
            ],
        ];

        foreach ($originalGalleries as $slug => $gallery) {
            Product::create([
                'name' => str_replace('-', ' ', $slug),
                'slug' => $slug,
                'product_category_id' => $category->id,
                'featured_image' => PostcardProductImageCatalog::imageFor($slug),
                'product_config' => [
                    'product' => [
                        'featured_image' => PostcardProductImageCatalog::imageFor($slug),
                    ],
                    'media' => [
                        'gallery' => [PostcardProductImageCatalog::imageFor($slug)],
                        'gallery_rules' => [[
                            'id' => 'default',
                            'match' => [],
                            'images' => $gallery,
                            'primary' => $gallery[0],
                        ]],
                    ],
                ],
            ]);
        }

        (new PostcardProductImageSeeder)->run();

        foreach ($originalGalleries as $slug => $gallery) {
            $product = Product::where('slug', $slug)->firstOrFail();

            $this->assertSame($gallery[0], $product->featured_image);
            $this->assertSame(
                $gallery[0],
                data_get($product->product_config, 'product.featured_image'),
            );
            $this->assertSame(
                $gallery,
                data_get($product->product_config, 'media.gallery'),
            );
            $options = app(ProductConfigurationService::class)->storefrontOptions($product);

            $this->assertSame(
                array_map(
                    fn (string $image): mixed => app(ProductImageResolver::class)->url($image),
                    $gallery,
                ),
                data_get($options, 'galleries.0.images'),
            );

            foreach ($gallery as $image) {
                $this->assertFileExists(public_path(ltrim($image, '/')));
            }
        }
    }
}
