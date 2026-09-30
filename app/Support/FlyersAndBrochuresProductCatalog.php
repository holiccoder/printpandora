<?php

namespace App\Support;

/**
 * Canonical catalog, option, and area-pricing data for flyers and brochures.
 *
 * The workbook prices these products by square metre, quantity, and the
 * quantity multiplier. Keeping the source data here lets migrations, seeders,
 * the storefront, and server-side pricing use the same contract.
 */
final class FlyersAndBrochuresProductCatalog
{
    public const CATEGORY_SLUG = 'flyers-brochures';

    public const START_QUANTITY = 200;

    public const RECOMMENDED_QUANTITY = 200;

    private const MILLIMETRES_PER_INCH = 25.4;

    /**
     * @var array<string, string>
     */
    private const ROUTE_SEGMENTS = [
        'classic-standard-flyers-and-brochures' => 'classic-standard',
        'classic-super-flyers-and-brochures' => 'classic-super',
        'classic-luxe-flyers-and-brochures' => 'classic-luxe',
        'quality-flyers-and-brochures' => 'quality',
        'special-flyers-and-brochures' => 'special',
        'super-flyers-and-brochures' => 'super',
    ];

    /**
     * Quantity multipliers transcribed from the flyers and brochures
     * workbook. The unit price is stored per square metre.
     *
     * @var array<string, float>
     */
    private const UNIT_MULTIPLIERS = [
        '200' => 6.5,
        '500' => 4.5,
        '1000' => 3.5,
        '2000' => 3.45,
        '3000' => 3.4,
        '4000' => 3.35,
        '5000' => 3.3,
    ];

    /**
     * @var array<string, string>
     */
    private const SIZE_SWATCHES = [
        '105x148' => '/images/product-options/flyers-and-brochures/sizes/105x148.png',
        '120x120' => '/images/product-options/flyers-and-brochures/sizes/120x120.png',
        '99x210' => '/images/product-options/flyers-and-brochures/sizes/99x210.png',
        '148x210' => '/images/product-options/flyers-and-brochures/sizes/148x210.png',
        '210x297' => '/images/product-options/flyers-and-brochures/sizes/210x297.png',
        'custom' => '/images/product-options/flyers-and-brochures/sizes/custom-size.png',
    ];

    /**
     * @var array<string, string>
     */
    private const PAPER_FINISH_SWATCHES = [
        'matte_lamination' => '/images/product-options/flyers-and-brochures/paper-finishes/铜版纸亚膜.png',
        'gloss_lamination' => '/images/product-options/flyers-and-brochures/paper-finishes/铜版纸光膜.png',
        'gloss_varnish' => '/images/product-options/flyers-and-brochures/paper-finishes/铜版纸光油.png',
        'soft_touch_lamination' => '/images/product-options/flyers-and-brochures/paper-finishes/铜版纸触感膜.png',
    ];

    /**
     * @var array<string, array{label: string, description: string, image: string}>
     */
    private const FOLDING_VALUES = [
        'half_fold' => [
            'label' => 'Half Fold',
            'description' => 'A simple fold in half.',
            'image' => '/images/product-options/flyers-and-brochures/foldings/half-fold.png',
        ],
        'three_panel_roll_fold' => [
            'label' => '3-Panel Roll Fold',
            'description' => 'Three panels that fold inward and tuck into the center.',
            'image' => '/images/product-options/flyers-and-brochures/foldings/three-panel-roll-fold.png',
        ],
        'three_panel_accordion_fold' => [
            'label' => '3-Panel Accordion Fold',
            'description' => 'Three alternating panels for a zig-zag fold.',
            'image' => '/images/product-options/flyers-and-brochures/foldings/three-panel-accordion-fold.png',
        ],
        'four_panel_gate_fold' => [
            'label' => '4-Panel Gate Fold',
            'description' => 'Four panels with the outer panels folding inward.',
            'image' => '/images/product-options/flyers-and-brochures/foldings/four-panel-gate-fold.png',
        ],
        'four_panel_accordion_fold' => [
            'label' => '4-Panel Accordion Fold',
            'description' => 'Four alternating panels for a zig-zag fold.',
            'image' => '/images/product-options/flyers-and-brochures/foldings/four-panel-accordion-fold.png',
        ],
        'five_panel_accordion_fold' => [
            'label' => '5-Panel Accordion Fold',
            'description' => 'Five alternating panels for an extended zig-zag fold.',
            'image' => '/images/product-options/flyers-and-brochures/foldings/five-panel-accordion-fold.png',
        ],
    ];

