<?php

namespace App\Support;

use App\Models\Product;

/**
 * Stable image assignments for the paper-based business-card and postcard
 * products represented by the supplied cards/postcards artwork folder.
 */
final class CardsAndPostcardsProductImageCatalog
{
    /**
     * @var array<string, list<string>>
     */
    private const BUSINESS_CARD_GALLERIES = [
        'classic-standard-business-cards' => [
            '/images/products/classic-standard-business-cards/classic-standard-business-cards-default-01.png',
            '/images/products/classic-standard-business-cards/classic-standard-business-cards-default-02.png',
            '/images/products/classic-standard-business-cards/classic-standard-business-cards-default-03.png',
            '/images/products/classic-standard-business-cards/classic-standard-business-cards-default-04.png',
        ],
        'classic-special-business-cards' => [
            '/images/classic-special-business-cards/default01.png',
            '/images/classic-special-business-cards/default02.png',
            '/images/classic-special-business-cards/default03.png',
            '/images/classic-special-business-cards/default04.png',
        ],
        'standard-quality-business-cards' => [
            '/images/products/standard-quality-business-cards/default-01.png',
            '/images/products/standard-quality-business-cards/default-02.png',
            '/images/products/standard-quality-business-cards/default-03.png',
            '/images/products/standard-quality-business-cards/default-04.png',
        ],
        'solid-quality-business-cards' => [
            '/images/products/solid-quality-business-cards/default-01.png',
            '/images/products/solid-quality-business-cards/default-02.png',
            '/images/products/solid-quality-business-cards/default-03.png',
            '/images/products/solid-quality-business-cards/default-04.png',
        ],
        'super-standard-business-cards' => [
            '/images/products/super-business-cards/super-business-cards-default-01.png',
            '/images/products/super-business-cards/super-business-cards-default-02.png',
            '/images/products/super-business-cards/super-business-cards-default-03.png',
            '/images/products/super-business-cards/super-business-cards-default-04.png',
        ],
        'super-luxe-business-cards' => [
            '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-01.png',
            '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-02.png',
            '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-03.png',
            '/images/products/super-luxe-business-cards/super-luxe-business-cards-default-04.png',
        ],
    ];

    /**
     * @var array<string, list<string>>
     */
    private const POSTCARD_GALLERIES = [
        'classic-standard-postcards' => [
            '/images/products/postcards/gallery/classic-standard-01.png',
            '/images/products/postcards/gallery/classic-standard-02.png',
            '/images/products/postcards/gallery/classic-standard-03.png',
            '/images/products/postcards/gallery/classic-standard-04.png',
        ],
        'classic-special-postcards' => [
            '/images/products/postcards/gallery/classic-special-01.png',
            '/images/products/postcards/gallery/classic-special-02.png',
            '/images/products/postcards/gallery/classic-special-03.png',
            '/images/products/postcards/gallery/classic-special-04.png',
        ],
        'super-standard-postcards' => [
            '/images/products/postcards/gallery/super-standard-01.png',
            '/images/products/postcards/gallery/super-standard-02.png',
            '/images/products/postcards/gallery/super-standard-03.png',
            '/images/products/postcards/gallery/super-standard-04.png',
        ],
        'super-luxe-postcards' => [
            '/images/products/postcards/gallery/super-luxe-01.png',
            '/images/products/postcards/gallery/super-luxe-02.png',
            '/images/products/postcards/gallery/super-luxe-03.png',
            '/images/products/postcards/gallery/super-luxe-04.png',
        ],
        'quality-standard-postcards' => [
            '/images/products/postcards/gallery/quality-standard-01.png',
            '/images/products/postcards/gallery/quality-standard-02.png',
            '/images/products/postcards/gallery/quality-standard-03.png',
            '/images/products/postcards/gallery/quality-standard-04.png',
        ],
        'quality-solid-postcards' => [
            '/images/products/postcards/gallery/quality-solid-01.png',
            '/images/products/postcards/gallery/quality-solid-02.png',
            '/images/products/postcards/gallery/quality-solid-03.png',
            '/images/products/postcards/gallery/quality-solid-04.png',
        ],
    ];

    /**
     * Stable featured-image URLs retained for existing storefront references.
     * The product gallery itself is maintained separately above.
     *
     * @var array<string, string>
     */
    private const POSTCARD_FEATURED_IMAGES = [
        'classic-standard-postcards' => '/images/products/postcards/classic-standard-postcards.png',
        'classic-special-postcards' => '/images/products/postcards/classic-special-postcards.png',
        'super-standard-postcards' => '/images/products/postcards/super-standard-postcards.png',
        'super-luxe-postcards' => '/images/products/postcards/super-luxe-postcards.png',
        'quality-standard-postcards' => '/images/products/postcards/quality-standard-postcards.png',
        'quality-solid-postcards' => '/images/products/postcards/quality-solid-postcards.png',
    ];

