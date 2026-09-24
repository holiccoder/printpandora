<?php

namespace App\Support;

final class SolidQualityBusinessCardGallery
{
    public const PRODUCT_SLUG = 'solid-quality-business-cards';

    /**
     * @var array<int, string>
     */
    public const DEFAULT_GALLERY = [
        '/images/products/solid-quality-business-cards/default-01.png',
        '/images/products/solid-quality-business-cards/default-02.png',
        '/images/products/solid-quality-business-cards/default-03.png',
        '/images/products/solid-quality-business-cards/default-04.png',
    ];

    /**
     * These assets are product-specific. Hot-foil values continue to use the
     * shared business-card artwork.
     *
     * @var array<string, string>
     */
    public const COLD_FOIL_IMAGES = [
        'cold_matte_gold' => '/images/products/solid-quality-business-cards/cold-foil/cold-matte-gold.png',
        'cold_matte_silver' => '/images/products/solid-quality-business-cards/cold-foil/cold-matte-silver.png',
        'cold_bright_gold' => '/images/products/solid-quality-business-cards/cold-foil/cold-bright-gold.png',
        'cold_bright_silver' => '/images/products/solid-quality-business-cards/cold-foil/cold-bright-silver.png',
        'cold_red_gold' => '/images/products/solid-quality-business-cards/cold-foil/cold-red-gold.png',
        'cold_green_gold' => '/images/products/solid-quality-business-cards/cold-foil/cold-green-gold.png',
        'cold_blue_gold' => '/images/products/solid-quality-business-cards/cold-foil/cold-blue-gold.png',
    ];

    /**
     * Shared swatches are used for the option tiles. Product-specific
     * artwork remains reserved for the selected gallery images above.
     *
     * @var array<string, string>
     */
    private const COLD_FOIL_SWATCH_IMAGES = [
        'cold_matte_gold' => '/images/product-options/business-cards/swatches/cold/matte-gold.png',
        'cold_matte_silver' => '/images/product-options/business-cards/swatches/cold/matte-silver.png',
        'cold_bright_gold' => '/images/product-options/business-cards/swatches/cold/bright-gold.png',
        'cold_bright_silver' => '/images/product-options/business-cards/swatches/cold/bright-silver.png',
        'cold_red_gold' => '/images/product-options/business-cards/swatches/cold/red-gold.png',
        'cold_green_gold' => '/images/product-options/business-cards/swatches/cold/green-gold.png',
        'cold_blue_gold' => '/images/product-options/business-cards/swatches/cold/blue-gold.png',
    ];

    /**
     * @var array<string, string>
     */
    private const PAPER_FINISH_SWATCH_IMAGES = [
        'starry_film' => '/images/product-options/business-cards/swatches/quality/starlight-film.png',
        'holo_film' => '/images/product-options/business-cards/swatches/quality/holographic-film.png',
        'soft_touch_film' => '/images/product-options/business-cards/swatches/quality/soft-touch-film.png',
    ];

    /**
     * @var array<string, string>
     */
    private const PAPER_FINISH_PRIMARY_IMAGES = [
        'starry_film' => '/images/products/solid-quality-business-cards/texture/starlight-film-primary.png',
        'holo_film' => '/images/products/solid-quality-business-cards/texture/laser-film-primary.png',
        'soft_touch_film' => '/images/products/solid-quality-business-cards/texture/soft-touch-film-primary.png',
    ];

    private const UV_FINISH_SWATCH_IMAGE = '/images/product-options/uv-swatch.png';

    private const UV_GALLERY_IMAGE = '/images/products/classic-solid/user-3d-uv.png';

    /**
     * Return the managed default, UV, and paper-finish rules for this product.
     * Hot-foil and other product-specific rules are preserved.
     *
     * @return array<int, array{id: string, is_default?: bool, match: array<string, string>, images: array<int, string>, primary: string}>
     */
    public static function rules(): array
    {
        return [
            [
                'id' => 'default',
                'is_default' => true,
                'match' => [],
                'images' => self::DEFAULT_GALLERY,
                'primary' => self::DEFAULT_GALLERY[0],
            ],
            self::uvRule('3d-uv-single-side', 'single_side_uv'),
            self::uvRule('3d-uv-both-sides', 'both_sides_uv'),
            self::surfaceRule(
                'standard-matte-square',
                'standard',
                'square',
                'matte',
                'standard-matte-square',
            ),
            self::surfaceRule(
                'standard-matte-rounded',
                'standard',
                'rounded',
                'matte',
                'standard-matte-rounded',
            ),
            self::surfaceRule(
                'standard-gloss-rounded',
                'standard',
                'rounded',
                'gloss',
                'standard-gloss-rounded',
            ),
            self::paperFinishRule(
                'standard-starlight-film',
                'standard',
                'square',
                'starry_film',
                'texture/starlight-film',
            ),
            self::paperFinishRule(
                'standard-laser-film',
                'standard',
                'square',
                'holo_film',
                'texture/laser-film',
            ),
            self::paperFinishRule(
                'standard-soft-touch-film',
                'standard',
                'square',
                'soft_touch_film',
                'texture/soft-touch-film',
            ),
        ];
    }

