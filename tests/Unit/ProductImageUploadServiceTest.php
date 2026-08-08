<?php

namespace Tests\Unit;

use App\Services\ProductImageUploadService;
use App\Support\ProductImagePolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageUploadServiceTest extends TestCase
{
    public function test_it_preserves_the_source_and_stores_a_resized_webp_derivative(): void
    {
        Storage::fake('public');

        $path = app(ProductImageUploadService::class)->store(
            UploadedFile::fake()->image('large-product.png', 2100, 210),
            'product-galleries',
        );

        $this->assertMatchesRegularExpression(
            '/^product-galleries\/[0-9A-Z]{26}\.webp$/',
            $path,
        );
        Storage::disk('public')->assertExists($path);

        $webp = $this->imageMetadata(Storage::disk('public')->path($path));

        $this->assertSame('image/webp', $webp['mime']);
        $this->assertSame([2000, 200], [$webp[0], $webp[1]]);

        $sources = Storage::disk('public')->allFiles(
            ProductImagePolicy::ORIGINALS_DIRECTORY.'/product-galleries',
        );

        $this->assertCount(1, $sources);

        $source = $this->imageMetadata(Storage::disk('public')->path($sources[0]));

        $this->assertSame('image/png', $source['mime']);
        $this->assertSame([2100, 210], [$source[0], $source[1]]);
    }

    public function test_it_does_not_upscale_smaller_images(): void
    {
        Storage::fake('public');

        $path = app(ProductImageUploadService::class)->store(
            UploadedFile::fake()->image('small-product.jpg', 400, 200),
            'product-options/swatches',
        );

        $webp = $this->imageMetadata(Storage::disk('public')->path($path));

        $this->assertSame('image/webp', $webp['mime']);
        $this->assertSame([400, 200], [$webp[0], $webp[1]]);
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
