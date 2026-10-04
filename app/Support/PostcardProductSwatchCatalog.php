<?php

namespace App\Support;

/**
 * Postcard option swatch assignments.
 *
 * These paths point to the original swatch assets. Option thumbnails are
 * synchronized independently from postcard media gallery and primary rules.
 */
final class PostcardProductSwatchCatalog
{
    /**
     * @var array<string, string>
     */
    private const SUPER_LUXE_TEXTURE_SWATCHES = [
        'inkpavo_j1' => '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j1.png',
        'inkpavo_j2' => '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j2.png',
        'inkpavo_j3' => '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j3.png',
        'inkpavo_j4' => '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j4.png',
        'inkpavo_j5' => '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j5.png',
        'inkpavo_j6' => '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j6.png',
        'inkpavo_j7' => '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j7.png',
        'inkpavo_j8' => '/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j8.png',
    ];

    /**
     * @var array<string, string>
     */
    private const SUPER_LUXE_PAPER_FINISH_SWATCHES = [
        'matte' => '/images/product-options/business-cards/laminates/matte-526x251.jpg',
        'gloss' => '/images/product-options/business-cards/laminates/gloss-526x251.jpg',
    ];

    /**
     * @var array<string, string>
     */
    private const QUALITY_SOLID_PAPER_FINISH_SWATCHES = [
        'matte' => '/images/product-options/business-cards/laminates/matte-526x251.jpg',
        'gloss' => '/images/product-options/business-cards/laminates/gloss-526x251.jpg',
        'starry_film' => '/images/product-options/business-cards/swatches/quality/starlight-film.png',
        'holo_film' => '/images/product-options/business-cards/swatches/quality/holographic-film.png',
        'soft_touch_film' => '/images/product-options/business-cards/swatches/quality/soft-touch-film.png',
    ];

    /**
     * @return array<string, array<string, array<string, string>>>
     */
    private const SWATCHES = [
        'super-luxe-postcards' => [
            'texture' => self::SUPER_LUXE_TEXTURE_SWATCHES,
            'paper_finish' => self::SUPER_LUXE_PAPER_FINISH_SWATCHES,
        ],
        'quality-solid-postcards' => [
            'paper_finish' => self::QUALITY_SOLID_PAPER_FINISH_SWATCHES,
        ],
    ];

    /**
     * Restore postcard option swatches while leaving media galleries and
     * their primary images untouched.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function synchronizeConfig(array $config, string $productSlug): array
    {
        $options = is_array($config['options'] ?? null) ? $config['options'] : [];
        $options = self::synchronizeOptions($options, $productSlug);

        if ($options !== []) {
            $config['options'] = $options;
        }

        return $config;
    }

    /**
     * Restore swatches in either canonical option groups or legacy flat
     * option arrays.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function synchronizeOptions(array $options, string $productSlug): array
    {
        $groups = self::SWATCHES[$productSlug] ?? [];

        foreach ($groups as $groupKey => $swatches) {
            if (! array_key_exists($groupKey, $options) || ! is_array($options[$groupKey])) {
                continue;
            }

            $group = $options[$groupKey];
            $isCanonicalGroup = array_key_exists('values', $group);
            $values = $isCanonicalGroup ? $group['values'] : $group;

            if (! is_array($values)) {
                continue;
            }

            foreach ($values as &$value) {
                if (! is_array($value)) {
                    continue;
                }

                $code = (string) ($value['code'] ?? '');

                if (isset($swatches[$code])) {
                    $value['swatch_image'] = $swatches[$code];
                }
            }
            unset($value);

            if ($isCanonicalGroup) {
                $group['values'] = array_values($values);
                $options[$groupKey] = $group;
            } else {
                $options[$groupKey] = array_values($values);
            }
        }

        return $options;
    }

    /**
     * @return array<string, array<string, array<string, string>>>
     */
    public static function swatchesFor(string $productSlug): array
    {
        return self::SWATCHES[$productSlug] ?? [];
    }

    /**
     * @return list<string>
     */
    public static function swatchesForProductSlugs(): array
    {
        return array_keys(self::SWATCHES);
    }
}
