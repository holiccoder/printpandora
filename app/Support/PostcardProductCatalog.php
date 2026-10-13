<?php

namespace App\Support;

/**
 * Catalog and pricing data for the postcard products.
 *
 * The quantity schedules are transcribed from the supplied cards workbook:
 * sheet1 contains paper pricing and sheet2 contains finishing-process pricing.
 * The area/unit-price columns use upper area bounds. Above the last bound,
 * retain the final rate. Totals are area × unit price × quantity multiplier.
 */
final class PostcardProductCatalog
{
    public const CATEGORY_SLUG = 'cards-and-postcards';

    public const RECOMMENDED_QUANTITY = 200;

    /**
     * @var array<string, array{
     *     reference: string,
     *     name: string,
     *     paper_name: string,
     *     area_sq_m: float,
     *     unit_price: float,
     *     area_unit_prices: array<string, float>,
     *     start_quantity: int,
     *     multipliers: array<string, float>
     * }>
     */
    private const PRODUCTS = [
        'classic-standard-postcards' => [
            'reference' => 'classic-standard-business-cards',
            'name' => 'Classic Standard Postcards',
            'paper_name' => '300g铜版纸',
            'area_sq_m' => 0.00486,
            'unit_price' => 3.2,
            'area_unit_prices' => [
                '0.005' => 3.2, '0.0085' => 3.0, '0.012' => 2.8,
                '0.0155' => 2.6, '0.019' => 2.4, '0.0225' => 2.2,
                '0.026' => 2.0, '0.0295' => 1.8,
            ],
            'start_quantity' => 200,
            'multipliers' => [
                '200' => 4.2,
                '500' => 3.0,
                '1000' => 2.2,
                '2000' => 1.9,
                '3000' => 1.8,
                '4000' => 1.7,
                '5000' => 1.65,
            ],
        ],
        'classic-special-postcards' => [
            'reference' => 'classic-special-business-cards',
            'name' => 'Classic Special Postcards',
            'paper_name' => '300g艺术纸',
            'area_sq_m' => 0.00486,
            'unit_price' => 4.0,
            'area_unit_prices' => [
                '0.005' => 4.0, '0.0085' => 3.8, '0.012' => 3.6,
                '0.0155' => 3.4, '0.019' => 3.2, '0.0225' => 3.0,
                '0.026' => 2.8, '0.0295' => 2.6,
            ],
            'start_quantity' => 200,
            'multipliers' => [
                '200' => 4.2,
                '500' => 3.0,
                '1000' => 2.2,
                '2000' => 1.9,
                '3000' => 1.8,
                '4000' => 1.7,
                '5000' => 1.65,
            ],
        ],
        'super-standard-postcards' => [
            'reference' => 'super-standard-business-cards',
            'name' => 'Super Standard Postcards',
            'paper_name' => '350g精品纸',
            'area_sq_m' => 0.015,
            'unit_price' => 5.5,
            'area_unit_prices' => [
                '0.005' => 7.0, '0.0085' => 6.5, '0.012' => 6.0,
                '0.0155' => 5.5, '0.019' => 5.0, '0.0225' => 4.5,
                '0.026' => 4.0, '0.0295' => 3.5, '0.033' => 3.0,
                '0.0365' => 2.5,
            ],
            'start_quantity' => 50,
            'multipliers' => [
                '50' => 12.0,
                '100' => 9.0,
                '200' => 6.0,
                '500' => 4.5,
                '1000' => 3.6,
                '2000' => 3.3,
                '3000' => 3.0,
                '4000' => 2.7,
                '5000' => 2.4,
            ],
        ],
        'super-luxe-postcards' => [
            'reference' => 'super-luxe-business-cards',
            'name' => 'Super Luxe Postcards',
            'paper_name' => '700g精品纸',
            'area_sq_m' => 0.00486,
            'unit_price' => 12.0,
            'area_unit_prices' => [
                '0.005' => 12.0, '0.0085' => 11.0, '0.012' => 10.0,
                '0.0155' => 9.0, '0.019' => 8.0, '0.0225' => 7.2,
                '0.026' => 6.4, '0.0295' => 5.6, '0.033' => 4.9,
                '0.0365' => 4.2,
            ],
            'start_quantity' => 50,
            'multipliers' => [
                '50' => 12.0,
                '100' => 9.0,
                '200' => 7.8,
                '500' => 6.8,
                '1000' => 5.2,
                '2000' => 4.0,
                '3000' => 3.6,
                '4000' => 3.4,
                '5000' => 3.2,
            ],
        ],
        'quality-standard-postcards' => [
            'reference' => 'standard-quality-business-cards',
            'name' => 'Quality Standard Postcards',
            'paper_name' => '320g铜版纸',
            'area_sq_m' => 0.0144,
            'unit_price' => 3.8,
            'area_unit_prices' => [
                '0.005' => 5.0, '0.0085' => 4.6, '0.012' => 4.2,
                '0.0155' => 3.8, '0.019' => 3.4, '0.0225' => 3.0,
                '0.026' => 2.7, '0.0295' => 2.4, '0.033' => 2.1,
                '0.0365' => 1.8,
            ],
            'start_quantity' => 50,
            'multipliers' => [
                '50' => 12.0,
                '100' => 8.0,
                '200' => 5.5,
                '500' => 4.0,
                '1000' => 3.3,
                '2000' => 3.0,
                '3000' => 2.7,
                '4000' => 2.5,
                '5000' => 2.3,
            ],
        ],
        'quality-solid-postcards' => [
            'reference' => 'solid-quality-business-cards',
            'name' => 'Quality Solid Postcards',
            'paper_name' => '640g铜版纸',
            'area_sq_m' => 0.015,
            'unit_price' => 9.0,
            'area_unit_prices' => [
                '0.005' => 9.0, '0.0085' => 8.0, '0.012' => 7.0,
                '0.0155' => 6.0, '0.019' => 5.0, '0.0225' => 4.2,
                '0.026' => 3.4, '0.0295' => 2.6, '0.033' => 2.2,
                '0.0365' => 1.8,
            ],
            'start_quantity' => 50,
            'multipliers' => [
                '50' => 18.0,
                '100' => 9.0,
                '200' => 4.5,
                '500' => 4.0,
                '1000' => 3.7,
                '2000' => 3.5,
                '3000' => 3.2,
                '4000' => 3.0,
                '5000' => 2.8,
            ],
        ],
    ];

