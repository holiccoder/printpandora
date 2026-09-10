<?php

namespace App\Support;

final class BusinessCardOptionCatalog
{
    public const STANDARD_SIZE_SWATCH_IMAGE = '/images/product-options/business-cards/swatches/standard-size.webp';

    public const SQUARE_SIZE_SWATCH_IMAGE = '/images/product-options/business-cards/swatches/square-size.webp';

    public const CUSTOM_SIZE_DESCRIPTION = 'max range: 2.1 - 3.5 inches';

    public const CLASSIC_STANDARD_STANDARD_SIZE_DESCRIPTION = '2.0 x 3.5 inches';

    public const CLASSIC_STANDARD_SQUARE_SIZE_DESCRIPTION = '2.5 x 2.5 inches';

    public const CLASSIC_STANDARD_CUSTOM_SIZE_DESCRIPTION = '2.1 - 3.5 inches';

    public const NO_PRINT_CODE_SWATCH_IMAGE = '/images/product-options/business-cards/swatches/pvc-no-print-code.png';

    public const PRINT_CODE_SWATCH_IMAGE = '/images/product-options/business-cards/swatches/pvc-print-code.png';

    public const PVC_PRINT_CODE_SWATCH_IMAGE = '/images/products/pvc/pvc-print-code.png';

    public const NO_DRILLING_SWATCH_IMAGE = '/images/product-options/business-cards/swatches/drilling/no-drilling.png';

    public const NEEDS_DRILLING_SWATCH_IMAGE = '/images/product-options/business-cards/swatches/drilling/needs-drilling.png';

    public const SIGNATURE_STRIPE_SWATCH_IMAGE = '/images/product-options/business-cards/swatches/pvc-signature-stripe.png';

    public const PVC_SIGNATURE_STRIPE_SWATCH_IMAGE = '/images/products/pvc/pvc-signature-stripe.png';

    /**
     * @var array<string, string>
     */
    private const PVC_FINISH_SWATCH_IMAGES = [
        'matte' => '/images/products/pvc/standard-pvc-matte.png',
        'gloss' => '/images/products/pvc/standard-pvc-gloss.png',
        'frosted' => '/images/products/pvc/standard-pvc-frosted.png',
    ];

    /**
     * @var array<string, string>
     */
    private const QUALITY_TEXTURE_SWATCH_IMAGES = [
        'shattered_glass_film' => '/images/product-options/business-cards/swatches/quality/shattered-glass-film.png',
        'holographic_film' => '/images/product-options/business-cards/swatches/quality/holographic-film.png',
        'starlight_film' => '/images/product-options/business-cards/swatches/quality/starlight-film.png',
        'holographic_star_film' => '/images/product-options/business-cards/swatches/quality/holographic-star-film.png',
        'soft_touch_film' => '/images/product-options/business-cards/swatches/quality/soft-touch-film.png',
    ];

    /**
     * @var array<int, string>
     */
    private const CONTRACT_SLUGS = [
        'classic-quality-business-cards',
        'basic-pvc-card',
        'standard-pvc-card',
        'premium-pvc-card',
        'classic-metal-business-cards',
        'premium-metal-business-cards',
        'luxe-metal-business-cards',
        'basic-cotton-business-card',
        'classic-cotton-business-card',
        'premium-cotton-business-card',
        'luxe-cotton-business-card',
        'grand-cotton-business-card',
        'luxe-business-cards',
        'super-business-cards',
    ];

    /**
     * Return whether the product has a centrally managed option contract.
     */
    public static function supports(string $slug): bool
    {
        return in_array($slug, self::CONTRACT_SLUGS, true);
    }

