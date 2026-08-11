<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Services\ProductConfigurationService;
use Illuminate\Database\Seeder;

class ClassicStandardBusinessCardOptionsSeeder extends Seeder
{
    private const PRODUCT_SLUG = 'classic-standard-business-cards';

    public function run(): void
    {
        $product = Product::query()
            ->where('slug', self::PRODUCT_SLUG)
            ->first();

        if (! $product) {
            if ($this->command !== null) {
                $this->command->warn('Classic standard business cards product was not found.');
            }

            return;
        }

        $configuration = app(ProductConfigurationService::class);
        $config = $configuration->canonicalConfig($product);
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
                            'description' => '2.0x3.5  ',
                            'swatch_image' => '/images/product-options/business-cards/swatches/standard-size.webp',
                        ],
                    ),
                    array_replace(
                        $this->existingValue($existing, 'sizes', 'square', [
                            'label' => 'Square',
                            'width' => '2.5',
                            'height' => '2.5',
                        ]),
                        [
                            'description' => '2.5x2.5',
                            'swatch_image' => '/images/product-options/business-cards/swatches/square-size.webp',
                        ],
                    ),
                    [
                        'code' => 'custom',
                        'label' => 'Custom',
                        'description' => 'Enter a custom width and height from 2.1 to 3.5 inches.',
                        'swatch_image' => '/images/product-options/business-cards/swatches/custom-size.webp',
                    ],
                ],
            ],
            'paper_finish' => [
                ...$this->copyGroup($existing, 'paper_finish', 'Paper Finish', [
                    'matte',
                    'gloss',
                    'uv',
                ]),
                'values' => [
                    array_replace(
                        $this->withSwatch(
                            $existing,
                            'paper_finish',
                            'matte',
                            '/images/product-options/business-cards/swatches/matte-paper-finish.webp',
                        ),
                        ['description' => 'With a smooth feel. Shine-free so no glare.'],
                    ),
                    array_replace(
                        $this->withSwatch(
                            $existing,
                            'paper_finish',
                            'gloss',
                            '/images/product-options/business-cards/swatches/gloss-paper-finish.webp',
                        ),
                        ['description' => 'Eye-catchingly shiny. Makes color photos pop.'],
                    ),
                    array_replace(
                        $this->existingValue($existing, 'paper_finish', 'uv', [
                            'label' => 'UV',
                        ]),
                        ['description' => 'UV effect business card'],
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
                'type' => 'select',
                'required' => true,
                'default' => 'no_special_finish',
                'values' => array_merge(
                    [
                        [
                            'code' => 'no_special_finish',
                            'label' => 'No special finish',
                            'description' => 'No special finish, thanks.',
                            'swatch_image' => '/images/product-options/no-foil.png',
                        ],
                    ],
                    array_map(
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
                ),
            ],
            'special_finish_on_sides' => $this->copyGroup(
                $existing,
                'special_finish_on_sides',
                'Special Finish on Sides',
                ['one_side', 'both_sides'],
            ),
        ];

        $product->forceFill(['product_config' => $config])->save();

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
