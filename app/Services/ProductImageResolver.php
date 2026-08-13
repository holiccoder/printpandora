<?php

namespace App\Services;

use App\Models\Media;
use App\Support\ProductImagePolicy;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProductImageResolver
{
    /** @var array<string, Media|null> */
    private array $mediaBySourcePath = [];

    public function url(mixed $image, string $disk = 'public'): mixed
    {
        if (! is_string($image) || $image === '') {
            return $image;
        }

        if (Str::startsWith(ltrim($image), '<svg')) {
            return 'data:image/svg+xml,'.rawurlencode($image);
        }

        if (Str::startsWith($image, ['http://', 'https://', '//', 'data:'])) {
            return $image;
        }

        if (Str::startsWith($image, ['/images/products/', 'images/products/'])) {
            return $this->publicProductImageUrl($image);
        }

        if (Str::startsWith($image, '/')) {
            return $image;
        }

        $path = $this->normaliseStoragePath($image);
        $preferredPath = $this->preferredPath($path, $disk);

        return '/storage/'.implode('/', array_map(rawurlencode(...), explode('/', $preferredPath)));
    }

    public function preferredPath(string $path, string $disk = 'public'): string
    {
        $path = $this->normaliseStoragePath($path);
        $derivativePath = $this->derivativePath($path);

        if ($derivativePath !== null && Storage::disk($disk)->exists($derivativePath)) {
            return $derivativePath;
        }

        $media = $this->mediaForSourcePath($path, $disk);

        if ($media !== null && $media->hasGeneratedConversion(ProductImagePolicy::STOREFRONT_CONVERSION)) {
            try {
                return ltrim(str_replace(
                    '\\',
                    '/',
                    $media->getPathRelativeToRoot(ProductImagePolicy::STOREFRONT_CONVERSION),
                ), '/');
            } catch (Throwable) {
                return $path;
            }
        }

        return $path;
    }

    public function derivativePath(string $sourcePath): ?string
    {
        $sourcePath = $this->normaliseStoragePath($sourcePath);
        $prefix = ProductImagePolicy::ORIGINALS_DIRECTORY.'/';

        if (! Str::startsWith($sourcePath, $prefix)) {
            return null;
        }

        $relativeSource = Str::after($sourcePath, $prefix);

        return preg_replace('/\.[^.\/]+$/', '.webp', $relativeSource) ?: null;
    }

    public function failureMarkerPath(string $webpPath): string
    {
        return $this->normaliseStoragePath($webpPath).ProductImagePolicy::FAILURE_MARKER_SUFFIX;
    }

    private function publicProductImageUrl(string $image): string
    {
        $url = '/'.ltrim(str_replace('\\', '/', $image), '/');
        $parsedUrl = parse_url($url);
        $path = is_array($parsedUrl) && is_string($parsedUrl['path'] ?? null)
            ? $parsedUrl['path']
            : $url;

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return $url;
        }

        $webpPath = preg_replace('/\\.(?:jpe?g|png)$/i', '.webp', $path);

        if (! is_string($webpPath) || ! is_file(public_path(ltrim($webpPath, '/')))) {
            return $url;
        }

        $suffix = '';

        if (is_array($parsedUrl) && isset($parsedUrl['query'])) {
            $suffix .= '?'.$parsedUrl['query'];
        }

        if (is_array($parsedUrl) && isset($parsedUrl['fragment'])) {
            $suffix .= '#'.$parsedUrl['fragment'];
        }

        return $webpPath.$suffix;
    }

    public function status(string $path, string $disk = 'public'): string
    {
        $path = $this->normaliseStoragePath($path);
        $storage = Storage::disk($disk);
        $derivativePath = $this->derivativePath($path);

        if ($derivativePath !== null) {
            if ($storage->exists($derivativePath)) {
                return ProductImagePolicy::STATUS_READY;
            }

            if ($storage->exists($this->failureMarkerPath($derivativePath))) {
                return ProductImagePolicy::STATUS_FAILED;
            }

            return ProductImagePolicy::STATUS_PROCESSING;
        }

        $media = $this->mediaForSourcePath($path, $disk);

        if ($media !== null && in_array($media->collection_name, ProductImagePolicy::MEDIA_COLLECTIONS, true)) {
            if ($media->hasGeneratedConversion(ProductImagePolicy::STOREFRONT_CONVERSION)) {
                return ProductImagePolicy::STATUS_READY;
            }

            if (filled($media->getCustomProperty(ProductImagePolicy::MEDIA_FAILURE_PROPERTY))) {
                return ProductImagePolicy::STATUS_FAILED;
            }

            return ProductImagePolicy::STATUS_PROCESSING;
        }

        return strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) === 'webp'
            ? ProductImagePolicy::STATUS_READY
            : ProductImagePolicy::STATUS_ORIGINAL;
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            ProductImagePolicy::STATUS_PROCESSING => '处理中',
            ProductImagePolicy::STATUS_READY => '已就绪',
            ProductImagePolicy::STATUS_FAILED => '转换失败',
            default => '原图',
        };
    }

    private function normaliseStoragePath(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        return Str::startsWith($path, 'storage/')
            ? Str::after($path, 'storage/')
            : $path;
    }

    private function mediaForSourcePath(string $path, string $disk): ?Media
    {
        $cacheKey = $disk."\0".$path;

        if (array_key_exists($cacheKey, $this->mediaBySourcePath)) {
            return $this->mediaBySourcePath[$cacheKey];
        }

        $firstSegment = Str::before($path, '/');

        if (! ctype_digit($firstSegment)) {
            return $this->mediaBySourcePath[$cacheKey] = null;
        }

        $media = Media::query()
            ->whereKey((int) $firstSegment)
            ->where('disk', $disk)
            ->first();

        if ($media === null) {
            return $this->mediaBySourcePath[$cacheKey] = null;
        }

        try {
            $mediaSourcePath = ltrim(str_replace('\\', '/', $media->getPathRelativeToRoot()), '/');
        } catch (Throwable) {
            return $this->mediaBySourcePath[$cacheKey] = null;
        }

        return $this->mediaBySourcePath[$cacheKey] = $mediaSourcePath === $path ? $media : null;
    }
}