    /** @var array<string, float> */
    private const FOIL_AREA_UNIT_PRICES = [
        '0.005' => 5.0, '0.0085' => 4.85, '0.012' => 4.7,
        '0.0155' => 4.65, '0.019' => 4.5, '0.0225' => 4.35,
        '0.026' => 4.2, '0.0295' => 4.05, '0.033' => 3.9,
        '0.0365' => 3.75,
    ];

    /** @var array<string, float> */
    private const UV_AREA_UNIT_PRICES = [
        '0.005' => 7.0, '0.0085' => 6.5, '0.012' => 6.0,
        '0.0155' => 5.5, '0.019' => 5.0, '0.0225' => 4.5,
        '0.026' => 4.0, '0.0295' => 3.5, '0.033' => 3.0,
        '0.0365' => 2.5,
    ];

    /**
     * @var array<string, array{
     *     label: string,
     *     unit_price: float,
     *     area_unit_prices: array<string, float>,
     *     multipliers: array<string, float>
     * }>
     */
    private const PROCESS_SCHEDULES = [
        'rounded_corners' => [
            'label' => 'Rounded corners',
            'unit_price' => 3.5,
            'area_unit_prices' => self::UV_AREA_UNIT_PRICES,
            'multipliers' => [
                '50' => 12.0,
                '100' => 9.0,
                '200' => 6.0,
                '500' => 4.5,
                '1000' => 3.6,
                '2000' => 3.3,
                '3000' => 3.0,
                '4000' => 2.7,
                '5000' => 2.4,
            ],
        ],
        'uv_finish' => [
            'label' => 'Regular UV',
            'unit_price' => 3.5,
            'area_unit_prices' => self::UV_AREA_UNIT_PRICES,
            'multipliers' => [
                '50' => 12.0,
                '100' => 9.0,
                '200' => 6.0,
                '500' => 4.5,
                '1000' => 3.6,
                '2000' => 3.3,
                '3000' => 3.0,
                '4000' => 2.7,
                '5000' => 2.4,
            ],
        ],
        'hot_foil' => [
            'label' => 'Hot foil',
            'unit_price' => 3.75,
            'area_unit_prices' => self::FOIL_AREA_UNIT_PRICES,
            'multipliers' => [
                '50' => 16.8,
                '100' => 8.4,
                '200' => 4.2,
                '500' => 3.7,
                '1000' => 3.3,
                '2000' => 3.3,
                '3000' => 2.9,
                '4000' => 2.9,
                '5000' => 2.5,
            ],
        ],
        'cold_foil' => [
            'label' => 'Cold foil',
            'unit_price' => 3.75,
            'area_unit_prices' => self::FOIL_AREA_UNIT_PRICES,
            'multipliers' => [
                '50' => 16.8,
                '100' => 8.4,
                '200' => 4.2,
                '500' => 3.7,
                '1000' => 3.3,
                '2000' => 3.3,
                '3000' => 2.9,
                '4000' => 2.9,
                '5000' => 2.5,
            ],
        ],
        '3d_uv' => [
            'label' => '3D UV',
            'unit_price' => 3.75,
            'area_unit_prices' => self::FOIL_AREA_UNIT_PRICES,
            'multipliers' => [
                '50' => 16.8,
                '100' => 8.4,
                '200' => 4.2,
                '500' => 3.7,
                '1000' => 3.3,
                '2000' => 3.3,
                '3000' => 2.9,
                '4000' => 2.9,
                '5000' => 2.5,
            ],
        ],
        'custom_die_cut' => [
            'label' => 'Custom die-cut',
            'unit_price' => 8.0,
            'area_unit_prices' => self::UV_AREA_UNIT_PRICES,
            'multipliers' => [
                '50' => 12.0,
                '100' => 9.0,
                '200' => 6.0,
                '500' => 4.5,
                '1000' => 3.6,
                '2000' => 3.3,
                '3000' => 3.0,
                '4000' => 2.7,
                '5000' => 2.4,
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

    /** @return array<string, mixed> */
    public static function sizeOptions(): array
    {
        $sizes = [
            ['standard', 'Standard', 4, 6, 'standard'],
            ['square', 'Square', 4.72, 4.72, 'square'],
            ['rack', 'Rack', 3.67, 8.5, 'rack'],
            ['half_page', 'Half Page', 5.5, 8.5, 'half-page'],
            ['small', 'Small', 4.13, 5.83, 'small'],
            ['medium', 'Medium', 5, 7, 'medium'],
            ['large', 'Large', 6, 9, 'large'],
        ];
        $values = [];

        foreach ($sizes as [$code, $label, $width, $height, $image]) {
            $values[] = [
                'code' => $code,
                'label' => $label,
                'width' => (string) $width,
                'height' => (string) $height,
                'unit' => 'in',
                'area_sq_m' => round($width * $height * 0.0254 ** 2, 8),
                'description' => $width.' x '.$height.' in',
                'swatch_image' => '/images/products/postcards/size-swatches/'.$image.'.svg',
            ];
        }

        $values[] = [
            'code' => 'custom',
            'label' => 'Custom Size',
            'unit' => 'mm',
            'min_width' => 18,
            'max_width' => 430,
            'min_height' => 18,
            'max_height' => 301,
            'description' => 'W 18–430 mm × H 18–301 mm',
            'swatch_image' => '/images/products/postcards/size-swatches/custom.svg',
        ];

        return [
            'label' => 'Size',
            'type' => 'select',
            'required' => true,
            'default' => 'standard',
            'values' => $values,
        ];
    }

    /**
     * Update existing postcards without replacing their copy, finishes or media.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function synchronizeConfig(array $config, string $slug): array
    {
        if (! self::isPostcardProduct($slug)) {
            return $config;
        }

        $config['options']['sizes'] = self::sizeOptions();
        $config['pricing'] = self::pricingFor($slug);

        return $config;
    }

    public static function isPostcardProduct(string $slug): bool
    {
        return isset(self::PRODUCTS[$slug]);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function normalizeOptions(array $options, string $slug): array
    {
        if (in_array($slug, ['super-standard-postcards', 'super-luxe-postcards'], true)) {
            unset($options['uv_finish']);
        }

        return $options;
    }

    public static function productSlugForSegment(string $segment): ?string
    {
        if (self::isPostcardProduct($segment)) {
            return $segment;
        }

        $productSlug = rtrim($segment, '-').'-postcards';

        return self::isPostcardProduct($productSlug) ? $productSlug : null;
    }

    public static function pathForProductSlug(string $productSlug): ?string
    {
        return self::isPostcardProduct($productSlug)
            ? '/postcards/'.str_replace('-postcards', '', $productSlug)
            : null;
    }

    public static function hrefForProductSlug(string $productSlug): string
    {
        return self::pathForProductSlug($productSlug)
            ?? '/'.ltrim($productSlug, '/');
    }

    /**
     * @return list<string>
     */
    public static function gallery(): array
    {
        return self::galleryFor('classic-standard-postcards');
    }

    /**
     * @return list<string>
     */
    public static function galleryFor(string $slug): array
    {
        return CardsAndPostcardsProductImageCatalog::postcardGalleryFor($slug) ?? [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function definition(string $slug): ?array
    {
        $definition = self::PRODUCTS[$slug] ?? null;

        if (! is_array($definition)) {
            return null;
        }

        return [
            ...$definition,
            'slug' => $slug,
            'gallery' => self::galleryFor($slug),
            'featured_image' => CardsAndPostcardsProductImageCatalog::postcardFeaturedImageFor($slug),
            'pricing' => self::pricingFor($slug),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function pricingFor(string $slug): array
    {
        $definition = self::PRODUCTS[$slug] ?? null;

        if (! is_array($definition)) {
            return [];
        }

        $paperMultipliers = $definition['multipliers'];
        $startQuantity = $definition['start_quantity'];

        $processes = [];

        foreach (['rounded_corners', 'uv_finish', 'hot_foil', 'cold_foil', '3d_uv', 'custom_die_cut'] as $code) {
            $schedule = self::PROCESS_SCHEDULES[$code];
            $processes[] = [
                'name' => $schedule['label'],
                'code' => $code,
                'markup' => $schedule['unit_price'],
                'rates' => self::discountRates($schedule['multipliers']),
                'unitMultipliers' => $schedule['multipliers'],
                'area_unit_prices' => $schedule['area_unit_prices'],
            ];
        }

        return [
            'mode' => 'rule_based',
            'currency' => 'USD',
            'total_rounding' => 'nearest_integer',
            'scenarios' => [],
            'quantity_price_table' => [],
            'rules' => [[
                'id' => 'postcard-pricing',
                'match' => [],
                'pricing' => [
                    'packageName' => $definition['paper_name'],
                    'basePrice' => $definition['unit_price'],
                    'startQuantity' => $startQuantity,
                    'recommendedQuantity' => min(self::RECOMMENDED_QUANTITY, max(array_map('intval', array_keys($paperMultipliers)))),
                    'paperRates' => self::discountRates($paperMultipliers),
                    'unitMultipliers' => $paperMultipliers,
                    'area_unit_prices' => $definition['area_unit_prices'],
                    'area_based' => true,
                    'processes' => $processes,
                ],
            ]],
        ];
    }

    /**
     * @param  array<string, float>  $multipliers
     * @return array<string, float>
     */
    private static function discountRates(array $multipliers): array
    {
        $base = (float) reset($multipliers);

        if ($base <= 0) {
            return [];
        }

        $rates = [];

        foreach ($multipliers as $quantity => $multiplier) {
            $rates[(string) $quantity] = round((1 - ((float) $multiplier / $base)) * 100, 2);
        }

        return $rates;
    }
}