    /**
     * Normalize solid-quality option values in a canonical configuration.
     * Stable codes are retained so existing carts and saved configurations
     * continue to resolve to the same choices.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function synchronizeOptions(array $options): array
    {
        // Solid-quality cards expose these choices as Paper Finish. Remove
        // the retired duplicate Texture group from older canonical rows.
        unset($options['texture']);

        $options = BusinessCardOptionCatalog::normalizeSharedSizeSwatches(
            $options,
            self::PRODUCT_SLUG,
        );
        $options = self::synchronizeCanonicalSizes($options);
        $options = self::synchronizeCanonicalPaperFinish($options);
        $options = self::synchronizeCanonicalUvFinish($options);
        $options = self::synchronizeCanonicalColdFoilSwatches($options);

        return $options;
    }

    /**
     * Apply the same contract to the legacy flat option source.
     *
     * @param  array<string, mixed>  $legacy
     * @return array<string, mixed>
     */
    public static function synchronizeLegacyOptions(array $legacy): array
    {
        $canonical = [];

        foreach (['sizes', 'paper_finish', 'uv_finish', 'special_finish'] as $groupKey) {
            if (is_array($legacy[$groupKey] ?? null)) {
                $canonical[$groupKey] = [
                    'values' => array_values($legacy[$groupKey]),
                ];
            }
        }

        $canonical = self::synchronizeOptions($canonical);

        foreach (array_keys($canonical) as $groupKey) {
            $values = $canonical[$groupKey]['values'] ?? null;

            if (! is_array($values)) {
                continue;
            }

            $legacy[$groupKey] = array_values(array_map(
                static function (mixed $value): mixed {
                    if (! is_array($value)) {
                        return $value;
                    }

                    if (array_key_exists('label', $value)) {
                        $value['name'] = $value['label'];
                        unset($value['label']);
                    }

                    return $value;
                },
                $values,
            ));
        }

        return $legacy;
    }

    /**
     * Replace stale solid-quality default, UV, and finish rules while
     * preserving unrelated special-finish rules.
     *
     * @param  array<int, mixed>  $existingRules
     * @return array<int, array<string, mixed>>
     */
    public static function synchronizeRules(array $existingRules): array
    {
        $preservedRules = array_values(array_filter(
            $existingRules,
            static fn (mixed $rule): bool => is_array($rule)
                && ! self::isManagedRule($rule)
                && ! self::isManagedColdFoilRule($rule),
        ));

        return [...self::rules(), ...$preservedRules, ...self::coldFoilRules()];
    }

