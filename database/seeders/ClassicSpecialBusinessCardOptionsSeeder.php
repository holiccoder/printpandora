<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Services\ProductConfigurationService;
use App\Support\BusinessCardOptionCatalog;
use App\Support\ClassicSpecialBusinessCardTexture;
use Illuminate\Database\Seeder;

class ClassicSpecialBusinessCardOptionsSeeder extends Seeder
{
    private const PRODUCT_SLUG = 'classic-special-business-cards';

    /**
     * The four shared default gallery images for the classic special card.
     *
     * @var array<int, string>
     */
    public const DEFAULT_GALLERY = [
        '/images/classic-special-business-cards/default01.png',
        '/images/classic-special-business-cards/default02.png',
        '/images/classic-special-business-cards/default03.png',
        '/images/classic-special-business-cards/default04.png',
    ];

    public function run(): void
    {
        $product = Product::query()
            ->where('slug', self::PRODUCT_SLUG)
            ->first();

        if (! $product) {
            if ($this->command !== null) {
                $this->command->warn('Classic special business cards product was not found.');
            }

            return;
        }

        $configuration = app(ProductConfigurationService::class);
        $config = $configuration->canonicalConfig($product);
        $config['media'] = self::synchronizeDefaultGallery(
            is_array($config['media'] ?? null) ? $config['media'] : [],
        );
        $existing = is_array($config['options'] ?? null) ? $config['options'] : [];

        $config['options'] = [
            'sizes' => [
                'label' => 'Size',
                'type' => 'select',
                'required' => true,
                'default' => 'standard',
                'values' => [
                    array_replace(
                        $this->existingValue($existing, 'sizes', 'standard', [
                            'label' => 'Standard',
                            'width' => '2.0',
                            'height' => '3.5',
                        ]),
                        [
                            'label' => 'Standard',
                            'description' => '2.0″ x 3.5″',
                            'width' => '2.0',
                            'height' => '3.5',
                            'swatch_image' => BusinessCardOptionCatalog::STANDARD_SIZE_SWATCH_IMAGE,
                        ],
                    ),
                    array_replace(
                        $this->existingValue($existing, 'sizes', 'square', [
                            'label' => 'Square',
                            'width' => '2.5',
                            'height' => '2.5',
                        ]),
                        [
                            'label' => 'Square',
                            'description' => '2.5″ x 2.5″',
                            'width' => '2.5',
                            'height' => '2.5',
                            'swatch_image' => BusinessCardOptionCatalog::SQUARE_SIZE_SWATCH_IMAGE,
                        ],
                    ),
                    [
                        'code' => 'custom',
                        'label' => 'Custom',
                        'description' => BusinessCardOptionCatalog::CUSTOM_SIZE_DESCRIPTION,
                        'swatch_image' => BusinessCardOptionCatalog::CUSTOM_SIZE_SWATCH_IMAGE,
                    ],
                ],
            ],
            'corners' => [
                'label' => 'Corners',
                'type' => 'select',
                'required' => true,
                'default' => 'square',
                'values' => [
                    array_replace(
                        $this->existingValue($existing, 'corners', 'square', [
                            'label' => 'Square',
                            'description' => 'Sharp and stylish.',
                        ]),
                        [
                            'label' => 'Square',
                            'description' => 'Sharp and stylish.',
                            'swatch_image' => '/images/product-options/business-cards/swatches/square.webp',
                        ],
                    ),
                    array_replace(
                        $this->existingValue($existing, 'corners', 'rounded', [
                            'label' => 'Rounded',
                        ]),
                        [
                            'label' => 'Rounded',
                            'description' => 'Smooth and rounded.',
                            'swatch_image' => '/images/product-options/business-cards/swatches/rounded.webp',
                        ],
                    ),
                ],
            ],
            'special_finish' => [
                'label' => 'Special Finish',
                'type' => 'multi_select',
                'required' => false,
                'default' => [],
                'values' => array_map(
                    fn (array $foil): array => array_replace(
                        $this->existingValue($existing, 'special_finish', $foil['code'], [
                            'label' => $foil['label'],
                        ]),
                        [
                            'label' => $foil['label'],
                            'description' => $foil['label'].' hot foil.',
                            'swatch_image' => $foil['swatch_image'],
                        ],
                    ),
                    $this->foilDefinitions(),
                ),
            ],
            'texture' => [
                'label' => 'Texture',
                'type' => 'select',
                'required' => true,
                'default' => 'water_ripple_paper',
                'values' => array_map(
                    fn (array $texture): array => array_replace(
                        $this->existingValue($existing, 'texture', $texture['code'], []),
                        [
                            'label' => $texture['label'],
                            'description' => '',
                            'swatch_image' => $texture['swatch_image'],
                        ],
                    ),
                    $this->textureDefinitions(),
                ),
            ],
        ];

        $normalizedProduct = clone $product;
        $normalizedProduct->setAttribute('product_config', $config);
        $config = $configuration->canonicalConfig($normalizedProduct);

        $product->forceFill(['product_config' => $config])->save();

        if ($this->command !== null) {
            $this->command->info('Classic special business card options imported.');
        }
    }