    /**
     * Apply the shared size metadata to any business-card option map that
     * exposes standard, square, or custom sizes.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function normalizeSharedSizeSwatches(array $options, ?string $slug = null): array
    {
        if (! is_array($options['sizes'] ?? null)) {
            return $options;
        }

        $isCanonicalGroup = array_key_exists('values', $options['sizes']);

        if ($isCanonicalGroup && ! is_array($options['sizes']['values'])) {
            return $options;
        }

        $values = $isCanonicalGroup ? $options['sizes']['values'] : $options['sizes'];

        $standardSwatchImage = self::STANDARD_SIZE_SWATCH_IMAGE;
        $squareSwatchImage = self::SQUARE_SIZE_SWATCH_IMAGE;

        foreach ($values as &$value) {
            if (! is_array($value)) {
                continue;
            }

            $code = $value['code'] ?? null;

            if ($code === 'standard') {
                $value['swatch_image'] = $standardSwatchImage;

                if ($slug === 'classic-standard-business-cards') {
                    $value['description'] = self::CLASSIC_STANDARD_STANDARD_SIZE_DESCRIPTION;
                }
            } elseif ($code === 'square') {
                $value['swatch_image'] = $squareSwatchImage;

                if ($slug === 'classic-standard-business-cards') {
                    $value['description'] = self::CLASSIC_STANDARD_SQUARE_SIZE_DESCRIPTION;
                }
            } elseif ($code === 'custom') {
                $value['description'] = $slug === 'classic-standard-business-cards'
                    ? self::CLASSIC_STANDARD_CUSTOM_SIZE_DESCRIPTION
                    : self::CUSTOM_SIZE_DESCRIPTION;
            }
        }
        unset($value);

        if ($isCanonicalGroup) {
            $options['sizes']['values'] = array_values($values);
        } else {
            $options['sizes'] = array_values($values);
        }

        return $options;
    }

    /**
     * Fill missing shared swatches without replacing a product-specific image
     * that is already present.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function normalizeSharedSwatchImages(array $options): array
    {
        $fallbacks = [
            'print_code' => [
                'no_print_code' => self::NO_PRINT_CODE_SWATCH_IMAGE,
                'print_code' => self::PRINT_CODE_SWATCH_IMAGE,
                'need_print_code' => self::PRINT_CODE_SWATCH_IMAGE,
            ],
            'drill' => [
                'no_drilling' => self::NO_DRILLING_SWATCH_IMAGE,
                'needs_drilling' => self::NEEDS_DRILLING_SWATCH_IMAGE,
            ],
            'print_code_or_signature_stripe' => [
                'no_print_code_or_signature_stripe' => self::NO_PRINT_CODE_SWATCH_IMAGE,
                'print_code' => self::PRINT_CODE_SWATCH_IMAGE,
                'signature_stripe' => self::SIGNATURE_STRIPE_SWATCH_IMAGE,
            ],
            'texture' => self::QUALITY_TEXTURE_SWATCH_IMAGES,
        ];

        foreach ($fallbacks as $groupKey => $groupFallbacks) {
            if (! is_array($options[$groupKey] ?? null)) {
                continue;
            }

            $isCanonicalGroup = array_key_exists('values', $options[$groupKey]);
            $values = $isCanonicalGroup ? $options[$groupKey]['values'] ?? null : $options[$groupKey];

            if (! is_array($values)) {
                continue;
            }

            foreach ($values as &$value) {
                if (! is_array($value)) {
                    continue;
                }

                $swatchImage = $groupFallbacks[$value['code'] ?? ''] ?? null;

                if ($swatchImage !== null && blank($value['swatch_image'] ?? null)) {
                    $value['swatch_image'] = $swatchImage;
                }
            }
            unset($value);

            if ($isCanonicalGroup) {
                $options[$groupKey]['values'] = array_values($values);
            } else {
                $options[$groupKey] = array_values($values);
            }
        }

        return $options;
    }

    /**
     * Normalize an existing option map to the requested product contract.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>|null
     */
    public static function normalize(string $slug, array $options): ?array
    {
        $normalized = match ($slug) {
            'classic-quality-business-cards' => self::classicQuality($options),
            'basic-pvc-card' => self::basicPvc($options),
            'standard-pvc-card' => self::standardPvc($options),
            'premium-pvc-card' => self::premiumPvc($options),
            'classic-metal-business-cards' => self::metal($options, false),
            'premium-metal-business-cards', 'luxe-metal-business-cards' => self::metal($options, true),
            'basic-cotton-business-card',
            'classic-cotton-business-card',
            'premium-cotton-business-card',
            'luxe-cotton-business-card',
            'grand-cotton-business-card' => self::cotton($options),
            'luxe-business-cards' => self::luxeBusinessCards($options),
            'super-business-cards' => self::superBusinessCards($options),
            default => null,
        };

        if ($normalized === null) {
            return null;
        }

        foreach ($normalized as $key => &$group) {
            if (
                is_array($options[$key] ?? null)
                && ($options[$key]['type'] ?? null) === 'multi_select'
            ) {
                $group['type'] = 'multi_select';
            }
        }
        unset($group);

        return self::normalizeSharedSwatchImages($normalized);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function classicQuality(array $options): array
    {
        return [
            'sizes' => self::group('Size', self::sizeValues($options), 'standard'),
            'corners' => self::group('Corners', self::cornerValues($options), 'square'),
            'texture' => self::group('Texture', self::textureValues($options), 'shattered_glass_film'),
            'paper_finish' => self::group('Paper Finish', self::paperFinishValues($options), 'matte'),
            'special_finish' => self::group(
                'Special Finish',
                [...self::hotFoilValues($options), ...self::coldFoilValues($options)],
                'no_special_finish',
            ),
            'special_finish_on_sides' => self::group(
                'Special Finish on Sides',
                self::specialFinishSideValues($options),
                'one_side',
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function basicPvc(array $options): array
    {
        return [
            'paper_finish' => self::group(
                'Paper Finish',
                self::pvcPaperFinishValues($options, self::PVC_FINISH_SWATCH_IMAGES),
                'matte',
            ),
            'print_code' => self::group(
                'Print Code',
                self::printCodeValues($options, self::PVC_PRINT_CODE_SWATCH_IMAGE),
                'no_print_code',
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function standardPvc(array $options): array
    {
        return [
            'paper_finish' => self::group(
                'Paper Finish',
                self::pvcPaperFinishValues($options, self::PVC_FINISH_SWATCH_IMAGES),
                'matte',
            ),
            'print_code_or_signature_stripe' => self::group(
                'Print Code or Signature Stripe',
                self::printCodeOrSignatureStripeValues(
                    $options,
                    self::PVC_PRINT_CODE_SWATCH_IMAGE,
                    self::PVC_SIGNATURE_STRIPE_SWATCH_IMAGE,
                ),
                'no_print_code_or_signature_stripe',
            ),
            'special_finish_on_sides' => self::group(
                'Special Finish on Sides',
                self::specialFinishSideValues($options),
                'one_side',
            ),
            'special_finish' => self::group(
                'Special Finish',
                self::hotFoilValues($options),
                'no_special_finish',
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function premiumPvc(array $options): array
    {
        return [
            'paper_finish' => self::group(
                'Paper Finish',
                self::pvcPaperFinishValues($options, self::PVC_FINISH_SWATCH_IMAGES),
                'matte',
            ),
            'print_code' => self::group(
                'Print Code',
                self::printCodeValues($options, self::PVC_PRINT_CODE_SWATCH_IMAGE),
                'no_print_code',
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function cotton(array $options): array
    {
        return [
            'corners' => self::group('Corners', self::cornerValues($options), 'square'),
            'with_nfc' => self::group('With NFC', [
                self::value($options, 'with_nfc', 'no_nfc', [
                    'label' => 'No NFC',
                    'description' => 'A standard cotton business card without NFC.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/no-nfc-card.png',
                ]),
                self::value($options, 'with_nfc', 'with_nfc', [
                    'label' => 'With NFC',
                    'description' => 'Add an NFC chip for contactless digital sharing.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/nfc-card.png',
                ]),
            ], 'no_nfc'),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function luxeBusinessCards(array $options): array
    {
        return [
            'sizes' => self::group('Size', self::sizeValues($options), 'standard'),
            'corners' => self::group('Corners', self::cornerValues($options), 'square'),
            'texture' => self::group('Texture', self::luxeTextureValues($options), 'inkpavo_j1'),
            'special_finish' => self::group(
                'Special Finish',
                self::hotFoilValues($options),
                'no_special_finish',
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function superBusinessCards(array $options): array
    {
        return [
            'sizes' => self::group('Size', self::sizeValues($options), 'standard'),
            'corners' => self::group('Corners', self::cornerValues($options), 'square'),
            'texture' => self::group('Texture', self::superTextureValues($options), 'j1_water_ripple_paper'),
            'special_finish' => self::group(
                'Special Finish',
                self::hotFoilValues($options),
                'no_special_finish',
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function metal(array $options, bool $withSpecialFinish): array
    {
        $groups = [
            'thickness' => self::group('Thickness', [
                self::value($options, 'thickness', '0_3_mm', [
                    'label' => '12pt',
                    'description' => '12pt metal card thickness.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/metal/thickness-0-3mm.png',
                ]),
                self::value($options, 'thickness', '0_5_mm', [
                    'label' => '20pt',
                    'description' => '20pt metal card thickness.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/metal/thickness-0-5mm.png',
                ]),
            ], '0_3_mm'),
            'sizes' => self::group('Size', [
                self::value($options, 'sizes', '89x51_mm', [
                    'label' => '4x2 inches',
                    'description' => '4x2 inches metal business card.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/metal/size-89x51mm.png',
                ]),
                self::value($options, 'sizes', '85x54_mm', [
                    'label' => '3x2 inches',
                    'description' => '3x2 inches metal business card.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/metal/size-85x54mm.png',
                ]),
                self::value($options, 'sizes', '80x50_mm', [
                    'label' => '3x2 inches',
                    'description' => '3x2 inches metal business card.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/metal/size-80x50mm.png',
                ]),
            ], '89x51_mm'),
            'print_code_or_magnetic_stripe' => self::group(
                'Print Code or Magnetic Stripe',
                [
                    self::value($options, 'print_code_or_magnetic_stripe', 'no_print_code_or_magnetic_stripe', [
                        'label' => 'No print code or magnetic stripe',
                        'description' => 'No print code or magnetic stripe.',
                        'swatch_image' => '/images/product-options/business-cards/swatches/metal/no-print-code-or-magnetic-stripe.png',
                    ]),
                    self::value($options, 'print_code_or_magnetic_stripe', 'print_code', [
                        'label' => 'Print code',
                        'description' => 'Add a printed code to the card.',
                        'swatch_image' => '/images/product-options/business-cards/swatches/metal/print-code.png',
                    ]),
                    self::value($options, 'print_code_or_magnetic_stripe', 'magnetic_stripe', [
                        'label' => 'Magnetic stripe',
                        'description' => 'Add a magnetic stripe to the card.',
                        'swatch_image' => '/images/product-options/business-cards/swatches/metal/magnetic-stripe.png',
                    ]),
                ],
                'no_print_code_or_magnetic_stripe',
            ),
        ];

        if ($withSpecialFinish) {
            $groups['special_finish'] = self::group('Special Finish', [
                self::value($options, 'special_finish', 'laser_engraving', [
                    'label' => 'Laser Engraving',
                    'description' => 'Laser engraving for a precise, tactile finish.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/metal/laser-engraving.png',
                ]),
                self::value($options, 'special_finish', 'color_printing', [
                    'label' => 'Color Printing',
                    'description' => 'Full-color printing on the metal card.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/metal/color-printing.png',
                ]),
                self::value($options, 'special_finish', 'plating', [
                    'label' => 'Plating',
                    'description' => 'Metal plating for a refined finish.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/metal/plating.png',
                ]),
                self::value($options, 'special_finish', 'nfc', [
                    'label' => 'NFC',
                    'description' => 'Add NFC functionality to the card.',
                    'swatch_image' => '/images/product-options/business-cards/swatches/metal/nfc.png',
                ]),
            ], 'laser_engraving');
        }

        return $groups;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function sizeValues(array $options): array
    {
        return [
            self::value($options, 'sizes', 'standard', [
                'label' => 'Standard',
                'description' => '2.0" x 3.5"',
                'width' => '2.0',
                'height' => '3.5',
                'swatch_image' => self::STANDARD_SIZE_SWATCH_IMAGE,
            ]),
            self::value($options, 'sizes', 'square', [
                'label' => 'Square',
                'description' => '2.5" x 2.5"',
                'width' => '2.5',
                'height' => '2.5',
                'swatch_image' => self::SQUARE_SIZE_SWATCH_IMAGE,
            ]),
            self::value($options, 'sizes', 'custom', [
                'label' => 'Custom',
                'description' => self::CUSTOM_SIZE_DESCRIPTION,
                'swatch_image' => '/images/product-options/business-cards/swatches/custom-size.webp',
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function cornerValues(array $options): array
    {
        return [
            self::value($options, 'corners', 'square', [
                'label' => 'Square',
                'description' => 'Sharp and stylish.',
                'swatch_image' => '/images/product-options/business-cards/swatches/square.webp',
            ]),
            self::value($options, 'corners', 'rounded', [
                'label' => 'Rounded',
                'description' => 'Smooth and rounded.',
                'swatch_image' => '/images/product-options/business-cards/swatches/rounded.webp',
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function paperFinishValues(array $options): array
    {
        return [
            self::value($options, 'paper_finish', 'matte', [
                'label' => 'Matte',
                'description' => 'With a smooth feel. Shine-free so no glare.',
                'swatch_image' => '/images/product-options/business-cards/laminates/matte-526x251.jpg',
            ]),
            self::value($options, 'paper_finish', 'gloss', [
                'label' => 'Gloss',
                'description' => 'Eye-catchingly shiny. Makes color photos pop.',
                'swatch_image' => '/images/product-options/business-cards/laminates/gloss-526x251.jpg',
            ]),
            self::value($options, 'paper_finish', 'uv', [
                'label' => '3D UV',
                'description' => 'Raised gloss highlights with a dimensional feel.',
                'swatch_image' => '/images/product-options/uv-swatch.png',
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, string>|null  $swatchImages
     * @return array<int, array<string, mixed>>
     */
    private static function pvcPaperFinishValues(array $options, ?array $swatchImages = null): array
    {
        $swatchImages ??= [
            'matte' => '/images/products/pvc/matte-pvc.png',
            'gloss' => '/images/products/pvc/gloss-pvc.png',
            'frosted' => '/images/products/pvc/frosted-pvc.png',
        ];

        return [
            self::value($options, 'paper_finish', 'matte', [
                'label' => 'Matte',
                'description' => 'Smooth, non-reflective matte finish.',
                'swatch_image' => $swatchImages['matte'],
            ]),
            self::value($options, 'paper_finish', 'gloss', [
                'label' => 'Gloss',
                'description' => 'Shiny and highly reflective gloss finish.',
                'swatch_image' => $swatchImages['gloss'],
            ]),
            self::value($options, 'paper_finish', 'frosted', [
                'label' => 'Frosted Glass',
                'description' => 'A translucent frosted-glass finish with a soft, elegant look.',
                'swatch_image' => $swatchImages['frosted'],
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function printCodeValues(array $options, ?string $printCodeSwatchImage = null): array
    {
        $printCodeSwatchImage ??= self::PRINT_CODE_SWATCH_IMAGE;

        return [
            self::value($options, 'print_code', 'no_print_code', [
                'label' => 'No print code',
                'description' => 'Do not add a print code.',
                'swatch_image' => '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
            ]),
            self::value($options, 'print_code', 'print_code', [
                'label' => 'Print code',
                'description' => 'Add a print code to the card.',
                'swatch_image' => $printCodeSwatchImage,
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function printCodeOrSignatureStripeValues(
        array $options,
        ?string $printCodeSwatchImage = null,
        ?string $signatureStripeSwatchImage = null,
    ): array {
        $printCodeSwatchImage ??= self::PRINT_CODE_SWATCH_IMAGE;
        $signatureStripeSwatchImage ??= self::SIGNATURE_STRIPE_SWATCH_IMAGE;

        return [
            self::value($options, 'print_code_or_signature_stripe', 'no_print_code_or_signature_stripe', [
                'label' => 'No print code or signature stripe',
                'description' => 'Do not add a print code or signature stripe.',
                'swatch_image' => '/images/product-options/business-cards/swatches/pvc-no-print-code.png',
            ]),
            self::value($options, 'print_code_or_signature_stripe', 'print_code', [
                'label' => 'Print code',
                'description' => 'Add a print code to the card.',
                'swatch_image' => $printCodeSwatchImage,
            ]),
            self::value($options, 'print_code_or_signature_stripe', 'signature_stripe', [
                'label' => 'Signature stripe',
                'description' => 'Add a writable signature stripe.',
                'swatch_image' => $signatureStripeSwatchImage,
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function specialFinishSideValues(array $options): array
    {
        return [
            self::value($options, 'special_finish_on_sides', 'one_side', [
                'label' => 'One side',
                'description' => 'Special finish applied to one side only.',
                'swatch_image' => '/images/product-options/business-cards/special-finishes/special-finish-one-side.png',
            ]),
            self::value($options, 'special_finish_on_sides', 'both_sides', [
                'label' => 'Both sides',
                'description' => 'Special finish applied to both sides.',
                'swatch_image' => '/images/product-options/business-cards/special-finishes/special-finish-both-sides.png',
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function hotFoilValues(array $options): array
    {
        $swatches = '/images/product-options/business-cards/swatches/';
        $foils = [
            ['code' => 'no_special_finish', 'label' => 'No special finish', 'swatch_image' => '/images/product-options/no-foil.png', 'description' => 'No special finish, thanks.'],
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

        return array_map(
            fn (array $foil): array => self::value(
                $options,
                'special_finish',
                $foil['code'],
                array_replace(
                    [
                        'label' => $foil['label'],
                        'description' => $foil['label'] === 'No special finish'
                            ? 'No special finish, thanks.'
                            : $foil['label'].' hot foil.',
                        'swatch_image' => $foil['swatch_image'],
                    ],
                    $foil,
                ),
            ),
            $foils,
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function coldFoilValues(array $options): array
    {
        $swatches = '/images/product-options/business-cards/swatches/cold/';
        $foils = [
            ['code' => 'cold_red_gold', 'label' => 'Cold Red Gold', 'description' => 'Vibrant cold red foil', 'swatch_image' => $swatches.'red-gold.png'],
            ['code' => 'cold_blue_gold', 'label' => 'Cold Blue Gold', 'description' => 'Elegant cold blue foil', 'swatch_image' => $swatches.'blue-gold.png'],
            ['code' => 'cold_bright_gold', 'label' => 'Cold Bright Gold', 'description' => 'Glistening cold gold foil', 'swatch_image' => $swatches.'bright-gold.png'],
            ['code' => 'cold_bright_silver', 'label' => 'Cold Bright Silver', 'description' => 'Shining cold silver foil', 'swatch_image' => $swatches.'bright-silver.png'],
            ['code' => 'cold_green_gold', 'label' => 'Cold Green Gold', 'description' => 'Rich cold green gold foil', 'swatch_image' => $swatches.'green-gold.png'],
            ['code' => 'cold_matte_gold', 'label' => 'Cold Matte Gold', 'description' => 'Sophisticated matte gold foil', 'swatch_image' => $swatches.'matte-gold.png'],
            ['code' => 'cold_matte_silver', 'label' => 'Cold Matte Silver', 'description' => 'Elegant matte silver foil', 'swatch_image' => $swatches.'matte-silver.png'],
        ];

        return array_map(
            fn (array $foil): array => self::value($options, 'special_finish', $foil['code'], $foil),
            $foils,
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function textureValues(array $options): array
    {
        $textures = [
            ['code' => 'shattered_glass_film', 'label' => 'Shattered Glass Film'],
            ['code' => 'holographic_film', 'label' => 'Holographic Film'],
            ['code' => 'starlight_film', 'label' => 'Starlight Film'],
            ['code' => 'holographic_star_film', 'label' => 'Holographic Star Film'],
            ['code' => 'soft_touch_film', 'label' => 'Soft-Touch Film'],
        ];

        return array_map(
            fn (array $texture): array => self::value($options, 'texture', $texture['code'], [
                'label' => $texture['label'],
                'description' => '',
                'swatch_image' => self::QUALITY_TEXTURE_SWATCH_IMAGES[$texture['code']],
            ]),
            $textures,
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function luxeTextureValues(array $options): array
    {
        $textures = array_map(
            fn (int $number): array => [
                'code' => "inkpavo_j{$number}",
                'label' => "InkPavo-J{$number}",
                'description' => "InkPavo-J{$number} texture.",
                'swatch_image' => "/images/products/luxe-business-cards/luxe-business-cards-standard-inkpavo-j{$number}.png",
            ],
            range(1, 8),
        );

        return array_map(
            fn (array $texture): array => self::value($options, 'texture', $texture['code'], $texture),
            $textures,
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    private static function superTextureValues(array $options): array
    {
        $textures = [
            ['code' => 'j1_water_ripple_paper', 'label' => 'J1 Water Ripple Paper'],
            ['code' => 'j2_cloth_texture_paper', 'label' => 'J2 Cloth Texture Paper'],
            ['code' => 'j3_eggshell_texture', 'label' => 'J3 Eggshell Texture'],
            ['code' => 'j4_high_grade_paper', 'label' => 'J4 High-Grade Paper'],
            ['code' => 'j5_pearlescent_paper', 'label' => 'J5 Pearlescent Paper'],
            ['code' => 'j6_kraft_paper', 'label' => 'J6 Kraft Paper'],
            ['code' => 'j7_absorbent_cotton_paper', 'label' => 'J7 Absorbent Cotton Paper'],
            ['code' => 'j8_pinhole_paper', 'label' => 'J8 Pinhole Paper'],
        ];

        return array_map(
            fn (array $texture): array => self::value($options, 'texture', $texture['code'], [
                'label' => $texture['label'],
                'description' => $texture['label'].'.',
                'swatch_image' => '/images/products/super-business-cards/texture/'.str_replace('_', '-', $texture['code']).'.png',
            ]),
            $textures,
        );
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $values
     * @return array<string, mixed>
     */
    private static function group(string $label, array $values, string $default): array
    {
        return [
            'label' => $label,
            'type' => 'select',
            'required' => true,
            'default' => $default,
            'values' => array_values($values),
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private static function value(array $options, string $groupKey, string $code, array $defaults): array
    {
        $values = data_get($options, "{$groupKey}.values", []);

        if (is_array($values)) {
            foreach ($values as $value) {
                if (is_array($value) && ($value['code'] ?? null) === $code) {
                    return array_replace($value, $defaults, ['code' => $code]);
                }
            }
        }

        return array_replace(['code' => $code], $defaults);
    }
}
