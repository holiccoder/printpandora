<?php

namespace App\Support;

use App\Models\Product;

/**
 * Stable assignments for the six postcard entries that reuse the business-
 * card option contracts. The source artwork was selected from the shared
 * 4x3 image set and copied into the public product-image directory with
 * route-safe filenames.
 */
final class PostcardProductImageCatalog
{
    /**
     * @var array<string, array{source_file: string, image: string, previous: string}>
     */
    private const DEFINITIONS = [
        'classic-standard-business-cards' => [
            'source_file' => 'beauty_edit_clean_three_sheet_4x3.png',
            'image' => '/images/products/postcards/classic-standard-postcards.png',
            'previous' => '/images/products/classic-standard-business-cards/classic-standard-business-cards-default-01.png',
        ],
        'classic-special-business-cards' => [
            'source_file' => '03_企业明信片_J2亚麻纸_4x3明亮版.png',
            'image' => '/images/products/postcards/classic-special-postcards.png',
            'previous' => '/images/classic-special-business-cards/default01.png',
        ],
        'super-standard-business-cards' => [
            'source_file' => '08_地产名片_J50珠光纸_4x3明亮版.png',
            'image' => '/images/products/postcards/super-standard-postcards.png',
            'previous' => '/images/products/super-business-cards/super-business-cards-default-01.png',
        ],
        'super-luxe-business-cards' => [
            'source_file' => '07_large_6x9in_4x3_3840x2880.png',
            'image' => '/images/products/postcards/super-luxe-postcards.png',
            'previous' => '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-01.png',
        ],
        'standard-quality-business-cards' => [
            'source_file' => '05_创意公司卡_J3颗粒纸_4x3明亮版.png',
            'image' => '/images/products/postcards/quality-standard-postcards.png',
            'previous' => '/images/products/standard-quality-business-cards/default-01.png',
        ],
        'solid-quality-business-cards' => [
            'source_file' => '05_medium_5x7in_4x3_3840x2880.png',
            'image' => '/images/products/postcards/quality-solid-postcards.png',
            'previous' => '/images/products/solid-quality-business-cards/default-01.png',
        ],
    ];

    /**
     * @return array<string, string>
     */
    public static function images(): array
    {
        return array_map(
            static fn (array $definition): string => $definition['image'],
            self::DEFINITIONS,
        );
    }

    public static function imageFor(string $productSlug): ?string
    {
        return self::DEFINITIONS[$productSlug]['image'] ?? null;
    }

    public static function isAppliedTo(Product $product): bool
    {
        $image = self::imageFor((string) $product->slug);

        return $image !== null
            && $product->getRawOriginal('featured_image') === $image;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function applyToConfig(array $config, string $productSlug): array
    {
        $image = self::imageFor($productSlug);

        if ($image === null) {
            return $config;
        }

        $config['product'] = is_array($config['product'] ?? null)
            ? $config['product']
            : [];
        $config['product']['featured_image'] = $image;
        $config['media'] = is_array($config['media'] ?? null)
            ? $config['media']
            : [];
        $config['media']['gallery'] = [$image];

        return $config;
    }

    /**
     * Apply the postcard image as the product's default storefront image.
     * Option-specific galleries remain unchanged; only the default gallery
     * and projected featured image are replaced.
     */
    public static function apply(Product $product): bool
    {
        $definition = self::DEFINITIONS[$product->slug] ?? null;

        if ($definition === null) {
            return false;
        }

        $config = self::applyToConfig(
            is_array($product->product_config) ? $product->product_config : [],
            $product->slug,
        );

        $product->forceFill([
            'featured_image' => $definition['image'],
            'product_config' => $config,
        ])->saveQuietly();

        return true;
    }

    public static function restorePrevious(Product $product): bool
    {
        $definition = self::DEFINITIONS[$product->slug] ?? null;

        if ($definition === null) {
            return false;
        }

        $config = is_array($product->product_config)
            ? $product->product_config
            : [];
        $config['product'] = is_array($config['product'] ?? null)
            ? $config['product']
            : [];
        $config['product']['featured_image'] = $definition['previous'];
        $config['media'] = is_array($config['media'] ?? null)
            ? $config['media']
            : [];
        $config['media']['gallery'] = [$definition['previous']];

        $product->forceFill([
            'featured_image' => $definition['previous'],
            'product_config' => $config,
        ])->saveQuietly();

        return true;
    }
}