    /**
     * @var array<string, array{
     *     name: string,
     *     paper_name: string,
     *     paper_name_source: string,
     *     unit_price: float,
     *     finish: string,
     *     foldings: list<string>,
     *     gallery: list<string>
     * }>
     */
    private const PRODUCTS = [
        'classic-standard-flyers-and-brochures' => [
            'name' => 'Classic Standard Flyers and Brochures',
            'paper_name' => '157g Coated Paper',
            'paper_name_source' => '157g铜版纸',
            'unit_price' => 1.5,
            'finish' => 'gloss_varnish',
            'foldings' => [
                'half_fold',
                'three_panel_accordion_fold',
                'three_panel_roll_fold',
                'four_panel_gate_fold',
                'four_panel_accordion_fold',
                'five_panel_accordion_fold',
            ],
            'gallery' => [
                '/images/products/flyers-and-brochures/classic-standard/01.png',
                '/images/products/flyers-and-brochures/classic-standard/02.png',
                '/images/products/flyers-and-brochures/classic-standard/03.png',
                '/images/products/flyers-and-brochures/classic-standard/04.png',
            ],
        ],
        'classic-super-flyers-and-brochures' => [
            'name' => 'Classic Super Flyers and Brochures',
            'paper_name' => '200g Coated Paper',
            'paper_name_source' => '200g铜版纸',
            'unit_price' => 1.7,
            'finish' => 'classic',
            'foldings' => [
                'half_fold',
                'three_panel_accordion_fold',
                'three_panel_roll_fold',
                'four_panel_gate_fold',
                'four_panel_accordion_fold',
                'five_panel_accordion_fold',
            ],
            'gallery' => [
                '/images/products/flyers-and-brochures/classic-super/01.png',
                '/images/products/flyers-and-brochures/classic-super/02.png',
                '/images/products/flyers-and-brochures/classic-super/03.png',
                '/images/products/flyers-and-brochures/classic-super/04.png',
            ],
        ],
        'classic-luxe-flyers-and-brochures' => [
            'name' => 'Classic Luxe Flyers and Brochures',
            'paper_name' => '300g Coated Paper',
            'paper_name_source' => '300g铜版纸',
            'unit_price' => 2.0,
            'finish' => 'classic',
            'foldings' => ['half_fold'],
            'gallery' => [
                '/images/products/flyers-and-brochures/classic-luxe/01.png',
                '/images/products/flyers-and-brochures/classic-luxe/02.png',
                '/images/products/flyers-and-brochures/classic-luxe/03.png',
                '/images/products/flyers-and-brochures/classic-luxe/04.png',
            ],
        ],
        'quality-flyers-and-brochures' => [
            'name' => 'Quality Flyers and Brochures',
            'paper_name' => '240g Pearl Paper',
            'paper_name_source' => '240g珠光纸',
            'unit_price' => 2.0,
            'finish' => 'none',
            'foldings' => [
                'half_fold',
                'three_panel_accordion_fold',
                'three_panel_roll_fold',
            ],
            'gallery' => [
                '/images/products/flyers-and-brochures/quality/01.png',
                '/images/products/flyers-and-brochures/quality/02.png',
                '/images/products/flyers-and-brochures/quality/03.png',
                '/images/products/flyers-and-brochures/quality/04.png',
            ],
        ],
        'special-flyers-and-brochures' => [
            'name' => 'Special Flyers and Brochures',
            'paper_name' => '250g Dutch White Card',
            'paper_name_source' => '250g白卡纸',
            'unit_price' => 1.8,
            'finish' => 'none',
            'foldings' => [
                'half_fold',
                'three_panel_accordion_fold',
                'three_panel_roll_fold',
            ],
            'gallery' => [
                '/images/products/flyers-and-brochures/special/01.png',
                '/images/products/flyers-and-brochures/special/02.png',
                '/images/products/flyers-and-brochures/special/03.png',
                '/images/products/flyers-and-brochures/special/04.png',
            ],
        ],
        'super-flyers-and-brochures' => [
            'name' => 'Super Flyers and Brochures',
            'paper_name' => '300g Meilan Elegant Ultra White Paper',
            'paper_name_source' => '300g美兰典雅超白',
            'unit_price' => 2.8,
            'finish' => 'none',
            'foldings' => [
                'half_fold',
                'three_panel_accordion_fold',
                'three_panel_roll_fold',
            ],
            'gallery' => [
                '/images/products/flyers-and-brochures/super/01.png',
                '/images/products/flyers-and-brochures/super/02.png',
                '/images/products/flyers-and-brochures/super/03.png',
                '/images/products/flyers-and-brochures/super/04.png',
            ],
        ],
    ];

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::PRODUCTS);
    }

    public static function isFlyerProduct(string $slug): bool
    {
        return in_array($slug, self::slugs(), true);
    }

    public static function productSlugForSegment(string $segment): ?string
    {
        $productSlug = array_search($segment, self::ROUTE_SEGMENTS, true);

        return $productSlug === false ? null : (string) $productSlug;
    }

    public static function pathForProductSlug(string $productSlug): ?string
    {
        $segment = self::ROUTE_SEGMENTS[$productSlug] ?? null;

        return $segment === null ? null : '/flyers-and-brochures/'.$segment;
    }

    public static function hrefForProductSlug(string $productSlug): string
    {
        return self::pathForProductSlug($productSlug)
            ?? '/'.ltrim($productSlug, '/');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function definitions(): array
    {
        return array_values(array_map(
            static fn (array $definition, string $slug): array => self::buildDefinition([
                ...$definition,
                'slug' => $slug,
            ]),
            self::PRODUCTS,
            array_keys(self::PRODUCTS),
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function definition(string $slug): ?array
    {
        $definition = self::PRODUCTS[$slug] ?? null;

        return is_array($definition)
            ? self::buildDefinition([...$definition, 'slug' => $slug])
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function pricingFor(string $slug): ?array
    {
        $definition = self::PRODUCTS[$slug] ?? null;

        if (! is_array($definition)) {
            return null;
        }

        return [
            'mode' => 'rule_based',
            'currency' => 'USD',
            'total_rounding' => 'nearest_integer',
            'scenarios' => [],
            'quantity_price_table' => [],
            'rules' => [
                [
                    'id' => 'flyers-and-brochures-area-pricing',
                    'match' => [],
                    'pricing' => [
                        'packageName' => $definition['name'].' pricing',
                        'basePrice' => (float) $definition['unit_price'],
                        'startQuantity' => self::START_QUANTITY,
                        'recommendedQuantity' => self::RECOMMENDED_QUANTITY,
                        'paperRates' => [],
                        'unitMultipliers' => self::UNIT_MULTIPLIERS,
                        'area_based' => true,
                        'processes' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private static function buildDefinition(array $definition): array
    {
        $slug = (string) $definition['slug'];
        $name = (string) $definition['name'];
        $gallery = array_values($definition['gallery']);
        $sizeValues = self::sizeValues();
        $pricing = self::pricingFor($slug) ?? [];
        $pricingRule = data_get($pricing, 'rules.0.pricing', []);
        $defaultSize = $sizeValues[0];
        $finishValues = self::finishValues((string) $definition['finish']);
        $options = [
            'sizes' => [
                'label' => 'Size',
                'type' => 'select',
                'required' => true,
                'default' => $defaultSize['code'],
                'values' => $sizeValues,
            ],
        ];

        if ($finishValues !== []) {
            $options['paper_finish'] = [
                'label' => 'Paper Finish',
                'type' => 'select',
                'required' => true,
                'default' => $finishValues[0]['code'],
                'values' => $finishValues,
            ];
        }

        $options['folding'] = [
            'label' => 'Folding',
            'type' => 'select',
            'required' => true,
            'default' => $definition['foldings'][0],
            'values' => self::foldingValues($definition['foldings']),
        ];
        $descriptionChoice = $finishValues !== []
            ? 'standard size, paper finish, and folding style'
            : 'standard size and folding style';
        $startingTotal = (int) round(
            self::START_QUANTITY
                * (float) $definition['unit_price']
                * self::UNIT_MULTIPLIERS[(string) self::START_QUANTITY]
                * (float) $defaultSize['area_sq_m'],
        );

        $config = [
            'schema_version' => 1,
            'product' => [
                'slug' => $slug,
                'name' => $name,
                'subtitle' => 'Professional flyers and brochures on '.$definition['paper_name'].'.',
                'description' => '<p>Print vivid flyers and brochures on '.$definition['paper_name'].' with your choice of '.$descriptionChoice.'.</p>',
                'description_title' => null,
                'bullet_points' => [
                    'Five standard sizes from 3.90 × 8.27 in to 8.27 × 11.69 in',
                    'Custom width and height from 3.94 × 3.94 in to 23.62 × 23.62 in',
                    'Full-color printing with consistent fold alignment',
                    'Quantity pricing from 200 pieces',
                ],
                'featured_image' => $gallery[0],
                'meta_description' => $name.' printed on '.$definition['paper_name'].' with custom sizes and folding options.',
            ],
            'options' => $options,
            'media' => [
                'gallery' => $gallery,
                'gallery_rules' => [[
                    'id' => 'default',
                    'match' => [],
                    'images' => $gallery,
                    'primary' => $gallery[0],
                ]],
            ],
            'pricing' => $pricing,
            'faq' => [],
            'detail_sections' => [
                'design_specifications' => PrintDesignSpecifications::businessCards(),
            ],
        ];

        return [
            'name' => $name,
            'slug' => $slug,
            'subtitle' => $config['product']['subtitle'],
            'description' => $config['product']['description'],
            'description_title' => null,
            'bullet_points' => $config['product']['bullet_points'],
            'meta_description' => $config['product']['meta_description'],
            'featured_image' => $gallery[0],
            'price_line' => self::START_QUANTITY.' flyers from $'.$startingTotal,
            'weight' => 0,
            'product_config' => $config,
            'is_active' => true,
            'pricing_rule' => $pricingRule,
            'paper_name_source' => $definition['paper_name_source'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function sizeValues(): array
    {
        $sizes = [
            ['code' => '105x148', 'width_mm' => 105, 'height_mm' => 148],
            ['code' => '120x120', 'width_mm' => 120, 'height_mm' => 120],
            ['code' => '99x210', 'width_mm' => 99, 'height_mm' => 210],
            ['code' => '148x210', 'width_mm' => 148, 'height_mm' => 210],
            ['code' => '210x297', 'width_mm' => 210, 'height_mm' => 297],
        ];

        $values = array_map(
            static function (array $size): array {
                $width = self::millimetresToInches($size['width_mm']);
                $height = self::millimetresToInches($size['height_mm']);
                $label = $width.' × '.$height.' in';
                $area = ((float) $size['width_mm'] / 1000) * ((float) $size['height_mm'] / 1000);

                return [
                    'code' => $size['code'],
                    'label' => $label,
                    'width' => $width,
                    'height' => $height,
                    'description' => $label,
                    'area_sq_m' => round($area, 8),
                    'unit' => 'in',
                    'swatch_image' => self::SIZE_SWATCHES[$size['code']],
                ];
            },
            $sizes,
        );

        $values[] = [
            'code' => 'custom',
            'label' => 'Custom',
            'unit' => 'in',
            'min_width' => self::millimetresToInches(100),
            'max_width' => self::millimetresToInches(600),
            'min_height' => self::millimetresToInches(100),
            'max_height' => self::millimetresToInches(600),
            'swatch_image' => self::SIZE_SWATCHES['custom'],
        ];

        return $values;
    }

    private static function millimetresToInches(int|float $millimetres): string
    {
        return number_format($millimetres / self::MILLIMETRES_PER_INCH, 2, '.', '');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function finishValues(string $finish): array
    {
        if ($finish === 'none') {
            return [];
        }

        $values = [
            [
                'code' => 'matte_lamination',
                'label' => 'Matte',
                'description' => 'A low-sheen protective matte film.',
                'swatch_image' => self::PAPER_FINISH_SWATCHES['matte_lamination'],
            ],
            [
                'code' => 'gloss_lamination',
                'label' => 'Gloss',
                'description' => 'A bright, reflective protective film.',
                'swatch_image' => self::PAPER_FINISH_SWATCHES['gloss_lamination'],
            ],
            [
                'code' => 'gloss_varnish',
                'label' => 'Gloss Varnish',
                'description' => 'A clear glossy coating that enhances color.',
                'swatch_image' => self::PAPER_FINISH_SWATCHES['gloss_varnish'],
            ],
            [
                'code' => 'soft_touch_lamination',
                'label' => 'Soft-Touch',
                'description' => 'A smooth, velvety protective film.',
                'swatch_image' => self::PAPER_FINISH_SWATCHES['soft_touch_lamination'],
            ],
        ];

        if ($finish === 'gloss_varnish') {
            return [$values[2]];
        }

        return $values;
    }

    /**
     * @param  list<string>  $codes
     * @return list<array<string, mixed>>
     */
    private static function foldingValues(array $codes): array
    {
        return array_values(array_map(
            static fn (string $code): array => [
                'code' => $code,
                'label' => self::FOLDING_VALUES[$code]['label'],
                'description' => self::FOLDING_VALUES[$code]['description'],
                'swatch_image' => self::FOLDING_VALUES[$code]['image'],
            ],
            $codes,
        ));
    }
}
