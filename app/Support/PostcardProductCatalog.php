<?php

namespace App\Support;

/**
 * Catalog and pricing data for the postcard products.
 *
 * The quantity schedules are transcribed from the supplied cards workbook:
 * sheet1 contains paper pricing and sheet2 contains finishing-process pricing.
 * Prices are stored per square metre so the same formula works for standard,
 * square, and custom dimensions: area × unit price × quantity multiplier.
 */
final class PostcardProductCatalog
{
    public const CATEGORY_SLUG = 'cards-and-postcards';

    public const RECOMMENDED_QUANTITY = 200;

    /**
     * One shared, stable four-image gallery selected from the supplied 4x3
     * source set. The selection is made once when the assets are added; it is
     * intentionally not randomized per request.
     *
     * @var list<string>
     */
    private const GALLERY = [
        '/images/products/postcards/gallery/postcard-4x3-01.png',
        '/images/products/postcards/gallery/postcard-4x3-02.png',
        '/images/products/postcards/gallery/postcard-4x3-03.png',
        '/images/products/postcards/gallery/postcard-4x3-04.png',
    ];

    /**
     * @var array<string, array{
     *     reference: string,
     *     name: string,
     *     paper_name: string,
     *     area_sq_m: float,
     *     unit_price: float,
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

    /**
     * @var array<string, array{
     *     label: string,
     *     unit_price: float,
     *     multipliers: array<string, float>
     * }>
     */
    private const PROCESS_SCHEDULES = [
        'rounded_corners' => [
            'label' => 'Rounded corners',
            'unit_price' => 3.5,
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

    public static function isPostcardProduct(string $slug): bool
    {
        return isset(self::PRODUCTS[$slug]);
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
        return self::GALLERY;
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
            'gallery' => self::GALLERY,
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
        $paperBasePrice = $definition['unit_price'] * $paperMultipliers[(string) $startQuantity];

        $processes = [];

        foreach (['rounded_corners', 'uv_finish', 'hot_foil', 'cold_foil', '3d_uv', 'custom_die_cut'] as $code) {
            $schedule = self::PROCESS_SCHEDULES[$code];
            $processes[] = [
                'name' => $schedule['label'],
                'code' => $code,
                'markup' => $schedule['unit_price'] * $schedule['multipliers']['50'],
                'rates' => self::discountRates($schedule['multipliers']),
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
                    'basePrice' => $paperBasePrice,
                    'startQuantity' => $startQuantity,
                    'recommendedQuantity' => min(self::RECOMMENDED_QUANTITY, max(array_map('intval', array_keys($paperMultipliers)))),
                    'paperRates' => self::discountRates($paperMultipliers),
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
