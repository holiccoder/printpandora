<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_product_gallery_image_can_be_replaced_and_restored(): void
    {
        Storage::fake('public');

        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        $product = Product::create([
            'name' => 'Test Business Cards',
            'slug' => 'test-business-cards',
            'product_category_id' => $category->id,
            'product_options' => [
                'galleries' => [[
                    'id' => 'default',
                    'is_default' => true,
                    'images' => ['/images/original.jpg'],
                ]],
            ],
        ]);

        $service = app(ProductImageService::class);
        $slot = $service->gallerySlots($product)[0];

        $media = $service->replaceGalleryImage(
            $product,
            $slot,
            UploadedFile::fake()->image('replacement.png'),
        );

        $this->assertSame($slot['key'], $media->getCustomProperty('slot_key'));
        $this->assertCount(1, $product->getMedia(ProductImageService::GALLERY_OVERRIDE_COLLECTION));

        $options = $service->applyGalleryOverrides($product, $product->product_options);

        $this->assertSame($media->getUrl(), $options['galleries'][0]['images'][0]);
        $this->assertTrue($service->gallerySlots($product)[0]['is_overridden']);

        $service->resetGalleryImage($product, $slot);

        $restoredSlot = $service->gallerySlots($product)[0];

        $this->assertFalse($restoredSlot['is_overridden']);
        $this->assertSame(asset('images/original.jpg'), $restoredSlot['current_url']);
    }
}
