<?php

namespace App\Services;

use App\Models\Product;
use App\Support\HardcodedContent;
use App\Support\ProductImagePolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductImageService
{
    public const GALLERY_OVERRIDE_COLLECTION = 'product-gallery-overrides';

    public const FEATURED_OVERRIDE_COLLECTION = 'product-featured-overrides';

    /**
     * The product categories whose image configurations are managed here.
     *
     * @var array<int, string>
     */
    private const BUSINESS_CARD_CATEGORIES = [
        'business-cards',
        'cotton-business-cards',
        'pvc-business-cards',
    ];

    public function __construct(
        private HardcodedContent $content,
        private ProductConfigurationService $configuration,
    ) {}

    public function supportsBusinessCard(Product $product): bool
    {
        return in_array($product->category?->slug, self::BUSINESS_CARD_CATEGORIES, true);
    }

    /**
     * Load the same product-option source used by the storefront.
     *
     * @return array<string, mixed>|null
     */
    public function productOptions(Product $product): ?array
    {
        return $this->configuration->storefrontOptions($product);
    }

    /**
     * Return every configurable storefront gallery image for a product.
     *
     * @return array<int, array{
     *     key: string,
     *     gallery_id: string,
     *     gallery_label: string,
     *     gallery_index: int,
     *     image_index: int,
     *     source_path: string,
     *     current_url: string,
     *     is_default: bool,
     *     is_overridden: bool
     * }>
     */
    public function gallerySlots(Product $product): array
    {
        $options = $this->productOptions($product);
        $galleries = is_array($options['galleries'] ?? null) ? $options['galleries'] : [];

        if ($galleries === []) {
            $galleries = [[
                'id' => 'fallback',
                'is_default' => true,
                'images' => $this->defaultGalleryImages(),
            ]];
        }

        $overrides = $this->galleryOverrides($product);
        $slots = [];

        foreach ($galleries as $galleryIndex => $gallery) {
            if (! is_array($gallery)) {
                continue;
            }

            $galleryId = (string) ($gallery['id'] ?? "gallery-{$galleryIndex}");
            $images = is_array($gallery['images'] ?? null) ? $gallery['images'] : [];

            foreach ($images as $imageIndex => $image) {
                if (! is_string($image) || $image === '') {
                    continue;
                }

                $key = $this->gallerySlotKey($galleryId, (int) $imageIndex);
                $override = $overrides->get($key);

                $slots[] = [
                    'key' => $key,
                    'gallery_id' => $galleryId,
                    'gallery_label' => Str::headline($galleryId),
                    'gallery_index' => (int) $galleryIndex,
                    'image_index' => (int) $imageIndex,
                    'source_path' => $image,
                    'current_url' => $override instanceof Media
                        ? $this->mediaUrl($override)
                        : $this->imageUrl($image),
                    'is_default' => (bool) ($gallery['is_default'] ?? false),
                    'is_overridden' => $override instanceof Media,
                ];
            }
        }

        return $slots;
    }

    /**
     * Return the fallback gallery used by products without a product-options
     * gallery configuration, with any product-specific replacements applied.
     *
     * @return array<int, string>
     */
    public function fallbackGalleryImages(Product $product): array
    {
        $images = $this->defaultGalleryImages();
        $overrides = $this->galleryOverrides($product);

        foreach ($images as $imageIndex => &$image) {
            $override = $overrides->get($this->gallerySlotKey('fallback', $imageIndex));

            if ($override instanceof Media) {
                $image = $this->mediaUrl($override);
            }
        }

        unset($image);

        return $images;
    }

    /**
     * Replace gallery paths with product-specific media overrides.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function applyGalleryOverrides(Product $product, array $options): array
    {
        if (! is_array($options['galleries'] ?? null)) {
            return $options;
        }

        $overrides = $this->galleryOverrides($product);

        foreach ($options['galleries'] as $galleryIndex => &$gallery) {
            if (! is_array($gallery) || ! is_array($gallery['images'] ?? null)) {
                continue;
            }

            $galleryId = (string) ($gallery['id'] ?? "gallery-{$galleryIndex}");

            foreach ($gallery['images'] as $imageIndex => &$image) {
                $override = $overrides->get(
                    $this->gallerySlotKey($galleryId, (int) $imageIndex),
                );

                if ($override instanceof Media) {
                    $image = $this->mediaUrl($override);
                }
            }

            unset($image);
        }

        unset($gallery);

        return $options;
    }

    public function featuredImageUrl(Product $product): ?string
    {
        $product->unsetRelation('media');

        $override = $product
            ->getMedia(self::FEATURED_OVERRIDE_COLLECTION)
            ->sortByDesc('created_at')
            ->first();

        if ($override instanceof Media) {
            return $this->mediaUrl($override);
        }

        return $this->imageUrl($product->getRawOriginal('featured_image'));
    }

    /**
     * @param  array{key: string, gallery_id: string, image_index: int, source_path: string}  $slot
     */
    public function replaceGalleryImage(Product $product, array $slot, UploadedFile $file): Media
    {
        $media = $product
            ->addMedia($file)
            ->usingFileName($this->fileName($product, $slot['key'], $file))
            ->withCustomProperties([
                'slot_key' => $slot['key'],
                'gallery_id' => $slot['gallery_id'],
                'image_index' => $slot['image_index'],
                'source_path' => $slot['source_path'],
            ])
            ->toMediaCollection(self::GALLERY_OVERRIDE_COLLECTION);

        $this->galleryOverrides($product)
            ->filter(fn (Media $existing): bool => $existing->getKey() !== $media->getKey() && $existing->getCustomProperty('slot_key') === $slot['key'])
            ->each(fn (Media $existing): ?bool => $existing->delete());

        return $media;
    }

    /**
     * @param  array{key: string}  $slot
     */
    public function resetGalleryImage(Product $product, array $slot): void
    {
        $this->galleryOverrides($product)
            ->filter(fn (Media $existing): bool => $existing->getCustomProperty('slot_key') === $slot['key'])
            ->each(fn (Media $existing): ?bool => $existing->delete());
    }

    public function replaceFeaturedImage(Product $product, UploadedFile $file): string
    {
        $media = $product
            ->addMedia($file)
            ->usingFileName($this->fileName($product, 'featured', $file))
            ->toMediaCollection(self::FEATURED_OVERRIDE_COLLECTION);

        $url = $this->mediaUrl($media);

        $product->forceFill(['featured_image' => $url])->save();

        $product->unsetRelation('media');

        $product
            ->getMedia(self::FEATURED_OVERRIDE_COLLECTION)
            ->filter(fn (Media $existing): bool => $existing->getKey() !== $media->getKey())
            ->each(fn (Media $existing): ?bool => $existing->delete());

        return $url;
    }

    public function gallerySlotKey(string $galleryId, int $imageIndex): string
    {
        return hash('sha256', "{$galleryId}|{$imageIndex}");
    }

    /**
     * @return Collection<string, Media>
     */
    private function galleryOverrides(Product $product): Collection
    {
        // Media Library caches the morphMany relation on the model. A page
        // can read the empty collection before an upload, then upload a file
        // during the same Livewire request. Always reload it before looking
        // up an override so the new image is reflected immediately.
        $product->unsetRelation('media');

        return $product
            ->getMedia(self::GALLERY_OVERRIDE_COLLECTION)
            ->keyBy(fn (Media $media): string => (string) $media->getCustomProperty('slot_key'));
    }

    private function fileName(Product $product, string $slotKey, UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');

        return Str::slug($product->slug)."-{$slotKey}-".Str::random(8).".{$extension}";
    }

    private function mediaUrl(Media $media): string
    {
        return $media->getAvailableUrl([ProductImagePolicy::STOREFRONT_CONVERSION]);
    }

    private function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        if (Str::startsWith($path, '/')) {
            return $path;
        }

        if (Str::startsWith($path, 'storage/')) {
            return '/'.ltrim($path, '/');
        }

        if (Str::startsWith($path, [
            'product-galleries/',
            'product-options/',
            'product-featured-overrides/',
        ])) {
            return '/storage/'.ltrim($path, '/');
        }

        return asset(ltrim($path, '/'));
    }

    /**
     * @return array<int, string>
     */
    private function defaultGalleryImages(): array
    {
        $images = $this->content->section('product_detail_page.gallery_thumb_image_urls', []);

        if (! is_array($images)) {
            return [];
        }

        return array_values(array_filter(
            $images,
            fn (mixed $image): bool => is_string($image) && $image !== '',
        ));
    }
}