    /**
     * Postcard finish artwork is intentionally separate from the option
     * swatches. The group names match the canonical option contracts copied
     * from each corresponding business-card product.
     *
     * @var array<string, array{group: string, gloss: string, matte: string, rounded: string}>
     */
    private const POSTCARD_FINISH_IMAGES = [
        'classic-standard-postcards' => [
            'group' => 'paper_finish',
            'gloss' => '/images/products/postcards/finishes/classic-standard-gloss.png',
            'matte' => '/images/products/postcards/finishes/classic-standard-matte.png',
            'rounded' => '/images/products/postcards/finishes/classic-standard-rounded.png',
        ],
        'quality-standard-postcards' => [
            'group' => 'texture',
            'gloss' => '/images/products/postcards/finishes/quality-standard-gloss.png',
            'matte' => '/images/products/postcards/finishes/quality-standard-matte.png',
            'rounded' => '/images/products/postcards/finishes/quality-standard-rounded.png',
        ],
        'quality-solid-postcards' => [
            'group' => 'paper_finish',
            'gloss' => '/images/products/postcards/finishes/quality-solid-gloss.png',
            'matte' => '/images/products/postcards/finishes/quality-solid-matte.png',
            'rounded' => '/images/products/postcards/finishes/quality-solid-rounded.png',
        ],
    ];

    /**
     * Texture artwork for Classic Special Postcards. These paths are gallery
     * primaries only; the texture option swatches stay on their existing
     * business-card artwork.
     *
     * @var array<string, array<string, string>>
     */
    private const POSTCARD_TEXTURE_IMAGES = [
        'classic-special-postcards' => [
            'white_cardstock' => '/images/products/postcards/textures/classic-special-white-cardstock.png',
            'eggshell_paper' => '/images/products/postcards/textures/classic-special-eggshell-paper.png',
            'linen_paper' => '/images/products/postcards/textures/classic-special-linen-paper.png',
            'water_ripple_paper' => '/images/products/postcards/textures/classic-special-water-ripple-paper.png',
        ],
    ];

    /**
     * Shared postcard cold-foil primary artwork, keyed by the option code.
     *
     * @var array<string, string>
     */
    private const POSTCARD_COLD_FOIL_IMAGES = [
        'cold_red_gold' => '/images/products/postcards/foil/cold/cold-red-gold.png',
        'cold_blue_gold' => '/images/products/postcards/foil/cold/cold-blue-gold.png',
        'cold_bright_gold' => '/images/products/postcards/foil/cold/cold-bright-gold.png',
        'cold_bright_silver' => '/images/products/postcards/foil/cold/cold-bright-silver.png',
        'cold_green_gold' => '/images/products/postcards/foil/cold/cold-green-gold.png',
        'cold_matte_gold' => '/images/products/postcards/foil/cold/cold-matte-gold.png',
        'cold_matte_silver' => '/images/products/postcards/foil/cold/cold-matte-silver.png',
    ];

    /**
     * Shared postcard hot-foil primary artwork, keyed by the option code.
     * The supplied hot-foil folder has no separate black-gold image, so that
     * option is deliberately left unchanged.
     *
     * @var array<string, string>
     */
    private const POSTCARD_HOT_FOIL_IMAGES = [
        'aged_gold' => '/images/products/postcards/foil/hot/hot-aged-gold.png',
        'blue_gold' => '/images/products/postcards/foil/hot/hot-blue-gold.png',
        'bright_gold' => '/images/products/postcards/foil/hot/hot-bright-gold.png',
        'bright_silver' => '/images/products/postcards/foil/hot/hot-bright-silver.png',
        'green_gold' => '/images/products/postcards/foil/hot/hot-green-gold.png',
        'laser_silver' => '/images/products/postcards/foil/hot/hot-laser-silver.png',
        'matte_gold' => '/images/products/postcards/foil/hot/hot-matte-gold.png',
        'matte_silver' => '/images/products/postcards/foil/hot/hot-matte-silver.png',
        'muted_purple_gold' => '/images/products/postcards/foil/hot/hot-muted-purple-gold.png',
        'red_gold' => '/images/products/postcards/foil/hot/hot-red-gold.png',
        'rose_gold' => '/images/products/postcards/foil/hot/hot-rose-gold.png',
    ];

    /**
     * @var array<string, string>
     */
    private const POSTCARD_BY_BUSINESS_CARD = [
        'classic-standard-business-cards' => 'classic-standard-postcards',
        'classic-special-business-cards' => 'classic-special-postcards',
        'standard-quality-business-cards' => 'quality-standard-postcards',
        'solid-quality-business-cards' => 'quality-solid-postcards',
        'super-standard-business-cards' => 'super-standard-postcards',
        'super-luxe-business-cards' => 'super-luxe-postcards',
    ];

