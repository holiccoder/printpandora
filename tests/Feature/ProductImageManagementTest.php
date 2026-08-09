<?php

namespace Tests\Feature;

use App\Jobs\PerformProductMediaConversions;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductImageService;
use App\Support\ProductImagePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class ProductImageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('media-library.queue_conversions_after_database_commit', false);
    }

    public function test_a_product_gallery_image_can_be_replaced_and_restored(): void
    {
        Storage::fake('public');
        $queue = Queue::fake();
        config()->set('media-library.queue_conversions_after_database_commit', false);

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
            UploadedFile::fake()->image('replacement.png', 2100, 210),
        );

        $this->assertSame($slot['key'], $media->getCustomProperty('slot_key'));
        $this->assertCount(1, $product->getMedia(ProductImageService::GALLERY_OVERRIDE_COLLECTION));
        $this->assertFalse($media->hasGeneratedConversion(ProductImagePolicy::STOREFRONT_CONVERSION));
        Queue::assertPushed(PerformProductMediaConversions::class);

        $source = $this->imageMetadata($media->getPath());

        $this->assertSame('image/png', $source['mime']);
        $this->assertSame([2100, 210], [$source[0], $source[1]]);

        $options = $service->applyGalleryOverrides($product, $product->product_options);

        $this->assertSame($media->getUrl(), $options['galleries'][0]['images'][0]);

        $overriddenSlot = $service->gallerySlots($product)[0];

        $this->assertTrue($overriddenSlot['is_overridden']);
        $this->assertSame($media->getUrl(), $overriddenSlot['current_url']);

        $media = $this->runQueuedMediaConversion($queue, $media);
        $this->assertTrue($media->hasGeneratedConversion(ProductImagePolicy::STOREFRONT_CONVERSION));

        $webp = $this->imageMetadata(
            $media->getPath(ProductImagePolicy::STOREFRONT_CONVERSION),
        );

        $this->assertSame('image/webp', $webp['mime']);
        $this->assertSame([2000, 200], [$webp[0], $webp[1]]);

        $options = $service->applyGalleryOverrides($product, $product->product_options);

        $this->assertSame(
            $media->getUrl(ProductImagePolicy::STOREFRONT_CONVERSION),
            $options['galleries'][0]['images'][0],
        );

        $overriddenSlot = $service->gallerySlots($product)[0];

        $this->assertTrue($overriddenSlot['is_overridden']);
        $this->assertSame(
            $media->getUrl(ProductImagePolicy::STOREFRONT_CONVERSION),
            $overriddenSlot['current_url'],
        );

        $media->markAsConversionNotGenerated(ProductImagePolicy::STOREFRONT_CONVERSION);

        $this->assertSame($media->getUrl(), $service->gallerySlots($product)[0]['current_url']);

        $service->resetGalleryImage($product, $slot);

        $restoredSlot = $service->gallerySlots($product)[0];

        $this->assertFalse($restoredSlot['is_overridden']);
        $this->assertSame('/images/original.jpg', $restoredSlot['current_url']);
    }

    public function test_a_featured_image_uses_a_webp_derivative_without_upscaling(): void
    {
        Storage::fake('public');
        $queue = Queue::fake();
        config()->set('media-library.queue_conversions_after_database_commit', false);

        $category = ProductCategory::create([
            'name' => 'Business Cards',
            'slug' => 'business-cards',
        ]);

        $product = Product::create([
            'name' => 'Featured Image Test',
            'slug' => 'featured-image-test',
            'product_category_id' => $category->id,
        ]);

        $service = app(ProductImageService::class);
        $url = $service->replaceFeaturedImage(
            $product,
            UploadedFile::fake()->image('featured.png', 400, 200),
        );

        $product->refresh();
        $media = $product->getMedia(ProductImageService::FEATURED_OVERRIDE_COLLECTION)->first();

        $this->assertNotNull($media);
        $this->assertFalse($media->hasGeneratedConversion(ProductImagePolicy::STOREFRONT_CONVERSION));
        $this->assertSame($media->getUrl(), $url);
        $this->assertSame($url, $product->featured_image);
        $this->assertSame($url, $service->featuredImageUrl($product));
        Queue::assertPushed(PerformProductMediaConversions::class);

        $media = $this->runQueuedMediaConversion($queue, $media);

        $this->assertTrue($media->hasGeneratedConversion(ProductImagePolicy::STOREFRONT_CONVERSION));
        $this->assertSame(
            $media->getUrl(ProductImagePolicy::STOREFRONT_CONVERSION),
            $service->featuredImageUrl($product),
        );
        $this->assertSame($url, $product->fresh()->featured_image);

        $webp = $this->imageMetadata(
            $media->getPath(ProductImagePolicy::STOREFRONT_CONVERSION),
        );

        $this->assertSame('image/webp', $webp['mime']);
        $this->assertSame([400, 200], [$webp[0], $webp[1]]);
    }

    private function runQueuedMediaConversion(QueueFake $queue, Media $media): Media
    {
        $job = $queue->pushed(PerformProductMediaConversions::class)->first();

        $this->assertInstanceOf(PerformProductMediaConversions::class, $job);
        $this->assertSame(3, $job->tries);
        $this->assertSame(180, $job->timeout);
        $this->assertTrue($job->failOnTimeout);
        $this->assertSame([10, 30, 60], $job->backoff);
        $job->handle(app(FileManipulator::class));

        return $media->refresh();
    }

    /**
     * @return array<int|string, int|string>
     */
    private function imageMetadata(string $path): array
    {
        $metadata = getimagesize($path);

        $this->assertIsArray($metadata);

        return $metadata;
    }
}
