<?php

namespace App\Services;

use App\Support\ProductImagePolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Throwable;

class ProductImageUploadService
{
    /**
     * Preserve the source image and store a WebP derivative for storefront use.
     */
    public function store(
        UploadedFile $file,
        string $directory,
        string $disk = 'public',
        string $visibility = 'public',
    ): string {
        $directory = trim($directory, '/');
        $identifier = (string) Str::ulid();
        $sourceExtension = $this->sourceExtension($file);
        $sourceDirectory = $this->joinPath(
            ProductImagePolicy::ORIGINALS_DIRECTORY,
            $directory,
        );
        $sourceName = "{$identifier}.{$sourceExtension}";
        $webpPath = $this->joinPath($directory, "{$identifier}.webp");
        $temporaryWebpPath = tempnam(sys_get_temp_dir(), 'product-image-');

        if ($temporaryWebpPath === false) {
            throw new RuntimeException('Unable to create a temporary product image.');
        }

        $storage = Storage::disk($disk);
        $storedSourcePath = null;

        try {
            $sourcePath = $file->getRealPath();

            if ($sourcePath === false) {
                throw new RuntimeException('Unable to read the uploaded product image.');
            }

            Image::load($sourcePath)
                ->orientation()
                ->fit(
                    Fit::Max,
                    ProductImagePolicy::MAX_DIMENSION,
                    ProductImagePolicy::MAX_DIMENSION,
                )
                ->format('webp')
                ->quality(ProductImagePolicy::WEBP_QUALITY)
                ->save($temporaryWebpPath);

            $storedSourcePath = $storage->putFileAs(
                $sourceDirectory,
                $file,
                $sourceName,
                ['visibility' => $visibility],
            );

            if (! is_string($storedSourcePath)) {
                throw new RuntimeException('Unable to preserve the original product image.');
            }

            $webpStream = fopen($temporaryWebpPath, 'rb');

            if ($webpStream === false) {
                throw new RuntimeException('Unable to read the optimized product image.');
            }

            try {
                $stored = $storage->put(
                    $webpPath,
                    $webpStream,
                    ['visibility' => $visibility],
                );
            } finally {
                fclose($webpStream);
            }

            if (! $stored) {
                throw new RuntimeException('Unable to store the optimized product image.');
            }

            return $webpPath;
        } catch (Throwable $exception) {
            if (is_string($storedSourcePath)) {
                $storage->delete($storedSourcePath);
            }

            $storage->delete($webpPath);

            throw $exception;
        } finally {
            if (is_file($temporaryWebpPath)) {
                unlink($temporaryWebpPath);
            }
        }
    }

    private function sourceExtension(UploadedFile $file): string
    {
        return match ($file->getMimeType()) {
            'image/jpeg', 'image/jpg', 'image/pjpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new InvalidArgumentException('Only JPEG, PNG, and WebP product images are supported.'),
        };
    }

    private function joinPath(string ...$segments): string
    {
        return implode('/', array_values(array_filter(
            array_map(fn (string $segment): string => trim($segment, '/'), $segments),
            fn (string $segment): bool => $segment !== '',
        )));
    }
}
