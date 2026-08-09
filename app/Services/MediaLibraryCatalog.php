<?php

namespace App\Services;

use App\Models\Media;
use App\Support\ProductImagePolicy;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class MediaLibraryCatalog
{
    public const CACHE_KEY = 'filament.media-library.catalog.v2';

    public const CACHE_SECONDS = 60;

    public const ITEMS_PER_PAGE = 24;

    /**
     * @var array<string, string>
     */
    private const UPLOAD_DIRECTORIES = [
        'general' => 'uploads',
        'product_gallery' => 'product-galleries',
        'product_swatch' => 'product-options/swatches',
        'blog' => 'blog',
    ];

    /**
     * @var array<string, string>
     */
    private const PURPOSE_LABELS = [
        'general' => '通用图片',
        'product_gallery' => '产品图库',
        'product_swatch' => '产品色卡',
        'blog' => '博客图片',
        'spatie' => '产品媒体',
        'other' => '其他图片',
    ];

    public function __construct(
        private ProductImageResolver $imageResolver,
    ) {}

    /**
     * @return array<string, string>
     */
    public static function uploadPurposeOptions(): array
    {
        return array_intersect_key(self::PURPOSE_LABELS, self::UPLOAD_DIRECTORIES);
    }

    /**
     * @return array<string, string>
     */
    public static function filterPurposeOptions(): array
    {
        return self::PURPOSE_LABELS;
    }

    public static function directoryForPurpose(string $purpose): ?string
    {
        return self::UPLOAD_DIRECTORIES[$purpose] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function assets(bool $fresh = false): array
    {
        if ($fresh) {
            $this->invalidate();
        }

        /** @var array<int, array<string, mixed>> $assets */
        $assets = Cache::remember(
            self::CACHE_KEY,
            now()->addSeconds(self::CACHE_SECONDS),
            fn (): array => $this->buildAssets(),
        );

        return $assets;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id, bool $fresh = false): ?array
    {
        foreach ($this->assets($fresh) as $asset) {
            if (hash_equals((string) $asset['id'], $id)) {
                return $asset;
            }
        }

        return null;
    }

    public function delete(string $id): bool
    {
        $asset = $this->find($id, fresh: true);

        if ($asset === null) {
            return false;
        }

        $usages = app(MediaUsageService::class)->forAssets([$asset])[(string) $asset['id']] ?? [];

        if ($usages !== []) {
            return false;
        }

        $mediaId = $asset['spatie_media_id'] ?? null;

        if (is_int($mediaId) || ctype_digit((string) $mediaId)) {
            $media = Media::query()->find((int) $mediaId);

            if ($media !== null) {
                $deleted = (bool) $media->delete();
                $this->invalidate();

                return $deleted;
            }
        }

        $paths = array_values(array_filter(
            $asset['variant_paths'] ?? [],
            fn (mixed $path): bool => is_string($path) && $path !== '',
        ));

        if ($paths === []) {
            return false;
        }

        $deleted = Storage::disk('public')->delete($paths);
        $this->invalidate();

        return $deleted;
    }

    public function invalidate(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildAssets(): array
    {
        $disk = Storage::disk('public');
        $allFiles = array_values(array_unique(array_filter(
            array_map($this->normalisePath(...), $disk->allFiles()),
            fn (string $path): bool => ! $this->isExcludedPath($path),
        )));
        $allImageFiles = array_values(array_filter($allFiles, $this->isManagedImage(...)));
        $imageFiles = array_values(array_filter(
            $allImageFiles,
            fn (string $path): bool => ! $this->isGeneratedMediaPath($path),
        ));
        $imageFileSet = array_fill_keys($allImageFiles, true);
        $consumed = [];
        $assets = [];

        foreach (Media::query()->where('disk', 'public')->get() as $media) {
            $asset = $this->spatieAsset($media, $allFiles, $imageFileSet, $disk);

            if ($asset === null) {
                continue;
            }

            $assets[] = $asset;

            foreach ($asset['variant_paths'] as $path) {
                $consumed[$path] = true;
            }
        }

        $originalsPrefix = ProductImagePolicy::ORIGINALS_DIRECTORY.'/';

        foreach ($imageFiles as $sourcePath) {
            if (isset($consumed[$sourcePath]) || ! Str::startsWith($sourcePath, $originalsPrefix)) {
                continue;
            }

            $relativeSource = Str::after($sourcePath, $originalsPrefix);
            $derivativePath = preg_replace('/\.[^.\/]+$/', '.webp', $relativeSource) ?? $relativeSource;
            $failureMarkerPath = $this->imageResolver->failureMarkerPath($derivativePath);
            $hasDerivative = isset($imageFileSet[$derivativePath]) && ! isset($consumed[$derivativePath]);
            $primaryPath = $hasDerivative ? $derivativePath : $sourcePath;
            $variants = $hasDerivative ? [$primaryPath, $sourcePath] : [$sourcePath];

            if ($disk->exists($failureMarkerPath)) {
                $variants[] = $failureMarkerPath;
            }

            $assets[] = $this->makeAsset(
                disk: $disk,
                primaryPath: $primaryPath,
                variantPaths: $variants,
                purposePath: $relativeSource,
                sourcePath: $sourcePath,
            );

            foreach ($variants as $path) {
                $consumed[$path] = true;
            }
        }

        foreach ($imageFiles as $path) {
            if (isset($consumed[$path])) {
                continue;
            }

            $assets[] = $this->makeAsset(
                disk: $disk,
                primaryPath: $path,
                variantPaths: [$path],
            );
        }

        usort(
            $assets,
            fn (array $left, array $right): int => ($right['modified_at'] <=> $left['modified_at'])
                ?: strnatcasecmp((string) $right['name'], (string) $left['name']),
        );

        return $assets;
    }

    /**
     * @param  array<int, string>  $allFiles
     * @param  array<string, bool>  $imageFileSet
     * @return array<string, mixed>|null
     */
    private function spatieAsset(
        Media $media,
        array $allFiles,
        array $imageFileSet,
        FilesystemAdapter $disk,
    ): ?array {
        try {
            $originalPath = $this->normalisePath($media->getPathRelativeToRoot());
        } catch (Throwable) {
            return null;
        }

        $root = Str::contains($originalPath, '/')
            ? Str::beforeLast($originalPath, '/')
            : '';
        $variants = array_values(array_filter(
            $allFiles,
            fn (string $path): bool => $path === $originalPath
                || ($root !== '' && Str::startsWith($path, $root.'/')),
        ));

        if ($variants === []) {
            return null;
        }

        $primaryPath = isset($imageFileSet[$originalPath]) ? $originalPath : null;
        $conversionDisk = $media->conversions_disk ?: $media->disk;

        if ($conversionDisk === 'public' && $media->hasGeneratedConversion(ProductImagePolicy::STOREFRONT_CONVERSION)) {
            try {
                $conversionPath = $this->normalisePath(
                    $media->getPathRelativeToRoot(ProductImagePolicy::STOREFRONT_CONVERSION),
                );

                if (isset($imageFileSet[$conversionPath])) {
                    $primaryPath = $conversionPath;
                }
            } catch (Throwable) {
                // Fall back to the original or another image in the media directory.
            }
        }

        $primaryPath ??= collect($variants)->first($this->isManagedImage(...));

        if (! is_string($primaryPath)) {
            return null;
        }

        return $this->makeAsset(
            disk: $disk,
            primaryPath: $primaryPath,
            variantPaths: $variants,
            purposePath: $primaryPath,
            sourcePath: isset($imageFileSet[$originalPath]) ? $originalPath : null,
            overrides: [
                'name' => $media->name ?: $media->file_name,
                'file_name' => $media->file_name,
                'purpose' => 'spatie',
                'purpose_label' => self::PURPOSE_LABELS['spatie'],
                'spatie_media_id' => (int) $media->getKey(),
                'spatie_collection' => $media->collection_name,
            ],
        );
    }

    /**
     * @param  array<int, string>  $variantPaths
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function makeAsset(
        FilesystemAdapter $disk,
        string $primaryPath,
        array $variantPaths,
        ?string $purposePath = null,
        ?string $sourcePath = null,
        array $overrides = [],
    ): array {
        $variantPaths = array_values(array_unique(array_filter(
            array_map($this->normalisePath(...), $variantPaths),
            fn (string $path): bool => $path !== '' && $disk->exists($path),
        )));
        $primarySize = $this->fileSize($disk, $primaryPath);
        $totalSize = array_sum(array_map(
            fn (string $path): int => $this->fileSize($disk, $path),
            $variantPaths,
        ));
        $modifiedAt = max(array_map(
            fn (string $path): int => $this->lastModified($disk, $path),
            $variantPaths,
        ) ?: [0]);
        [$width, $height] = $this->dimensions($disk, $primaryPath);
        $purpose = $this->purposeForPath($purposePath ?? $primaryPath);
        $canonicalPath = $sourcePath ?? $primaryPath;
        $resolvedPath = $this->imageResolver->preferredPath($canonicalPath);
        $relativeUrl = $this->relativeUrl($resolvedPath);
        $conversionStatus = $this->imageResolver->status($canonicalPath);

        return array_replace([
            'id' => hash('sha256', "public\0{$canonicalPath}"),
            'disk' => 'public',
            'name' => basename($primaryPath),
            'file_name' => basename($primaryPath),
            'primary_path' => $primaryPath,
            'source_path' => $sourcePath,
            'variant_paths' => $variantPaths,
            'variant_count' => count($variantPaths),
            'url' => $relativeUrl,
            'absolute_url' => url($relativeUrl),
            'extension' => strtolower((string) pathinfo($primaryPath, PATHINFO_EXTENSION)),
            'mime_type' => $this->mimeType($primaryPath),
            'width' => $width,
            'height' => $height,
            'dimensions' => $width !== null && $height !== null ? "{$width} × {$height}" : '尺寸未知',
            'size' => $primarySize,
            'total_size' => $totalSize,
            'formatted_size' => self::formatBytes($primarySize),
            'formatted_total_size' => self::formatBytes($totalSize),
            'modified_at' => $modifiedAt,
            'modified_at_label' => $modifiedAt > 0 ? date('Y-m-d H:i', $modifiedAt) : '未知',
            'purpose' => $purpose,
            'purpose_label' => self::PURPOSE_LABELS[$purpose],
            'conversion_status' => $conversionStatus,
            'conversion_status_label' => $this->imageResolver->statusLabel($conversionStatus),
            'has_original' => $sourcePath !== null,
            'spatie_media_id' => null,
            'spatie_collection' => null,
        ], $overrides);
    }

    private function purposeForPath(string $path): string
    {
        $path = Str::after($path, ProductImagePolicy::ORIGINALS_DIRECTORY.'/');

        return match (true) {
            Str::startsWith($path, 'product-galleries/') => 'product_gallery',
            Str::startsWith($path, 'product-options/swatches/') => 'product_swatch',
            Str::startsWith($path, 'blog/') => 'blog',
            Str::startsWith($path, 'uploads/') => 'general',
            default => 'other',
        };
    }

    private function isExcludedPath(string $path): bool
    {
        return $path === '' || Str::startsWith($path, [
            'livewire-tmp/',
            'temp-uploads/',
        ]);
    }

    private function isManagedImage(string $path): bool
    {
        return in_array(strtolower((string) pathinfo($path, PATHINFO_EXTENSION)), [
            'jpg',
            'jpeg',
            'png',
            'webp',
        ], true);
    }

    private function isGeneratedMediaPath(string $path): bool
    {
        $path = '/'.$this->normalisePath($path).'/';

        return Str::contains($path, [
            '/conversions/',
            '/responsive-images/',
        ]);
    }

    private function normalisePath(string $path): string
    {
        return ltrim(str_replace('\\', '/', $path), '/');
    }

    private function relativeUrl(string $path): string
    {
        return '/storage/'.implode('/', array_map(rawurlencode(...), explode('/', $path)));
    }

    private function mimeType(string $path): string
    {
        return match (strtolower((string) pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }

    private function fileSize(FilesystemAdapter $disk, string $path): int
    {
        try {
            return (int) $disk->size($path);
        } catch (Throwable) {
            return 0;
        }
    }

    private function lastModified(FilesystemAdapter $disk, string $path): int
    {
        try {
            return (int) $disk->lastModified($path);
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function dimensions(FilesystemAdapter $disk, string $path): array
    {
        try {
            $dimensions = @getimagesize($disk->path($path));

            if (is_array($dimensions)) {
                return [(int) $dimensions[0], (int) $dimensions[1]];
            }
        } catch (Throwable) {
            // Corrupt or remotely stored images still belong in the catalog.
        }

        return [null, null];
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / (1024 * 1024), 1).' MB';
    }
}
