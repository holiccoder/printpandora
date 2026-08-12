<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Services\ProductConfigurationService;
use Illuminate\Database\Seeder;

class ClassicSpecialBusinessCardOptionsSeeder extends Seeder
{
    private const PRODUCT_SLUG = 'classic-special-business-cards';

    public function run(): void
    {
        $product = Product::query()
            ->where('slug', self::PRODUCT_SLUG)
            ->first();

        if (! $product) {
            $this->command?->warn('Classic special business cards product was not found.');

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
                            'label' => 'Standard',
                            'description' => '2.0″ x 3.5″',
                            'width' => '2.0',
                            'height' => '3.5',
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
                            'label' => 'Square',
                            'description' => '2.5″ x 2.5″',
                            'width' => '2.5',
                            'height' => '2.5',
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
            'paper_finish' => [
                'label' => 'Paper Finish',
                'type' => 'select',
                'required' => true,
                'default' => 'matte',
                'values' => [
                    array_replace(
                        $this->existingValue($existing, 'paper_finish', 'matte', [
                            'label' => 'Matte',
                        ]),
                        [
                            'label' => 'Matte',
                            'description' => 'With a smooth feel. Shine-free so no glare.',
                            'swatch_image' => '/images/product-options/business-cards/swatches/matte-paper-finish.webp',
                        ],
                    ),
                    array_replace(
                        $this->existingValue($existing, 'paper_finish', 'gloss', [
                            'label' => 'Gloss',
                        ]),
                        [
                            'label' => 'Gloss',
                            'description' => 'Eye-catchingly shiny. Makes color photos pop.',
                            'swatch_image' => '/images/product-options/business-cards/swatches/gloss-paper-finish.webp',
                        ],
                    ),
                    array_replace(
                        $this->existingValue($existing, 'paper_finish', 'uv', [
                            'label' => '3D UV',
                        ]),
                        [
                            'label' => '3D UV',
                            'description' => 'Raised gloss highlights with a dimensional feel.',
                            'swatch_image' => '/images/product-options/uv-swatch.png',
                        ],
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
                        array_replace(
                            $this->existingValue($existing, 'special_finish', 'no_special_finish', []),
                            [
                                'label' => 'No special finish',
                                'description' => 'No special finish, thanks.',
                                'swatch_image' => '/images/product-options/no-foil.png',
                            ],
                        ),
                    ],
                    array_map(
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
                ),
            ],
            'special_finish_on_sides' => [
                'label' => 'Special Finish on Sides',
                'type' => 'select',
                'required' => true,
                'default' => 'one_side',
                'values' => [
                    array_replace(
                        $this->existingValue($existing, 'special_finish_on_sides', 'one_side', [
                            'label' => 'One side',
                        ]),
                        [
                            'label' => 'One side',
                            'description' => 'Special finish applied to one side only.',
                            'swatch_image' => '/images/product-options/business-cards/special-finishes/special-finish-one-side.png',
                        ],
                    ),
                    array_replace(
                        $this->existingValue($existing, 'special_finish_on_sides', 'both_sides', [
                            'label' => 'Both sides',
                        ]),
                        [
                            'label' => 'Both sides',
                            'description' => 'Special finish applied to both sides.',
                            'swatch_image' => '/images/product-options/business-cards/special-finishes/special-finish-both-sides.png',
                        ],
                    ),
                ],
            ],
            'texture' => [
                'label' => 'Texture',
                'type' => 'select',
                'required' => true,
                'default' => 'pin_hole_paper',
                'values' => array_map(
                    fn (array $texture): array => array_replace(
                        $this->existingValue($existing, 'texture', $texture['code'], []),
                        [
                            'label' => $texture['label'],
                            'description' => '',
                            'swatch_image' => '',
                        ],
                    ),
                    $this->textureDefinitions(),
                ),
            ],
        ];

        $product->forceFill(['product_config' => $config])->save();

        $this->command?->info('Classic special business card options imported.');
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
     * @return array<int, array{code: string, label: string}>
     */
    private function textureDefinitions(): array
    {
        return [
            ['code' => 'pin_hole_paper', 'label' => 'Pin-hole Paper'],
            ['code' => 'water_ripple_paper', 'label' => 'Water Ripple Paper'],
            ['code' => 'linen_paper', 'label' => 'Linen Paper'],
            ['code' => 'eggshell_paper', 'label' => 'Eggshell Paper'],
            ['code' => 'white_cardstock', 'label' => 'White Cardstock'],
            ['code' => 'pearlized_paper', 'label' => 'Pearlized Paper'],
        ];
    }
}
