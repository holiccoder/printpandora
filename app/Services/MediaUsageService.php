<?php

namespace App\Services;

use App\Models\DesignServiceRequest;
use App\Models\Media;
use App\Models\Post;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

final class MediaUsageService
{
    /**
     * @param  array<int, array<string, mixed>>  $assets
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function forAssets(array $assets): array
    {
        $usages = [];
        $pathMap = [];
        $mediaAssetIds = [];

        foreach ($assets as $asset) {
            $assetId = (string) $asset['id'];
            $usages[$assetId] = [];

            foreach ($asset['variant_paths'] ?? [] as $path) {
                if (! is_string($path)) {
                    continue;
                }

                $normalised = $this->normaliseReference($path);

                if ($normalised !== null) {
                    $pathMap[$normalised][] = $assetId;
                }
            }

            $mediaId = $asset['spatie_media_id'] ?? null;

            if (is_int($mediaId) || ctype_digit((string) $mediaId)) {
                $mediaAssetIds[(int) $mediaId] = $assetId;
            }
        }

        if ($assets === []) {
            return $usages;
        }

        Product::query()
            ->select([
                'id',
                'name',
                'featured_image',
                'product_config',
                'product_options',
                'subtitle',
                'description',
                'bullet_points',
            ])
            ->each(function (Product $product) use (&$usages, $pathMap): void {
                $this->addReferencedUsages(
                    usages: $usages,
                    pathMap: $pathMap,
                    value: $product->featured_image,
                    usage: $this->usage(
                        type: 'product',
                        recordId: $product->getKey(),
                        label: $product->name,
                        location: '产品主图',
                        routeName: 'filament.admin.resources.products.edit',
                    ),
                );
                $this->addReferencedUsages(
                    $usages,
                    $pathMap,
                    $product->product_config,
                    $this->usage('product', $product->getKey(), $product->name, '产品配置', 'filament.admin.resources.products.edit'),
                );
                $this->addReferencedUsages(
                    $usages,
                    $pathMap,
                    $product->product_options,
                    $this->usage('product', $product->getKey(), $product->name, '旧版产品选项', 'filament.admin.resources.products.edit'),
                );
                $this->addReferencedUsages(
                    $usages,
                    $pathMap,
                    [$product->subtitle, $product->description, $product->bullet_points],
                    $this->usage('product', $product->getKey(), $product->name, '产品内容', 'filament.admin.resources.products.edit'),
                );
            });

        Post::query()
            ->select(['id', 'title', 'featured_image', 'body'])
            ->each(function (Post $post) use (&$usages, $pathMap): void {
                $this->addReferencedUsages(
                    $usages,
                    $pathMap,
                    $post->featured_image,
                    $this->usage('post', $post->getKey(), $post->title, '博客封面', 'filament.admin.resources.posts.edit'),
                );
                $this->addReferencedUsages(
                    $usages,
                    $pathMap,
                    $post->body,
                    $this->usage('post', $post->getKey(), $post->title, '博客正文', 'filament.admin.resources.posts.edit'),
                );
            });

        DesignServiceRequest::query()
            ->select(['id', 'business_name', 'email', 'logo_path', 'example_paths'])
            ->each(function (DesignServiceRequest $request) use (&$usages, $pathMap): void {
                $label = $request->business_name ?: $request->email;
                $usage = $this->usage(
                    'design_service_request',
                    $request->getKey(),
                    $label,
                    '设计服务附件',
                    'filament.admin.resources.design-service-requests.edit',
                );

                $this->addReferencedUsages(
                    $usages,
                    $pathMap,
                    [$request->logo_path, $request->example_paths],
                    $usage,
                );
            });

        if ($mediaAssetIds !== []) {
            Media::query()
                ->with('model')
                ->whereKey(array_keys($mediaAssetIds))
                ->get()
                ->each(function (Media $media) use (&$usages, $mediaAssetIds): void {
                    $owner = $media->model;

                    if (! $owner instanceof Model) {
                        return;
                    }

                    $assetId = $mediaAssetIds[(int) $media->getKey()];
                    $ownerName = $owner->getAttribute('name')
                        ?? $owner->getAttribute('title')
                        ?? $owner->getAttribute('email')
                        ?? '#'.$owner->getKey();
                    $routeName = $owner instanceof Product
                        ? 'filament.admin.resources.products.edit'
                        : null;

                    $this->appendUsage($usages[$assetId], $this->usage(
                        type: 'spatie',
                        recordId: $owner->getKey(),
                        label: class_basename($owner).' · '.$ownerName,
                        location: '媒体集合：'.$media->collection_name,
                        routeName: $routeName,
                    ));
                });
        }

        foreach ($usages as &$assetUsages) {
            usort(
                $assetUsages,
                fn (array $left, array $right): int => strnatcasecmp(
                    (string) $left['location'].(string) $left['label'],
                    (string) $right['location'].(string) $right['label'],
                ),
            );
        }

        unset($assetUsages);

        return $usages;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $usages
     * @param  array<string, array<int, string>>  $pathMap
     * @param  array<string, mixed>  $usage
     */
    private function addReferencedUsages(
        array &$usages,
        array $pathMap,
        mixed $value,
        array $usage,
    ): void {
        foreach ($this->referencedAssetIds($value, $pathMap) as $assetId) {
            $this->appendUsage($usages[$assetId], $usage);
        }
    }

