<?php

namespace App\Support;

final class ClassicStandardBusinessCardGallery
{
    public const PRODUCT_SLUG = 'classic-standard-business-cards';

    /**
     * @var array<int, string>
     */
    public const DEFAULT_GALLERY = [
        '/images/products/classic-standard-business-cards/classic-standard-business-cards-default-01.png',
        '/images/products/classic-standard-business-cards/classic-standard-business-cards-default-02.png',
        '/images/products/classic-standard-business-cards/classic-standard-business-cards-default-03.png',
        '/images/products/classic-standard-business-cards/classic-standard-business-cards-default-04.png',
    ];

    /**
     * Return the managed default and option-specific rules for this product.
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
            self::rule(
                'standard-matte-square',
                ['sizes' => 'standard', 'paper_finish' => 'matte', 'corners' => 'square'],
                'standard-matte-square',
            ),
            self::rule(
                'square-matte-square',
                ['sizes' => 'square', 'paper_finish' => 'matte', 'corners' => 'square'],
                'square-matte-square',
            ),
            self::rule(
                'standard-matte-rounded',
                ['sizes' => 'standard', 'paper_finish' => 'matte', 'corners' => 'rounded'],
                'standard-matte-rounded',
            ),
            self::rule(
                'square-matte-rounded',
                ['sizes' => 'square', 'paper_finish' => 'matte', 'corners' => 'rounded'],
                'square-matte-rounded',
            ),
            self::rule(
                'standard-gloss-square',
                ['sizes' => 'standard', 'paper_finish' => 'gloss', 'corners' => 'square'],
                'standard-gloss-square',
            ),
            self::rule(
                'square-gloss-square',
                ['sizes' => 'square', 'paper_finish' => 'gloss', 'corners' => 'square'],
                'square-gloss-square',
            ),
            self::rule(
                'standard-gloss-rounded',
                ['sizes' => 'standard', 'paper_finish' => 'gloss', 'corners' => 'rounded'],
                'standard-gloss-rounded',
            ),
            self::rule(
                'square-gloss-rounded',
                ['sizes' => 'square', 'paper_finish' => 'gloss', 'corners' => 'rounded'],
                'square-gloss-rounded',
            ),
            self::rule(
                'standard-uv-square-single-side',
                ['sizes' => 'standard', 'uv_finish' => 'single_side_uv', 'corners' => 'square'],
                'standard-uv-square',
            ),
            self::rule(
                'standard-uv-square-both-sides',
                ['sizes' => 'standard', 'uv_finish' => 'both_sides_uv', 'corners' => 'square'],
                'standard-uv-square',
            ),
            self::rule(
                'standard-uv-rounded-single-side',
                ['sizes' => 'standard', 'uv_finish' => 'single_side_uv', 'corners' => 'rounded'],
                'standard-uv-rounded',
            ),
            self::rule(
                'standard-uv-rounded-both-sides',
                ['sizes' => 'standard', 'uv_finish' => 'both_sides_uv', 'corners' => 'rounded'],
                'standard-uv-rounded',
            ),
            self::rule(
                'square-uv-square-single-side',
                ['sizes' => 'square', 'uv_finish' => 'single_side_uv', 'corners' => 'square'],
                'square-uv-square',
            ),
            self::rule(
                'square-uv-square-both-sides',
                ['sizes' => 'square', 'uv_finish' => 'both_sides_uv', 'corners' => 'square'],
                'square-uv-square',
            ),
            self::rule(
                'square-uv-rounded-single-side',
                ['sizes' => 'square', 'uv_finish' => 'single_side_uv', 'corners' => 'rounded'],
                'square-uv-rounded',
            ),
            self::rule(
                'square-uv-rounded-both-sides',
                ['sizes' => 'square', 'uv_finish' => 'both_sides_uv', 'corners' => 'rounded'],
                'square-uv-rounded',
            ),
        ];
    }

    /**
     * Replace the old unqualified and square-only classic gallery rules while
     * preserving product-specific rules such as foil galleries.
     *
     * @param  array<int, mixed>  $existingRules
     * @return array<int, array<string, mixed>>
     */
    public static function synchronizeRules(array $existingRules): array
    {
        $preservedRules = array_values(array_filter(
            $existingRules,
            static fn (mixed $rule): bool => is_array($rule)
                && ! self::isManagedRule($rule),
        ));

        return [...self::rules(), ...$preservedRules];
    }

    /**
     * Synchronize the managed gallery portion of a canonical product config.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function synchronizeConfig(array $config): array
    {
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
     * @param  array<string, string>  $match
     * @return array{id: string, match: array<string, string>, images: array<int, string>, primary: string}
     */
    private static function rule(string $id, array $match, string $assetName): array
    {
        $image = "/images/products/classic-standard-business-cards/classic-standard-business-cards-{$assetName}.png";

        return [
            'id' => $id,
            'match' => $match,
            'images' => [$image],
            'primary' => $image,
        ];
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

        if (self::normalize($match['special_finish'] ?? null) !== 'no-special-finish') {
            return false;
        }

        $corner = self::normalize($match['corners'] ?? null);
        $paperFinish = self::normalize($match['paper_finish'] ?? null);
        $uvFinish = self::normalize($match['uv_finish'] ?? null);

        return in_array($corner, ['square', 'rounded'], true)
            && (
                in_array($paperFinish, ['matte', 'gloss', 'uv'], true)
                || in_array($uvFinish, ['single-side-uv', 'both-sides-uv'], true)
            );
    }

    private static function normalize(mixed $value): string
    {
        return strtolower(str_replace(['_', ' '], '-', trim((string) $value)));
    }
}
