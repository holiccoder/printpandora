<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductConfigurationService;
use App\Support\PostcardProductImageCatalog;
use Database\Seeders\PostcardProductImageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostcardProductImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_postcard_image_seeder_assigns_each_default_image(): void
    {
        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        foreach (array_keys(PostcardProductImageCatalog::images()) as $slug) {
            Product::create([
                'name' => str_replace('-', ' ', $slug),
                'slug' => $slug,
                'product_category_id' => $category->id,
                'featured_image' => '/images/old-default.png',
                'product_config' => [
                    'product' => ['featured_image' => '/images/old-default.png'],
                    'media' => ['gallery' => ['/images/old-default.png']],
                ],
            ]);
        }

        (new PostcardProductImageSeeder)->run();

        foreach (PostcardProductImageCatalog::images() as $slug => $image) {
            $product = Product::where('slug', $slug)->firstOrFail();

            $this->assertSame($image, $product->featured_image);
            $this->assertSame(
                $image,
                data_get($product->product_config, 'product.featured_image'),
            );
            $this->assertSame(
                [$image],
                data_get($product->product_config, 'media.gallery'),
            );
            $this->assertFileExists(public_path(ltrim($image, '/')));
        }

        foreach (['standard-quality-business-cards', 'solid-quality-business-cards'] as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();
            $options = app(ProductConfigurationService::class)->storefrontOptions($product);

            $this->assertSame(
                PostcardProductImageCatalog::imageFor($slug),
                data_get($options, 'galleries.0.images.0'),
            );
        }
    }
}
