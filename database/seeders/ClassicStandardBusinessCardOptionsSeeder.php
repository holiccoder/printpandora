<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Services\ProductConfigurationService;
use App\Support\BusinessCardOptionCatalog;
use App\Support\ClassicStandardBusinessCardGallery;
use Illuminate\Database\Seeder;

class ClassicStandardBusinessCardOptionsSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::query()
            ->where('slug', ClassicStandardBusinessCardGallery::PRODUCT_SLUG)
            ->first();

        if (! $product) {
            if ($this->command !== null) {
                $this->command->warn('Classic standard business cards product was not found.');
            }

            return;
        }

        $configuration = app(ProductConfigurationService::class);
        $config = $configuration->canonicalConfig($product);
        $config = ClassicStandardBusinessCardGallery::synchronizeConfig($config);
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
                            'description' => BusinessCardOptionCatalog::CLASSIC_STANDARD_STANDARD_SIZE_DESCRIPTION,
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
                            'description' => BusinessCardOptionCatalog::CLASSIC_STANDARD_SQUARE_SIZE_DESCRIPTION,
                            'swatch_image' => BusinessCardOptionCatalog::SQUARE_SIZE_SWATCH_IMAGE,
                        ],
                    ),
                    [
                        'code' => 'custom',
                        'label' => 'Custom',
                        'description' => BusinessCardOptionCatalog::CLASSIC_STANDARD_CUSTOM_SIZE_DESCRIPTION,
                        'swatch_image' => '/images/product-options/business-cards/swatches/custom-size.webp',
                    ],
                ],
            ],
            'paper_finish' => [
                ...$this->copyGroup($existing, 'paper_finish', 'Paper Finish', [
                    'matte',
                    'gloss',
                ]),
                'required' => false,
                'values' => [
                    array_replace(
                        $this->withSwatch(
                            $existing,
                            'paper_finish',
                            'matte',
                            '/images/product-options/business-cards/laminates/matte-526x251.jpg',
                        ),
                        ['description' => 'With a smooth feel. Shine-free so no glare.'],
                    ),
                    array_replace(
                        $this->withSwatch(
                            $existing,
                            'paper_finish',
                            'gloss',
                            '/images/product-options/business-cards/laminates/gloss-526x251.jpg',
                        ),
                        ['description' => 'Eye-catchingly shiny. Makes color photos pop.'],
                    ),
                ],
            ],
            'uv_finish' => [
                'label' => 'UV',
                'type' => 'select',
                'required' => false,
                'default' => null,
                'values' => [
                    array_replace(
                        $this->existingValue($existing, 'uv_finish', 'single_side_uv', [
                            'label' => 'single side UV',
                            'swatch_image' => '/images/product-options/uv-swatch.png',
                        ]),
                        [
                            'label' => 'single side UV',
                            'swatch_image' => '/images/product-options/uv-swatch.png',
                        ],
                    ),
                    array_replace(
                        $this->existingValue($existing, 'uv_finish', 'both_sides_uv', [
                            'label' => 'both sides UV',
                            'swatch_image' => '/images/product-options/uv-swatch.png',
                        ]),
                        [
                            'label' => 'both sides UV',
                            'swatch_image' => '/images/product-options/uv-swatch.png',
                        ],
                    ),
                ],
            ],
            'corners' => [
                ...$this->copyGroup($existing, 'corners', 'Corners', [
                    'square',
                    'rounded',
                ]),
                'values' => [
                    $this->withSwatch(
                        $existing,
                        'corners',
                        'square',
                        '/images/product-options/business-cards/swatches/square.webp',
                    ),
                    array_replace(
                        $this->withSwatch(
                            $existing,
                            'corners',
                            'rounded',
                            '/images/product-options/business-cards/swatches/rounded.webp',
                        ),
                        ['description' => ''],
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
                            'description' => "{$foil['label']} hot foil.",
                            'swatch_image' => $foil['swatch_image'],
                        ]),
                        [
                            'label' => $foil['label'],
                            'description' => "{$foil['label']} hot foil.",
                            'swatch_image' => $foil['swatch_image'],
                        ],
                    ),
                    [
                        [
                            'code' => 'black_gold',
                            'label' => 'Black Gold',
                            'swatch_image' => '/images/product-options/business-cards/swatches/black-gold.png',
                        ],
                        [
                            'code' => 'blue_gold',
                            'label' => 'Blue Gold',
                            'swatch_image' => '/images/product-options/business-cards/swatches/blue-gold.png',
                        ],
                        [
                            'code' => 'bright_gold',
                            'label' => 'Bright Gold',
                            'swatch_image' => '/images/product-options/business-cards/swatches/bright-gold.png',
                        ],
                        [
                            'code' => 'bright_silver',
                            'label' => 'Bright Silver',
                            'swatch_image' => '/images/product-options/business-cards/swatches/bright-silver.png',
                        ],
                        [
                            'code' => 'green_gold',
                            'label' => 'Green Gold',
                            'swatch_image' => '/images/product-options/business-cards/swatches/green-gold.png',
                        ],
                        [
                            'code' => 'matte_gold',
                            'label' => 'Matte Gold',
                            'swatch_image' => '/images/product-options/business-cards/swatches/matte-gold.png',
                        ],
                        [
                            'code' => 'matte_silver',
                            'label' => 'Matte Silver',
                            'swatch_image' => '/images/product-options/business-cards/swatches/matte-silver.png',
                        ],
                        [
                            'code' => 'red_gold',
                            'label' => 'Red Gold',
                            'swatch_image' => '/images/product-options/business-cards/swatches/red-gold.png',
                        ],
                        [
                            'code' => 'rose_gold',
                            'label' => 'Rose Gold',
                            'swatch_image' => '/images/product-options/business-cards/swatches/rose-gold.png',
                        ],
                        [
                            'code' => 'aged_gold',
                            'label' => 'Aged Gold',
                            'swatch_image' => '/images/product-options/business-cards/swatches/aged-gold.png',
                        ],
                        [
                            'code' => 'muted_purple_gold',
                            'label' => 'Muted Purple Gold',
                            'swatch_image' => '/images/product-options/business-cards/swatches/muted-purple-gold.png',
                        ],
                    ],
                ),
            ],
        ];

        $normalizedProduct = clone $product;
        $normalizedProduct->setAttribute('product_config', $config);
        $config = $configuration->canonicalConfig($normalizedProduct);

        $product->forceFill([
            'featured_image' => ClassicStandardBusinessCardGallery::DEFAULT_GALLERY[0],
            'product_config' => $config,
        ])->save();

        if ($this->command !== null) {
            $this->command->info('Classic standard business card options imported.');
        }
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
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function withSwatch(array $options, string $groupKey, string $code, string $swatchImage): array
    {
        return array_replace(
            $this->existingValue($options, $groupKey, $code, [
                'label' => str($code)->replace('_', ' ')->title()->toString(),
            ]),
            ['swatch_image' => $swatchImage],
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<int, string>  $codes
     * @return array<string, mixed>
     */
    private function copyGroup(array $options, string $key, string $label, array $codes): array
    {
        $values = array_map(
            fn (string $code): array => $this->existingValue($options, $key, $code, [
                'label' => str($code)->replace('_', ' ')->title()->toString(),
            ]),
            $codes,
        );

        return [
            'label' => $label,
            'type' => 'select',
            'required' => true,
            'default' => $codes[0],
            'values' => $values,
        ];
    }
}
