<?php

namespace Tests\Unit;

use App\Jobs\GenerateProductImageWebp;
use App\Services\MediaLibraryCatalog;
use App\Services\ProductImageConversionService;
use App\Services\ProductImageResolver;
use App\Services\ProductImageUploadService;
use App\Support\ProductImagePolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Testing\Fakes\QueueFake;
use RuntimeException;
use Tests\TestCase;

class ProductImageUploadServiceTest extends TestCase
{
    public function test_it_returns_the_preserved_source_and_queues_a_resized_webp_derivative(): void
    {
        Storage::fake('public');
        $queue = Queue::fake();

        $sourcePath = app(ProductImageUploadService::class)->store(
            UploadedFile::fake()->image('large-product.png', 2100, 210),
            'product-galleries',
        );

        $this->assertMatchesRegularExpression(
            '~^product-upload-originals/product-galleries/[0-9A-Z]{26}\.png$~',
            $sourcePath,
        );
        Storage::disk('public')->assertExists($sourcePath);

        $webpPath = app(ProductImageResolver::class)->derivativePath($sourcePath);

        $this->assertIsString($webpPath);
        Storage::disk('public')->assertMissing($webpPath);
        Queue::assertPushed(
            GenerateProductImageWebp::class,
            fn (GenerateProductImageWebp $job): bool => $job->disk === 'public'
                && $job->sourcePath === $sourcePath
                && $job->webpPath === $webpPath
                && $job->connection === config('media-library.queue_connection_name')
                && $job->queue === config('media-library.queue_name'),
        );
        Queue::assertPushed(GenerateProductImageWebp::class, 1);

        $this->runQueuedConversion($queue);

        Storage::disk('public')->assertExists($webpPath);
        $webp = $this->imageMetadata(Storage::disk('public')->path($webpPath));

        $this->assertSame('image/webp', $webp['mime']);
        $this->assertSame([2000, 200], [$webp[0], $webp[1]]);

        $sources = Storage::disk('public')->allFiles(
            ProductImagePolicy::ORIGINALS_DIRECTORY.'/product-galleries',
        );

        $this->assertSame([$sourcePath], $sources);

        $source = $this->imageMetadata(Storage::disk('public')->path($sourcePath));

        $this->assertSame('image/png', $source['mime']);
        $this->assertSame([2100, 210], [$source[0], $source[1]]);
    }

    public function test_it_does_not_upscale_smaller_images(): void
    {
        Storage::fake('public');
        $queue = Queue::fake();

        $sourcePath = app(ProductImageUploadService::class)->store(
            UploadedFile::fake()->image('small-product.jpg', 400, 200),
            'product-options/swatches',
        );

        $this->runQueuedConversion($queue);

        $webpPath = app(ProductImageResolver::class)->derivativePath($sourcePath);

        $this->assertIsString($webpPath);
        $webp = $this->imageMetadata(Storage::disk('public')->path($webpPath));

        $this->assertSame('image/webp', $webp['mime']);
        $this->assertSame([400, 200], [$webp[0], $webp[1]]);
    }