    /**
     * Synchronize the gallery and featured image in a canonical product
     * configuration.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function synchronizeConfig(array $config): array
    {
        $config['options'] = self::synchronizeOptions(
            is_array($config['options'] ?? null) ? $config['options'] : [],
        );

        $media = is_array($config['media'] ?? null) ? $config['media'] : [];
        $existingRules = is_array($media['gallery_rules'] ?? null)
            ? $media['gallery_rules']
            : [];

        $media['gallery'] = self::DEFAULT_GALLERY;
        $media['gallery_rules'] = self::synchronizeRules($existingRules);
        $config['media'] = $media;
        $config['product'] = is_array($config['product'] ?? null) ? $config['product'] : [];
        $config['product']['featured_image'] = self::DEFAULT_GALLERY[0];

        return $config;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function synchronizeCanonicalSizes(array $options): array
    {
        if (! is_array($options['sizes'] ?? null)) {
            return $options;
        }

        $isCanonicalGroup = array_key_exists('values', $options['sizes']);
        $values = $isCanonicalGroup ? ($options['sizes']['values'] ?? null) : $options['sizes'];

        if (! is_array($values)) {
            return $options;
        }

        $hasCustom = false;

        foreach ($values as &$value) {
            if (! is_array($value)) {
                continue;
            }

            $code = (string) ($value['code'] ?? '');

            if ($code === 'standard') {
                $value['swatch_image'] = BusinessCardOptionCatalog::STANDARD_SIZE_SWATCH_IMAGE;
            } elseif ($code === 'square') {
                $value['swatch_image'] = BusinessCardOptionCatalog::SQUARE_SIZE_SWATCH_IMAGE;
            } elseif ($code === 'custom') {
                $hasCustom = true;
                $value['description'] = BusinessCardOptionCatalog::CUSTOM_SIZE_DESCRIPTION;
                $value['swatch_image'] = BusinessCardOptionCatalog::CUSTOM_SIZE_SWATCH_IMAGE;
            }
        }
        unset($value);

        if (! $hasCustom) {
            $values[] = [
                'code' => 'custom',
                'label' => 'Custom',
                'description' => BusinessCardOptionCatalog::CUSTOM_SIZE_DESCRIPTION,
                'swatch_image' => BusinessCardOptionCatalog::CUSTOM_SIZE_SWATCH_IMAGE,
            ];
        }

        if ($isCanonicalGroup) {
            $options['sizes']['values'] = array_values($values);
        } else {
            $options['sizes'] = array_values($values);
        }

        return $options;
    }

    /**
     * Normalize legacy solid-quality UV values to the same two side choices
     * used by standard-quality business cards.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function synchronizeCanonicalUvFinish(array $options): array
    {
        if (! is_array($options['uv_finish'] ?? null)) {
            return $options;
        }

        $isCanonicalGroup = array_key_exists('values', $options['uv_finish']);
        $values = $isCanonicalGroup
            ? ($options['uv_finish']['values'] ?? null)
            : $options['uv_finish'];

        if (! is_array($values)) {
            return $options;
        }

        $existingByCode = [];

        foreach ($values as $value) {
            if (! is_array($value)) {
                continue;
            }

            $code = self::normalizeUvCode($value['code'] ?? $value['label'] ?? $value['name'] ?? null);

            if ($code !== null) {
                $existingByCode[$code] = $value;
            }
        }

        $normalizedValues = [];

        foreach ([
            'single_side_uv' => 'single side',
            'both_sides_uv' => 'both sides',
        ] as $code => $label) {
            $normalizedValues[] = array_replace(
                $existingByCode[$code] ?? [],
                [
                    'code' => $code,
                    'label' => $label,
                    'description' => '',
                    'swatch_image' => self::UV_FINISH_SWATCH_IMAGE,
                ],
            );
        }

        if ($isCanonicalGroup) {
            $options['uv_finish'] = array_replace(
                $options['uv_finish'],
                [
                    'label' => '3D UV',
                    'type' => 'select',
                    'required' => false,
                    'default' => null,
                    'values' => $normalizedValues,
                ],
            );
        } else {
            $options['uv_finish'] = array_values($normalizedValues);
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function synchronizeCanonicalPaperFinish(array $options): array
    {
        if (! is_array($options['paper_finish'] ?? null)) {
            return $options;
        }

        $isCanonicalGroup = array_key_exists('values', $options['paper_finish']);
        $values = $isCanonicalGroup
            ? ($options['paper_finish']['values'] ?? null)
            : $options['paper_finish'];

        if (! is_array($values)) {
            return $options;
        }

        $labels = [
            'matte' => 'Matte',
            'gloss' => 'Gloss',
            'starry_film' => 'Starlight Film',
            'soft_touch_film' => 'Soft-Touch Film',
            'holo_film' => 'Laser Film',
        ];

        foreach ($values as &$value) {
            if (! is_array($value)) {
                continue;
            }

            $code = (string) ($value['code'] ?? '');

            if (isset($labels[$code])) {
                $value['label'] = $labels[$code];
            }

            if (isset(self::PAPER_FINISH_SWATCH_IMAGES[$code])) {
                $value['swatch_image'] = self::PAPER_FINISH_SWATCH_IMAGES[$code];
            }
        }
        unset($value);

        if ($isCanonicalGroup) {
            $options['paper_finish']['values'] = array_values($values);
        } else {
            $options['paper_finish'] = array_values($values);
        }

        return $options;
    }

    /**
     * @return array{id: string, match: array<string, string>, images: array<int, string>, primary: string}
     */
    private static function uvRule(string $id, string $uvFinish): array
    {
        return [
            'id' => $id,
            'match' => [
                'uv_finish' => $uvFinish,
            ],
            'images' => [self::UV_GALLERY_IMAGE],
            'primary' => self::UV_GALLERY_IMAGE,
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function synchronizeCanonicalColdFoilSwatches(array $options): array
    {
        if (! is_array($options['special_finish'] ?? null)) {
            return $options;
        }

        $isCanonicalGroup = array_key_exists('values', $options['special_finish']);
        $values = $isCanonicalGroup
            ? ($options['special_finish']['values'] ?? null)
            : $options['special_finish'];

        if (! is_array($values)) {
            return $options;
        }

        foreach ($values as &$value) {
            if (! is_array($value)) {
                continue;
            }

            $code = (string) ($value['code'] ?? '');

            if (isset(self::COLD_FOIL_SWATCH_IMAGES[$code])) {
                $value['swatch_image'] = self::COLD_FOIL_SWATCH_IMAGES[$code];
            }
        }
        unset($value);

        if ($isCanonicalGroup) {
            $options['special_finish']['values'] = array_values($values);
        } else {
            $options['special_finish'] = array_values($values);
        }

        return $options;
    }

    /**
     * @param  array<string, string>  $match
     * @return array{id: string, match: array<string, string>, images: array<int, string>, primary: string}
     */
    private static function surfaceRule(
        string $id,
        string $size,
        string $corner,
        string $paperFinish,
        string $assetName,
    ): array {
        return self::rule(
            $id,
            [
                'sizes' => $size,
                'corners' => $corner,
                'paper_finish' => $paperFinish,
            ],
            $assetName,
        );
    }

    /**
     * @param  array<string, string>  $match
     * @return array{id: string, match: array<string, string>, images: array<int, string>, primary: string}
     */
    private static function paperFinishRule(
        string $id,
        string $size,
        string $corner,
        string $paperFinish,
        string $assetName,
    ): array {
        return self::rule(
            $id,
            [
                'sizes' => $size,
                'corners' => $corner,
                'paper_finish' => $paperFinish,
            ],
            $assetName,
            self::PAPER_FINISH_PRIMARY_IMAGES[$paperFinish] ?? null,
        );
    }

    /**
     * @param  array<string, string>  $match
     * @return array{id: string, match: array<string, string>, images: array<int, string>, primary: string}
     */
    private static function rule(
        string $id,
        array $match,
        string $assetName,
        ?string $primary = null,
    ): array
    {
        $image = "/images/products/solid-quality-business-cards/{$assetName}.png";

        return [
            'id' => $id,
            'match' => $match,
            'images' => [$image],
            'primary' => $primary ?? $image,
        ];
    }

    /**
     * @return array<int, array{id: string, match: array<string, string>, images: array<int, string>, primary: string}>
     */
    private static function coldFoilRules(): array
    {
        return array_map(
            static fn (string $code, string $image): array => [
                'id' => "shared-foil-{$code}",
                'match' => ['special_finish' => $code],
                'images' => [$image],
                'primary' => $image,
            ],
            array_keys(self::COLD_FOIL_IMAGES),
            array_values(self::COLD_FOIL_IMAGES),
        );
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private static function isManagedRule(array $rule): bool
    {
        $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

        if (($rule['id'] ?? null) === 'default' || $match === []) {
            return true;
        }

        $specialFinish = self::normalize($match['special_finish'] ?? null);

        if ($specialFinish !== '' && $specialFinish !== 'no-special-finish') {
            return false;
        }

        $paperFinish = self::normalize($match['paper_finish'] ?? null);
        $uvFinish = self::normalize($match['uv_finish'] ?? null);

        return in_array($paperFinish, [
            'matte',
            'gloss',
            'starry-film',
            'starlight-film',
            'soft-touch-film',
            'holo-film',
            'laser-film',
        ], true)
            || in_array($uvFinish, [
                'no-3d-uv',
                '3d-uv',
                'single-side-uv',
                'both-sides-uv',
            ], true);
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private static function isManagedColdFoilRule(array $rule): bool
    {
        $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

        return isset(self::COLD_FOIL_IMAGES[(string) ($match['special_finish'] ?? '')])
            || in_array(
                (string) ($rule['id'] ?? ''),
                array_map(
                    static fn (string $code): string => "shared-foil-{$code}",
                    array_keys(self::COLD_FOIL_IMAGES),
                ),
                true,
            );
    }

    private static function normalize(mixed $value): string
    {
        return strtolower(str_replace(['_', ' '], '-', trim((string) $value)));
    }

    private static function normalizeUvCode(mixed $value): ?string
    {
        return match (str_replace(['-', ' ', '_'], '', strtolower(trim((string) $value)))) {
            'no3duv', 'singleside', 'singlesideuv' => 'single_side_uv',
            '3duv', 'bothsides', 'bothsidesuv' => 'both_sides_uv',
            default => null,
        };
    }
}
