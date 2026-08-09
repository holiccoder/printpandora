<?php

namespace App\Services;

use App\Jobs\GenerateProductImageWebp;
use App\Support\ProductImagePolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ProductImageUploadService
{
    public function __construct(
        private ProductImageResolver $resolver,
        private MediaLibraryCatalog $catalog,
    ) {}

    /**
     * Preserve the source image and queue a WebP derivative for storefront use.
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
        $storage = Storage::disk($disk);
        $storedSourcePath = null;

        try {
            $storedSourcePath = $storage->putFileAs(
                $sourceDirectory,
                $file,
                $sourceName,
                ['visibility' => $visibility],
            );

            if (! is_string($storedSourcePath)) {
                throw new RuntimeException('Unable to preserve the original product image.');
            }

            $storage->delete($this->resolver->failureMarkerPath($webpPath));

            GenerateProductImageWebp::dispatch(
                disk: $disk,
                sourcePath: $storedSourcePath,
                webpPath: $webpPath,
                visibility: $visibility,
            )
                ->onConnection(config('media-library.queue_connection_name', 'database'))
                ->onQueue(config('media-library.queue_name', 'default'));

            $this->catalog->invalidate();

            return $storedSourcePath;
        } catch (Throwable $exception) {
            if (is_string($storedSourcePath)) {
                $storage->delete($storedSourcePath);
            }

            $storage->delete([
                $webpPath,
                $this->resolver->failureMarkerPath($webpPath),
            ]);

            throw $exception;
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
