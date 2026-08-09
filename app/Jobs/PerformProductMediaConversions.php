<?php

namespace App\Jobs;

use App\Services\MediaLibraryCatalog;
use App\Support\ProductImagePolicy;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use Throwable;

class PerformProductMediaConversions extends PerformConversionsJob
{
    public int $tries = 3;

    public int $timeout = 180;

    public bool $failOnTimeout = true;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function handle(FileManipulator $fileManipulator): bool
    {
        $this->media->forgetCustomProperty(ProductImagePolicy::MEDIA_FAILURE_PROPERTY)->save();

        $result = parent::handle($fileManipulator);

        $this->media->forgetCustomProperty(ProductImagePolicy::MEDIA_FAILURE_PROPERTY)->save();
        app(MediaLibraryCatalog::class)->invalidate();

        return $result;
    }

    public function failed(?Throwable $exception = null): void
    {
        $this->media
            ->setCustomProperty(ProductImagePolicy::MEDIA_FAILURE_PROPERTY, [
                'failed_at' => now()->toIso8601String(),
                'message' => str($exception?->getMessage() ?? 'WebP conversion failed.')->limit(500)->toString(),
            ])
            ->save();

        app(MediaLibraryCatalog::class)->invalidate();
    }
}
