<?php

namespace App\Support;

final class ClassicSpecialBusinessCardTexture
{
    public const CODE = 'pin_point_embossed_paper';

    public const LABEL = 'Pin-point embossed paper';

    public const SQUARE_IMAGE = '/images/products/classic-special-business-cards/texture/pin-point-embossed-paper-square.png';

    public const ROUNDED_IMAGE = '/images/products/classic-special-business-cards/texture/pin-point-embossed-paper-rounded.png';

    /**
     * The remaining art-paper textures use the standard-size square-corner
     * image as their swatch and have four size/corner-specific gallery images.
     *
     * @var array<int, array{code: string, label: string, swatch_image: string, standard_rounded: string, square_square: string, square_rounded: string}>
     */
    private const MAPPED_TEXTURES = [
        [
            'code' => 'water_ripple_paper',
            'label' => 'Water Ripple Paper',
            'swatch_image' => '/images/products/classic-special-business-cards/texture/water-ripple-paper.png',
            'standard_rounded' => '/images/products/classic-special-business-cards/texture/water-ripple-paper-rounded.png',
            'square_square' => '/images/products/classic-special-business-cards/texture/water-ripple-paper-square-size.png',
            'square_rounded' => '/images/products/classic-special-business-cards/texture/water-ripple-paper-square-size-rounded.png',
        ],
        [
            'code' => 'linen_paper',
            'label' => 'Linen Paper',
            'swatch_image' => '/images/products/classic-special-business-cards/texture/linen-paper.png',
            'standard_rounded' => '/images/products/classic-special-business-cards/texture/linen-paper-rounded.png',
            'square_square' => '/images/products/classic-special-business-cards/texture/linen-paper-square-size.png',
            'square_rounded' => '/images/products/classic-special-business-cards/texture/linen-paper-square-size-rounded.png',
        ],
        [
            'code' => 'eggshell_paper',
            'label' => 'Eggshell Paper',
            'swatch_image' => '/images/products/classic-special-business-cards/texture/eggshell-paper.png',
            'standard_rounded' => '/images/products/classic-special-business-cards/texture/eggshell-paper-rounded.png',
            'square_square' => '/images/products/classic-special-business-cards/texture/eggshell-paper-square-size.png',
            'square_rounded' => '/images/products/classic-special-business-cards/texture/eggshell-paper-square-size-rounded.png',
        ],
        [
            'code' => 'white_cardstock',
            'label' => 'White Cardstock',
            'swatch_image' => '/images/products/classic-special-business-cards/texture/white-cardstock.png',
            'standard_rounded' => '/images/products/classic-special-business-cards/texture/white-cardstock-rounded.png',
            'square_square' => '/images/products/classic-special-business-cards/texture/white-cardstock-square-size.png',
            'square_rounded' => '/images/products/classic-special-business-cards/texture/white-cardstock-square-size-rounded.png',
        ],
        [
            'code' => 'pearlized_paper',
            'label' => 'Pearlized Paper',
            'swatch_image' => '/images/products/classic-special-business-cards/texture/pearlized-paper.png',
            'standard_rounded' => '/images/products/classic-special-business-cards/texture/pearlized-paper-rounded.png',
            'square_square' => '/images/products/classic-special-business-cards/texture/pearlized-paper-square-size.png',
            'square_rounded' => '/images/products/classic-special-business-cards/texture/pearlized-paper-square-size-rounded.png',
        ],
    ];

    /**
     * @return array{code: string, label: string, swatch_image: string}
     */
    public static function optionDefinition(): array
    {
        return [
            'code' => self::CODE,
            'label' => self::LABEL,
            'swatch_image' => self::SQUARE_IMAGE,
        ];
    }

    /**
     * @return array<int, array{code: string, label: string, swatch_image: string}>
     */
    public static function optionDefinitions(): array
    {
        return [
            ...array_map(
                static fn (array $texture): array => [
                    'code' => $texture['code'],
                    'label' => $texture['label'],
                    'swatch_image' => $texture['swatch_image'],
                ],
                self::MAPPED_TEXTURES,
            ),
            self::optionDefinition(),
        ];
    }

    /**
     * @return array<int, array{code: string, label: string, swatch_image: string}>
     */
    public static function mappedTextureDefinitions(): array
    {
        return array_slice(self::optionDefinitions(), 0, count(self::MAPPED_TEXTURES));
    }

    /**
     * @return array<int, array{id: string, match: array<string, string>, images: array<int, string>, primary: string}>
     */
    public static function galleryRules(): array
    {
        return [
            self::galleryRule(
                'texture-pin-point-embossed-paper-square',
                [
                    'texture' => self::CODE,
                    'corners' => 'Square',
                ],
                self::SQUARE_IMAGE,
            ),
            self::galleryRule(
                'texture-pin-point-embossed-paper-rounded',
                [
                    'texture' => self::CODE,
                    'corners' => 'Rounded',
                ],
                self::ROUNDED_IMAGE,
            ),
            ...self::mappedGalleryRules(),
        ];
    }

    /**
     * @return array<int, array{id: string, match: array<string, string>, images: array<int, string>, primary: string}>
     */
    public static function mappedGalleryRules(): array
    {
        $rules = [];

        foreach (self::MAPPED_TEXTURES as $texture) {
            $slug = str_replace('_', '-', $texture['code']);
            $variants = [
                [
                    'suffix' => 'standard-square',
                    'size' => 'Standard',
                    'corner' => 'Square',
                    'image' => $texture['swatch_image'],
                ],
                [
                    'suffix' => 'standard-rounded',
                    'size' => 'Standard',
                    'corner' => 'Rounded',
                    'image' => $texture['standard_rounded'],
                ],
                [
                    'suffix' => 'square-size-square',
                    'size' => 'Square',
                    'corner' => 'Square',
                    'image' => $texture['square_square'],
                ],
                [
                    'suffix' => 'square-size-rounded',
                    'size' => 'Square',
                    'corner' => 'Rounded',
                    'image' => $texture['square_rounded'],
                ],
            ];

            foreach ($variants as $variant) {
                $rules[] = self::galleryRule(
                    'texture-'.$slug.'-'.$variant['suffix'],
                    [
                        'texture' => $texture['code'],
                        'sizes' => $variant['size'],
                        'corners' => $variant['corner'],
                    ],
                    $variant['image'],
                );
            }
        }

        return $rules;
    }

    /**
     * @param  array<string, string>  $match
     * @return array{id: string, match: array<string, string>, images: array<int, string>, primary: string}
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
