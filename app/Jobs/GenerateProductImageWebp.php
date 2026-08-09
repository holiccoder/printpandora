<?php

namespace App\Jobs;

use App\Services\MediaLibraryCatalog;
use App\Services\ProductImageConversionService;
use App\Services\ProductImageResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class GenerateProductImageWebp implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public bool $failOnTimeout = true;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public string $disk,
        public string $sourcePath,
        public string $webpPath,
        public string $visibility = 'public',
    ) {}

    public function handle(
        ProductImageConversionService $converter,
        ProductImageResolver $resolver,
        MediaLibraryCatalog $catalog,
    ): void {
        $storage = Storage::disk($this->disk);
        $storage->delete($resolver->failureMarkerPath($this->webpPath));

        if (! $storage->exists($this->sourcePath)) {
            $catalog->invalidate();

            return;
        }

        $converter->convert(
            disk: $this->disk,
            sourcePath: $this->sourcePath,
            webpPath: $this->webpPath,
            visibility: $this->visibility,
        );

        $storage->delete($resolver->failureMarkerPath($this->webpPath));
        $catalog->invalidate();
    }

    public function failed(?Throwable $exception): void
    {
        $storage = Storage::disk($this->disk);

        if (! $storage->exists($this->sourcePath) || $storage->exists($this->webpPath)) {
            app(MediaLibraryCatalog::class)->invalidate();

            return;
        }

        $markerPath = app(ProductImageResolver::class)->failureMarkerPath($this->webpPath);
        $payload = json_encode([
            'failed_at' => now()->toIso8601String(),
            'message' => Str::limit($exception?->getMessage() ?? 'WebP conversion failed.', 500),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! is_string($payload)) {
            $payload = '{"message":"WebP conversion failed."}';
        }

        $storage->put($markerPath, $payload.PHP_EOL);

        app(MediaLibraryCatalog::class)->invalidate();
    }
}
