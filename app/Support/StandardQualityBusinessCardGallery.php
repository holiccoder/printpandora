<?php

namespace App\Support;

final class StandardQualityBusinessCardGallery
{
    public const PRODUCT_SLUG = 'standard-quality-business-cards';

    /**
     * @var array<int, string>
     */
    public const DEFAULT_GALLERY = [
        '/images/products/standard-quality-business-cards/default-01.png',
        '/images/products/standard-quality-business-cards/default-02.png',
        '/images/products/standard-quality-business-cards/default-03.png',
        '/images/products/standard-quality-business-cards/default-04.png',
    ];

    /**
     * @var array<string, string>
     */
    public const COLD_FOIL_IMAGES = [
        'cold_matte_gold' => '/images/products/standard-quality-business-cards/cold-foil/cold-matte-gold.png',
        'cold_matte_silver' => '/images/products/standard-quality-business-cards/cold-foil/cold-matte-silver.png',
        'cold_bright_gold' => '/images/products/standard-quality-business-cards/cold-foil/cold-bright-gold.png',
        'cold_bright_silver' => '/images/products/standard-quality-business-cards/cold-foil/cold-bright-silver.png',
        'cold_red_gold' => '/images/products/standard-quality-business-cards/cold-foil/cold-red-gold.png',
        'cold_green_gold' => '/images/products/standard-quality-business-cards/cold-foil/cold-green-gold.png',
        'cold_blue_gold' => '/images/products/standard-quality-business-cards/cold-foil/cold-blue-gold.png',
    ];

    /**
     * Return the managed default and option-specific rules for this product.
     * Texture rules intentionally precede UV rules: when a non-matte/non-gloss
     * texture and 3D UV are both selected, the texture artwork wins.
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
            self::textureRule('texture-starlight-film', 'starlight_film', 'texture/starlight-film'),
            self::textureRule('texture-holographic-film', 'holographic_film', 'texture/holographic-film'),
            self::textureRule('texture-soft-touch-film', 'soft_touch_film', 'texture/soft-touch-film'),
            self::uvRule('3d-uv-single-side', 'single_side_uv'),
            self::uvRule('3d-uv-both-sides', 'both_sides_uv'),
            self::surfaceRule('standard-matte-square', 'standard', 'square', 'matte', 'standard-matte-square'),
            self::surfaceRule('standard-matte-rounded', 'standard', 'rounded', 'matte', 'standard-matte-rounded'),
            self::surfaceRule('standard-gloss-rounded', 'standard', 'rounded', 'gloss', 'standard-gloss-rounded'),
            self::surfaceRule('standard-gloss-square', 'standard', 'square', 'gloss', 'standard-gloss-square'),
        ];
    }

    /**
     * Replace stale standard-quality default/texture/finish rules while
     * preserving unrelated special-finish and product-specific rules.
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
     * @param  string  $assetName
     * @return array{id: string, match: array<string, string>, images: array<int, string>, primary: string}
     */
    private static function rule(string $id, array $match, string $assetName): array
    {
        $image = "/images/products/standard-quality-business-cards/{$assetName}.png";

        return [
            'id' => $id,
            'match' => $match,
            'images' => [$image],
            'primary' => $image,
        ];
    }

    /**
     * @return array{id: string, match: array<string, string>, images: array<int, string>, primary: string}
     */
    private static function textureRule(string $id, string $texture, string $assetName): array
    {
        return self::rule(
            $id,
            ['texture' => $texture],
            $assetName,
        );
    }

    /**
     * @return array{id: string, match: array<string, string>, images: array<int, string>, primary: string}
     */
    private static function uvRule(string $id, string $uvFinish): array
    {
        return self::rule(
            $id,
            ['uv_finish' => $uvFinish],
            '3d-uv',
        );
    }

    /**
     * @return array{id: string, match: array<string, string>, images: array<int, string>, primary: string}
     */
    private static function surfaceRule(
        string $id,
        string $size,
        string $corner,
        string $texture,
        string $assetName,
    ): array {
        return self::rule(
            $id,
            [
                'sizes' => $size,
                'corners' => $corner,
                'texture' => $texture,
            ],
            $assetName,
        );
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

        $texture = self::normalize($match['texture'] ?? null);
        $paperFinish = self::normalize($match['paper_finish'] ?? null);
        $uvFinish = self::normalize($match['uv_finish'] ?? null);

        return in_array($texture, [
            'matte',
            'gloss',
            'starlight-film',
            'holographic-film',
            'holographic-star-film',
            'shattered-glass-film',
            'soft-touch-film',
        ], true)
            || in_array($paperFinish, ['matte', 'gloss', 'uv', '3d-uv'], true)
            || in_array($uvFinish, ['single-side-uv', 'both-sides-uv'], true);
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
}