    /**
     * Keep the canonical product gallery in sync with the four imported
     * default images while preserving shared foil-specific rules.
     *
     * @param  array<string, mixed>  $media
     * @return array<string, mixed>
     */
    public static function synchronizeDefaultGallery(array $media): array
    {
        $media['gallery'] = self::DEFAULT_GALLERY;
        $galleryRules = is_array($media['gallery_rules'] ?? null)
            ? array_values($media['gallery_rules'])
            : [];
        $textureRuleIds = array_map(
            static fn (array $rule): string => $rule['id'],
            ClassicSpecialBusinessCardTexture::galleryRules(),
        );
        $galleryRules = array_values(array_filter(
            $galleryRules,
            static fn (mixed $rule): bool => is_array($rule)
                && ! in_array((string) ($rule['id'] ?? ''), $textureRuleIds, true),
        ));
        $hasDefaultRule = false;

        foreach ($galleryRules as &$rule) {
            $images = is_array($rule['images'] ?? null) ? $rule['images'] : [];
            $containsClassicSpecialImage = count(array_filter(
                $images,
                fn (mixed $image): bool => is_string($image)
                    && str_starts_with($image, '/images/classic-special-business-cards/'),
            )) > 0;
            $isDefaultRule = ($rule['id'] ?? null) === 'default'
                || ($rule['match'] ?? []) === [];

            if (! $isDefaultRule && ! $containsClassicSpecialImage) {
                continue;
            }

            $rule['images'] = self::DEFAULT_GALLERY;
            $rule['primary'] = self::DEFAULT_GALLERY[0];

            if (($rule['id'] ?? null) === 'default' || $isDefaultRule) {
                $hasDefaultRule = true;
            }
        }
        unset($rule);

        if (! $hasDefaultRule) {
            $galleryRules[] = [
                'id' => 'default',
                'match' => [],
                'images' => self::DEFAULT_GALLERY,
                'primary' => self::DEFAULT_GALLERY[0],
            ];
        }

        $defaultRules = [];
        $otherRules = [];

        foreach ($galleryRules as $rule) {
            $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

            if (($rule['id'] ?? null) === 'default' || $match === []) {
                if ($defaultRules === []) {
                    $defaultRules[] = $rule;
                }

                continue;
            }

            $otherRules[] = $rule;
        }

        $media['gallery_rules'] = [
            ...$defaultRules,
            ...ClassicSpecialBusinessCardTexture::galleryRules(),
            ...$otherRules,
        ];

        return $media;
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function existingValue(array $options, string $groupKey, string $code, array $defaults): array
    {
        $values = data_get($options, "{$groupKey}.values", []);

        if (is_array($values)) {
            foreach ($values as $value) {
                if (is_array($value) && ($value['code'] ?? null) === $code) {
                    return array_replace($defaults, $value, ['code' => $code]);
                }
            }
        }

        return array_replace(['code' => $code], $defaults);
    }

    /**
     * @return array<int, array{code: string, label: string, swatch_image: string}>
     */
    private function foilDefinitions(): array
    {
        $swatches = '/images/product-options/business-cards/swatches/';

        return [
            ['code' => 'black_gold', 'label' => 'Black Gold', 'swatch_image' => $swatches.'black-gold.png'],
            ['code' => 'blue_gold', 'label' => 'Blue Gold', 'swatch_image' => $swatches.'blue-gold.png'],
            ['code' => 'bright_gold', 'label' => 'Bright Gold', 'swatch_image' => $swatches.'bright-gold.png'],
            ['code' => 'bright_silver', 'label' => 'Bright Silver', 'swatch_image' => $swatches.'bright-silver.png'],
            ['code' => 'green_gold', 'label' => 'Green Gold', 'swatch_image' => $swatches.'green-gold.png'],
            ['code' => 'matte_gold', 'label' => 'Matte Gold', 'swatch_image' => $swatches.'matte-gold.png'],
            ['code' => 'matte_silver', 'label' => 'Matte Silver', 'swatch_image' => $swatches.'matte-silver.png'],
            ['code' => 'red_gold', 'label' => 'Red Gold', 'swatch_image' => $swatches.'red-gold.png'],
            ['code' => 'rose_gold', 'label' => 'Rose Gold', 'swatch_image' => $swatches.'rose-gold.png'],
            ['code' => 'aged_gold', 'label' => 'Aged Gold', 'swatch_image' => $swatches.'aged-gold.png'],
            ['code' => 'muted_purple_gold', 'label' => 'Muted Purple Gold', 'swatch_image' => $swatches.'muted-purple-gold.png'],
        ];
    }

    /**
     * @return array<int, array{code: string, label: string, swatch_image: string}>
     */
    private function textureDefinitions(): array
    {
        return [
            ...ClassicSpecialBusinessCardTexture::optionDefinitions(),
        ];
    }
}
