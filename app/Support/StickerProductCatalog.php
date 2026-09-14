<?php

namespace App\Support;

/**
 * Canonical catalog data for the three sticker products.
 *
 * The material artwork is sourced from the sticker reference folder and is
 * used for both the material swatch and its matching product gallery rule.
 * Keeping the catalog here lets the migration and seeder create the same
 * product configuration on existing and fresh installations.
 */
final class StickerProductCatalog
{
    public const CATEGORY_SLUG = 'stickers-and-labels';

    private const SIZE_SWATCH_IMAGE = '/images/product-options/stickers/square-corner.svg';

    /**
     * Public route segments keyed by the internal product slugs.
     *
     * @var array<string, string>
     */
    private const ROUTE_SEGMENTS = [
        'classic-stickers' => 'classic',
        'premium-stickers' => 'premium',
        'super-stickers' => 'super',
    ];

    private const SQUARE_INCH_TO_SQUARE_METRE = 0.00064516;

    /**
     * Quantity multipliers from the sticker pricing workbook.
     *
     * @var array<string, float>
     */
    private const UNIT_MULTIPLIERS = [
        '50' => 12.0,
        '100' => 7.0,
        '200' => 4.5,
        '500' => 4.0,
        '1000' => 3.8,
        '2000' => 3.6,
        '3000' => 3.4,
        '4000' => 3.2,
        '5000' => 3.0,
    ];

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::productDefinitions());
    }

    public static function isStickerProduct(string $slug): bool
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

        return $segment === null ? null : '/stickers/'.$segment;
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
        $definitions = self::productDefinitions();

        return array_values(array_map(
            static fn (array $definition, string $slug): array => self::buildDefinition([
                ...$definition,
                'slug' => $slug,
            ]),
            $definitions,
            array_keys($definitions),
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function definition(string $slug): ?array
    {
        $definition = self::productDefinitions()[$slug] ?? null;

        return is_array($definition)
            ? self::buildDefinition([...$definition, 'slug' => $slug])
            : null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function productDefinitions(): array
    {
        return [
            'classic-stickers' => [
                'name' => 'Classic Stickers',
                'subtitle' => 'Reliable everyday stickers in classic paper stocks.',
                'description' => '<p>Classic stickers printed on dependable paper stocks for packaging, promotions, labels, and everyday branding.</p>',
                'bullet_points' => [
                    'Square-corner or die-cut shapes',
                    'Six practical paper materials',
                    '2 x 2 in through 5 x 5 in standard sizes',
                    'Custom sizes available',
                ],
                'meta_description' => 'Classic custom stickers in coated, kraft, Avery, and writable paper stocks.',
                'base_price' => 10.0,
                'default_gallery' => [
                    '/images/products/stickers/128gsm-coated.png',
                    '/images/products/stickers/157gsm-coated.png',
                    '/images/products/stickers/avery.png',
                    '/images/products/stickers/light-kraft.png',
                ],
                'materials' => [
                    ['code' => 'coated_128gsm', 'label' => '128gsm Coated Sticker Paper', 'image' => '/images/products/stickers/128gsm-coated.png'],
                    ['code' => 'coated_157gsm', 'label' => '157gsm Coated Sticker Paper', 'image' => '/images/products/stickers/157gsm-coated.png'],
                    ['code' => 'avery', 'label' => 'Avery Sticker Paper', 'image' => '/images/products/stickers/avery.png'],
                    ['code' => 'light_kraft', 'label' => 'Light Kraft Sticker Paper', 'image' => '/images/products/stickers/light-kraft.png'],
                    ['code' => 'dark_kraft', 'label' => 'Dark Kraft Sticker Paper', 'image' => '/images/products/stickers/dark-kraft.png'],
                    ['code' => 'writable', 'label' => 'Writable Sticker Paper', 'image' => '/images/products/stickers/writable.png'],
                ],
            ],
            'premium-stickers' => [
                'name' => 'Premium Stickers',
                'subtitle' => 'Distinctive textured and specialty paper stickers.',
                'description' => '<p>Premium stickers with tactile, textured, and luminous paper stocks for packaging, invitations, product labels, and elevated brand details.</p>',
                'bullet_points' => [
                    'Square-corner or die-cut shapes',
                    'Eight textured and specialty paper materials',
                    '2 x 2 in through 5 x 5 in standard sizes',
                    'Custom sizes available',
                ],
                'meta_description' => 'Premium custom stickers in textured, pearl, cotton-fiber, and specialty paper stocks.',
                'base_price' => 14.0,
                'default_gallery' => [
                    '/images/products/stickers/off-white-grass-scented.png',
                    '/images/products/stickers/off-white-cotton-fiber.png',
                    '/images/products/stickers/dark-yellow-grass-scented.png',
                    '/images/products/stickers/woodgrain.png',
                ],
                'materials' => [
                    ['code' => 'off_white_grass_scented', 'label' => 'Off-White Grass-Scented Sticker Paper', 'image' => '/images/products/stickers/off-white-grass-scented.png'],
                    ['code' => 'off_white_cotton_fiber', 'label' => 'Off-White Cotton-Fiber Sticker Paper', 'image' => '/images/products/stickers/off-white-cotton-fiber.png'],
                    ['code' => 'dark_yellow_grass_scented', 'label' => 'Dark Yellow Grass-Scented Sticker Paper', 'image' => '/images/products/stickers/dark-yellow-grass-scented.png'],
                    ['code' => 'woodgrain', 'label' => 'Woodgrain Sticker Paper', 'image' => '/images/products/stickers/woodgrain.png'],
                    ['code' => 'white_fabric_texture', 'label' => 'White Fabric-Texture Sticker Paper', 'image' => '/images/products/stickers/white-fabric-texture.png'],
                    ['code' => 'pearl_white', 'label' => 'Pearl White Sticker Paper', 'image' => '/images/products/stickers/pearl-white.png'],
                    ['code' => 'antique_water_ripple_white', 'label' => 'Antique Water-Ripple White Sticker Paper', 'image' => '/images/products/stickers/antique-water-ripple-white.png'],
                    ['code' => 'masking_paper', 'label' => 'Masking Sticker Paper', 'image' => '/images/products/stickers/masking-paper.png'],
                ],
            ],
            'super-stickers' => [
                'name' => 'Super Stickers',
                'subtitle' => 'Bold metallic, synthetic, and transparent stickers.',
                'description' => '<p>Super stickers use standout specialty stocks for metallic accents, durable labels, transparent branding, and memorable packaging details.</p>',
                'bullet_points' => [
                    'Square-corner or die-cut shapes',
                    'Six metallic, synthetic, and transparent materials',
                    '2 x 2 in through 5 x 5 in standard sizes',
                    'Custom sizes available',
                ],
                'meta_description' => 'Super custom stickers in gold-sprinkled, metallic, synthetic PP, and transparent stocks.',
                'base_price' => 18.0,
                'default_gallery' => [
                    '/images/products/stickers/bright-red-gold-sprinkled.png',
                    '/images/products/stickers/masking-gold-sprinkled.png',
                    '/images/products/stickers/liquid-gold-pearl.png',
                    '/images/products/stickers/5mil-matte-silver.png',
                ],
                'materials' => [
                    ['code' => 'bright_red_gold_sprinkled', 'label' => 'Bright Red Gold-Sprinkled Sticker Paper', 'image' => '/images/products/stickers/bright-red-gold-sprinkled.png'],
                    ['code' => 'masking_gold_sprinkled', 'label' => 'Masking Gold-Sprinkled Sticker Paper', 'image' => '/images/products/stickers/masking-gold-sprinkled.png'],
                    ['code' => 'liquid_gold_pearl', 'label' => 'Liquid Gold Pearl Sticker Paper', 'image' => '/images/products/stickers/liquid-gold-pearl.png'],
                    ['code' => 'matte_silver_5mil', 'label' => '5-mil Matte Silver Sticker Paper', 'image' => '/images/products/stickers/5mil-matte-silver.png'],
                    ['code' => 'thick_base_pp_synthetic', 'label' => 'Thick-Base PP Synthetic Sticker Paper', 'image' => '/images/products/stickers/thick-base-pp-synthetic.png'],
                    ['code' => 'clear_5mil', 'label' => '5-mil Clear Sticker Paper', 'image' => '/images/products/stickers/5mil-clear.png'],
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
        $basePrice = (float) $definition['base_price'];
        $defaultGallery = array_values($definition['default_gallery']);
        $materials = array_values($definition['materials']);

        $config = [
            'schema_version' => 1,
            'product' => [
                'slug' => $slug,
                'name' => $name,
                'subtitle' => $definition['subtitle'],
                'description' => $definition['description'],
                'description_title' => null,
                'bullet_points' => $definition['bullet_points'],
                'featured_image' => $defaultGallery[0],
                'meta_description' => $definition['meta_description'],
            ],
            'options' => [
                'sizes' => [
                    'label' => 'Size',
                    'type' => 'select',
                    'required' => true,
                    'default' => '2x2',
                    'values' => self::sizeValues(),
                ],
                'shape' => [
                    'label' => 'Shape',
                    'type' => 'select',
                    'required' => true,
                    'default' => 'square_corner',
                    'values' => [
                        [
                            'code' => 'square_corner',
                            'label' => 'Square Corner',
                            'description' => 'Clean square corners.',
                            'swatch_image' => '/images/product-options/stickers/square-corner.svg',
                        ],
                        [
                            'code' => 'die_cut',
                            'label' => 'Die Cut',
                            'description' => 'Cut to the outline of your artwork.',
                            'swatch_image' => '/images/product-options/stickers/die-cut.svg',
                        ],
                    ],
                ],
                'material' => [
                    'label' => 'Material',
                    'type' => 'select',
                    'required' => true,
                    'default' => $materials[0]['code'],
                    'values' => array_map(
                        static fn (array $material): array => [
                            'code' => $material['code'],
                            'label' => $material['label'],
                            'swatch_image' => $material['image'],
                        ],
                        $materials,
                    ),
                ],
            ],
            'media' => [
                'gallery' => $defaultGallery,
                'gallery_rules' => [
                    ...array_map(
                        static fn (array $material): array => [
                            'id' => "material_{$material['code']}",
                            'match' => ['material' => $material['code']],
                            'images' => [$material['image']],
                            'primary' => $material['image'],
                        ],
                        $materials,
                    ),
                ],
            ],
            'pricing' => [
                'mode' => 'rule_based',
                'currency' => 'USD',
                'total_rounding' => 'nearest_integer',
                'scenarios' => [],
                'quantity_price_table' => [],
                'rules' => [
                    [
                        'id' => 'sticker-area-pricing',
                        'match' => [],
                        'pricing' => [
                            'packageName' => "{$name} pricing",
                            'basePrice' => $basePrice,
                            'startQuantity' => 50,
                            'paperRates' => [],
                            'unitMultipliers' => self::UNIT_MULTIPLIERS,
                            'area_based' => true,
                            'processes' => [],
                        ],
                    ],
                ],
            ],
            'faq' => [],
            'detail_sections' => [],
        ];

        $defaultArea = self::sizeValues()[0]['area_sq_m'];
        $startingTotal = (int) round(50 * $basePrice * self::UNIT_MULTIPLIERS['50'] * $defaultArea);

        return [
            'name' => $name,
            'slug' => $slug,
            'subtitle' => $definition['subtitle'],
            'description' => $definition['description'],
            'description_title' => null,
            'bullet_points' => $definition['bullet_points'],
            'meta_description' => $definition['meta_description'],
            'featured_image' => $defaultGallery[0],
            'price_line' => "50 stickers from \${$startingTotal}",
            'weight' => 0,
            'product_config' => $config,
            'is_active' => true,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function sizeValues(): array
    {
        return [
            [
                'code' => '2x2',
                'label' => '2 x 2 in',
                'description' => '2 x 2 inches',
                'width' => '2.00',
                'height' => '2.00',
                'area_sq_m' => 0.00258064,
                'swatch_image' => self::SIZE_SWATCH_IMAGE,
            ],
            [
                'code' => '3x3',
                'label' => '3 x 3 in',
                'description' => '3 x 3 inches',
                'width' => '3.00',
                'height' => '3.00',
                'area_sq_m' => 0.00580644,
                'swatch_image' => self::SIZE_SWATCH_IMAGE,
            ],
            [
                'code' => '4x4',
                'label' => '4 x 4 in',
                'description' => '4 x 4 inches',
                'width' => '4.00',
                'height' => '4.00',
                'area_sq_m' => 0.01032256,
                'swatch_image' => self::SIZE_SWATCH_IMAGE,
            ],
            [
                'code' => '5x5',
                'label' => '5 x 5 in',
                'description' => '5 x 5 inches',
                'width' => '5.00',
                'height' => '5.00',
                'area_sq_m' => 0.016129,
                'swatch_image' => self::SIZE_SWATCH_IMAGE,
            ],
            [
                'code' => 'custom',
                'label' => 'Custom Size',
                'description' => '18 x 18 mm minimum; up to 430 x 301 mm.',
                'min_width' => '0.71',
                'max_width' => '16.93',
                'min_height' => '0.71',
                'max_height' => '11.85',
                'swatch_image' => self::SIZE_SWATCH_IMAGE,
            ],
        ];
    }
}
