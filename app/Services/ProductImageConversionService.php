<?php

namespace App\Services;

use App\Support\ProductImagePolicy;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

class ProductImageConversionService
{
    public function convert(
        string $disk,
        string $sourcePath,
        string $webpPath,
        string $visibility = 'public',
    ): void {
        $storage = Storage::disk($disk);

        if ($storage->exists($webpPath)) {
            return;
        }

        if (! $storage->exists($sourcePath)) {
            throw new RuntimeException("Product image source does not exist: {$sourcePath}");
        }

        $temporarySourcePath = tempnam(sys_get_temp_dir(), 'product-image-source-');
        $temporaryWebpPath = tempnam(sys_get_temp_dir(), 'product-image-webp-');

        if ($temporarySourcePath === false || $temporaryWebpPath === false) {
            $this->deleteTemporaryFile($temporarySourcePath);
            $this->deleteTemporaryFile($temporaryWebpPath);

            throw new RuntimeException('Unable to create temporary product image files.');
        }

        try {
            $sourceStream = $storage->readStream($sourcePath);

            if ($sourceStream === null) {
                throw new RuntimeException('Unable to read the preserved product image.');
            }

            $temporarySourceStream = fopen($temporarySourcePath, 'wb');

            if ($temporarySourceStream === false) {
                fclose($sourceStream);

                throw new RuntimeException('Unable to prepare the preserved product image.');
            }

            try {
                if (stream_copy_to_stream($sourceStream, $temporarySourceStream) === false) {
                    throw new RuntimeException('Unable to copy the preserved product image.');
                }
            } finally {
                fclose($sourceStream);
                fclose($temporarySourceStream);
            }

            Image::load($temporarySourcePath)
                ->orientation()
                ->fit(
                    Fit::Max,
                    ProductImagePolicy::MAX_DIMENSION,
                    ProductImagePolicy::MAX_DIMENSION,
                )
                ->format('webp')
                ->quality(ProductImagePolicy::WEBP_QUALITY)
                ->save($temporaryWebpPath);

            $webpStream = fopen($temporaryWebpPath, 'rb');

            if ($webpStream === false) {
                throw new RuntimeException('Unable to read the converted product image.');
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
                $storage->delete($webpPath);

                throw new RuntimeException('Unable to store the converted product image.');
            }
        } finally {
            $this->deleteTemporaryFile($temporarySourcePath);
            $this->deleteTemporaryFile($temporaryWebpPath);
        }
    }

    private function deleteTemporaryFile(string|false $path): void
    {
        if (is_string($path) && is_file($path)) {
            unlink($path);
        }
    }
}