    public function test_a_failed_conversion_keeps_the_original_and_a_successful_retry_clears_the_marker(): void
    {
        Storage::fake('public');
        $queue = Queue::fake();

        $sourcePath = app(ProductImageUploadService::class)->store(
            UploadedFile::fake()->image('retry.png', 400, 200),
            'product-galleries',
        );
        $job = $queue->pushed(GenerateProductImageWebp::class)->first();

        $this->assertInstanceOf(GenerateProductImageWebp::class, $job);

        $job->failed(new RuntimeException('Test conversion failure.'));

        $resolver = app(ProductImageResolver::class);
        $markerPath = $resolver->failureMarkerPath($job->webpPath);

        Storage::disk('public')->assertExists($sourcePath);
        Storage::disk('public')->assertExists($markerPath);
        $this->assertSame(ProductImagePolicy::STATUS_FAILED, $resolver->status($sourcePath));
        $this->assertSame('/storage/'.$sourcePath, $resolver->url($sourcePath));

        $this->runConversion($job);

        Storage::disk('public')->assertExists($sourcePath);
        Storage::disk('public')->assertExists($job->webpPath);
        Storage::disk('public')->assertMissing($markerPath);
        $this->assertSame(ProductImagePolicy::STATUS_READY, $resolver->status($sourcePath));
        $this->assertSame('/storage/'.$job->webpPath, $resolver->url($sourcePath));

        $firstDerivative = Storage::disk('public')->get($job->webpPath);

        $this->runConversion($job);

        $this->assertSame($firstDerivative, Storage::disk('public')->get($job->webpPath));
        $this->assertCount(2, Storage::disk('public')->allFiles());
    }

    public function test_a_job_with_a_missing_original_exits_safely(): void
    {
        Storage::fake('public');

        $job = new GenerateProductImageWebp(
            disk: 'public',
            sourcePath: 'product-upload-originals/product-galleries/missing.png',
            webpPath: 'product-galleries/missing.webp',
        );

        $this->runConversion($job);

        Storage::disk('public')->assertMissing($job->webpPath);
        Storage::disk('public')->assertMissing(
            app(ProductImageResolver::class)->failureMarkerPath($job->webpPath),
        );
    }

    public function test_the_resolver_preserves_legacy_external_webp_and_svg_compatibility(): void
    {
        Storage::fake('public');

        $resolver = app(ProductImageResolver::class);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"></svg>';
        $svgDataUrl = 'data:image/svg+xml,%3Csvg%3E%3C%2Fsvg%3E';

        $this->assertSame('/storage/uploads/legacy%20image.jpg', $resolver->url('uploads/legacy image.jpg'));
        $this->assertSame('/storage/product-galleries/existing.webp', $resolver->url('product-galleries/existing.webp'));
        $this->assertSame('https://cdn.example.com/product.jpg', $resolver->url('https://cdn.example.com/product.jpg'));
        $this->assertSame('//cdn.example.com/product.jpg', $resolver->url('//cdn.example.com/product.jpg'));
        $this->assertSame('/images/product.jpg', $resolver->url('/images/product.jpg'));
        $this->assertSame(
            '/images/products/cotton/basic/basic-01.webp',
            $resolver->url('/images/products/cotton/basic/basic-01.png'),
        );
        $this->assertSame(
            '/images/products/cotton/basic/basic-01.webp',
            $resolver->url('images/products/cotton/basic/basic-01.png'),
        );
        $this->assertSame(
            '/images/products/cotton/basic/missing.png',
            $resolver->url('/images/products/cotton/basic/missing.png'),
        );
        $this->assertSame($svgDataUrl, $resolver->url($svgDataUrl));
        $this->assertSame('data:image/svg+xml,'.rawurlencode($svg), $resolver->url($svg));
        $this->assertSame(ProductImagePolicy::STATUS_READY, $resolver->status('product-galleries/existing.webp'));
        $this->assertSame(ProductImagePolicy::STATUS_ORIGINAL, $resolver->status('uploads/legacy.jpg'));
    }

    private function runQueuedConversion(QueueFake $queue): void
    {
        $job = $queue->pushed(GenerateProductImageWebp::class)->first();

        $this->assertInstanceOf(GenerateProductImageWebp::class, $job);
        $this->assertSame(3, $job->tries);
        $this->assertSame(180, $job->timeout);
        $this->assertTrue($job->failOnTimeout);
        $this->assertSame([10, 30, 60], $job->backoff);
        $this->runConversion($job);
    }

    private function runConversion(GenerateProductImageWebp $job): void
    {
        $job->handle(
            app(ProductImageConversionService::class),
            app(ProductImageResolver::class),
            app(MediaLibraryCatalog::class),
        );
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