    /**
     * @return list<string>
     */
    public static function businessCardSlugs(): array
    {
        return array_keys(self::BUSINESS_CARD_GALLERIES);
    }

    /**
     * @return list<string>
     */
    public static function postcardSlugs(): array
    {
        return array_keys(self::POSTCARD_GALLERIES);
    }

    /**
     * @return list<string>|null
     */
    public static function businessCardGalleryFor(string $productSlug): ?array
    {
        return self::BUSINESS_CARD_GALLERIES[$productSlug] ?? null;
    }

    /**
     * @return list<string>|null
     */
    public static function postcardGalleryFor(string $productSlug): ?array
    {
        return self::POSTCARD_GALLERIES[$productSlug] ?? null;
    }

    public static function postcardFeaturedImageFor(string $productSlug): ?string
    {
        return self::POSTCARD_FEATURED_IMAGES[$productSlug] ?? null;
    }

    /**
     * Return the conditional primary-image rules requested for one postcard.
     *
     * @param  array<string, mixed>  $options
     * @return list<array{id: string, match: array<string, string>, images: list<string>, primary: string}>
     */
    public static function postcardOptionGalleryRulesFor(string $productSlug, array $options = []): array
    {
        if (! isset(self::POSTCARD_GALLERIES[$productSlug])) {
            return [];
        }

        $rules = [];
        $finish = self::POSTCARD_FINISH_IMAGES[$productSlug] ?? null;

        if ($finish !== null) {
            foreach (['gloss', 'matte'] as $finishCode) {
                $image = $finish[$finishCode];
                $rules[] = self::galleryRule(
                    "postcard-{$productSlug}-{$finishCode}",
                    [
                        $finish['group'] => $finishCode,
                        'corners' => 'square',
                    ],
                    $image,
                );
            }

            $rules[] = self::galleryRule(
                "postcard-{$productSlug}-rounded",
                ['corners' => 'rounded'],
                $finish['rounded'],
            );
        }

        foreach (self::POSTCARD_TEXTURE_IMAGES[$productSlug] ?? [] as $textureCode => $image) {
            $rules[] = self::galleryRule(
                "postcard-{$productSlug}-texture-{$textureCode}",
                ['texture' => $textureCode],
                $image,
            );
        }

        $foilGroups = ['special_finish'];

        if (self::optionGroupHasValues($options, 'hot_foil')) {
            $foilGroups[] = 'hot_foil';
        }

        foreach ($foilGroups as $groupKey) {
            foreach (self::POSTCARD_COLD_FOIL_IMAGES + self::POSTCARD_HOT_FOIL_IMAGES as $code => $image) {
                $rules[] = self::galleryRule(
                    "postcard-{$productSlug}-{$groupKey}-{$code}",
                    [$groupKey => $code],
                    $image,
                );
            }
        }

        return $rules;
    }

    public static function postcardSlugForBusinessCard(string $productSlug): ?string
    {
        return self::POSTCARD_BY_BUSINESS_CARD[$productSlug] ?? null;
    }

    public static function businessCardSlugForPostcard(string $productSlug): ?string
    {
        $businessCardSlug = array_search($productSlug, self::POSTCARD_BY_BUSINESS_CARD, true);

        return is_string($businessCardSlug) ? $businessCardSlug : null;
    }