    /**
     * @param  array<string, array<int, string>>  $pathMap
     * @return array<int, string>
     */
    private function referencedAssetIds(mixed $value, array $pathMap): array
    {
        $assetIds = [];

        foreach ($this->stringValues($value) as $string) {
            $decoded = rawurldecode(html_entity_decode($string));
            $normalised = $this->normaliseReference($decoded);

            if ($normalised !== null && isset($pathMap[$normalised])) {
                foreach ($pathMap[$normalised] as $assetId) {
                    $assetIds[$assetId] = true;
                }
            }

            foreach ($pathMap as $path => $ids) {
                if (! str_contains($decoded, $path) && ! str_contains($decoded, '/storage/'.$path)) {
                    continue;
                }

                foreach ($ids as $assetId) {
                    $assetIds[$assetId] = true;
                }
            }
        }

        return array_keys($assetIds);
    }

    /**
     * @return array<int, string>
     */
    private function stringValues(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        $strings = [];

        array_walk_recursive($value, function (mixed $item) use (&$strings): void {
            if (is_string($item)) {
                $strings[] = $item;
            }
        });

        return $strings;
    }

    private function normaliseReference(string $value): ?string
    {
        $value = trim(rawurldecode(html_entity_decode($value)), " \t\n\r\0\x0B\"'");

        if ($value === '' || str_starts_with($value, 'data:')) {
            return null;
        }

        if (preg_match('~^https?://~i', $value) === 1) {
            $path = parse_url($value, PHP_URL_PATH);

            if (! is_string($path) || ! str_contains($path, '/storage/')) {
                return null;
            }

            $value = $path;
        }

        $value = str_replace('\\', '/', $value);
        $value = preg_split('/[?#]/', $value, 2)[0] ?? $value;

        if (str_contains($value, '/storage/')) {
            $value = explode('/storage/', $value, 2)[1];
        } elseif (str_starts_with($value, 'storage/')) {
            $value = substr($value, strlen('storage/'));
        }

        $value = ltrim($value, '/');

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<int, array<string, mixed>>  $usages
     * @param  array<string, mixed>  $usage
     */
    private function appendUsage(array &$usages, array $usage): void
    {
        $key = implode('|', [
            (string) $usage['type'],
            (string) $usage['record_id'],
            (string) $usage['location'],
        ]);

        foreach ($usages as $existing) {
            $existingKey = implode('|', [
                (string) $existing['type'],
                (string) $existing['record_id'],
                (string) $existing['location'],
            ]);

            if ($existingKey === $key) {
                return;
            }
        }

        $usages[] = $usage;
    }

    /**
     * @return array<string, mixed>
     */
    private function usage(
        string $type,
        int|string $recordId,
        string $label,
        string $location,
        ?string $routeName,
    ): array {
        return [
            'type' => $type,
            'record_id' => $recordId,
            'label' => $label,
            'location' => $location,
            'url' => $routeName !== null && Route::has($routeName)
                ? route($routeName, ['record' => $recordId], absolute: false)
                : null,
        ];
    }
}