    /**
     * Apply postcard defaults and all requested conditional primary images.
     * Existing unrelated gallery rules are retained.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function synchronizePostcardConfig(array $config, string $productSlug): array
    {
        $gallery = self::postcardGalleryFor($productSlug);

        if ($gallery === null) {
            return $config;
        }

        $config = self::synchronizeConfig(
            $config,
            $gallery,
            self::postcardFeaturedImageFor($productSlug),
        );
        $options = is_array($config['options'] ?? null) ? $config['options'] : [];
        $media = is_array($config['media'] ?? null) ? $config['media'] : [];
        $rules = is_array($media['gallery_rules'] ?? null) ? $media['gallery_rules'] : [];
        $rules = array_values(array_filter(
            $rules,
            static fn (mixed $rule): bool => self::keepPostcardGalleryRule($rule, $productSlug),
        ));

        $media['gallery_rules'] = [
            ...$rules,
            ...self::postcardOptionGalleryRulesFor($productSlug, $options),
        ];
        $config['media'] = $media;

        return $config;
    }

    /**
     * Apply a default gallery without removing product-specific option rules.
     * The first empty-match/default rule is updated; option-specific rules are
     * retained as-is.
     *
     * @param  array<string, mixed>  $config
     * @param  list<string>  $gallery
     * @return array<string, mixed>
     */
    public static function synchronizeConfig(array $config, array $gallery, ?string $featuredImage = null): array
    {
        $gallery = array_values(array_filter($gallery, static fn (mixed $image): bool => is_string($image) && $image !== ''));

        if ($gallery === []) {
            return $config;
        }

        $config['product'] = is_array($config['product'] ?? null)
            ? $config['product']
            : [];
        $config['product']['featured_image'] = $featuredImage ?: $gallery[0];

        $media = is_array($config['media'] ?? null) ? $config['media'] : [];
        $media['gallery'] = $gallery;
        $rules = is_array($media['gallery_rules'] ?? null) ? array_values($media['gallery_rules']) : [];
        $hasDefaultRule = false;

        foreach ($rules as &$rule) {
            if (! is_array($rule)) {
                continue;
            }

            $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

            if ($hasDefaultRule || (($rule['id'] ?? null) !== 'default' && $match !== [])) {
                continue;
            }

            $hasDefaultRule = true;
            $rule['id'] = 'default';
            $rule['match'] = [];
            $rule['images'] = $gallery;
            $rule['primary'] = $gallery[0];
        }
        unset($rule);

        if (! $hasDefaultRule) {
            array_unshift($rules, [
                'id' => 'default',
                'match' => [],
                'images' => $gallery,
                'primary' => $gallery[0],
            ]);
        }

        $media['gallery_rules'] = $rules;
        $config['media'] = $media;

        return $config;
    }

    public static function applyBusinessCard(Product $product): bool
    {
        $gallery = self::businessCardGalleryFor((string) $product->slug);

        if ($gallery === null) {
            return false;
        }

        $config = self::synchronizeConfig(
            is_array($product->product_config) ? $product->product_config : [],
            $gallery,
        );

        $product->forceFill([
            'featured_image' => $gallery[0],
            'product_config' => $config,
        ])->saveQuietly();

        return true;
    }

    public static function applyPostcard(Product $product): bool
    {
        $gallery = self::postcardGalleryFor((string) $product->slug);

        if ($gallery === null) {
            return false;
        }

        $config = self::synchronizePostcardConfig(
            is_array($product->product_config) ? $product->product_config : [],
            (string) $product->slug,
        );

        $product->forceFill([
            'featured_image' => self::postcardFeaturedImageFor((string) $product->slug) ?? $gallery[0],
            'product_config' => $config,
        ])->saveQuietly();

        return true;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private static function optionGroupHasValues(array $options, string $groupKey): bool
    {
        $group = $options[$groupKey] ?? null;

        if (! is_array($group)) {
            return false;
        }

        $values = array_key_exists('values', $group) ? $group['values'] : $group;

        return is_array($values) && $values !== [];
    }

    private static function keepPostcardGalleryRule(mixed $rule, string $productSlug): bool
    {
        if (! is_array($rule)) {
            return false;
        }

        $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];
        $foilCodes = array_keys(self::POSTCARD_COLD_FOIL_IMAGES + self::POSTCARD_HOT_FOIL_IMAGES);

        foreach (['special_finish', 'hot_foil'] as $groupKey) {
            if (in_array(self::normalizedOptionCode($match[$groupKey] ?? null), $foilCodes, true)) {
                return false;
            }
        }

        foreach (array_keys(self::POSTCARD_TEXTURE_IMAGES[$productSlug] ?? []) as $textureCode) {
            if (self::normalizedOptionCode($match['texture'] ?? null) === $textureCode) {
                return false;
            }
        }

        $finish = self::POSTCARD_FINISH_IMAGES[$productSlug] ?? null;

        if ($finish !== null) {
            $finishCode = self::normalizedOptionCode($match[$finish['group']] ?? null);

            if (in_array($finishCode, ['gloss', 'matte'], true)) {
                return false;
            }

            if (
                self::normalizedOptionCode($match['corners'] ?? null) === 'rounded'
                && ! array_key_exists('texture', $match)
                && ! array_key_exists('special_finish', $match)
                && ! array_key_exists('hot_foil', $match)
                && ! array_key_exists('uv_finish', $match)
            ) {
                return false;
            }
        }

        return true;
    }

    private static function normalizedOptionCode(mixed $value): string
    {
        return is_scalar($value)
            ? str_replace(['-', ' '], '_', strtolower(trim((string) $value)))
            : '';
    }

    /**
     * @return array{id: string, match: array<string, string>, images: list<string>, primary: string}
     */
    private static function galleryRule(string $id, array $match, string $image): array
    {
        return [
            'id' => $id,
            'match' => $match,
            'images' => [$image],
            'primary' => $image,
        ];
    }
}
