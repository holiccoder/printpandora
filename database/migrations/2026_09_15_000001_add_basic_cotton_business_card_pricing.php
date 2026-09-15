<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PRODUCT_SLUG = 'basic-cotton-business-card';

    private const PRICING_RULE_ID = 'basic-cotton-business-card-default';

    /**
     * This is the executable portion of the uploaded pricing table plus its
     * generated rows for reference. The rows are deliberately not consumed
     * by either pricing calculator.
     *
     * @var array<string, mixed>
     */
    private const PRICING_PAYLOAD = [
        'packageName' => '棉纸-基础型',
        'basePrice' => 1.9,
        'startQuantity' => 200,
        'paperRates' => [
            '100' => 47.37,
            '200' => 68.42,
            '500' => 68.42,
            '1000' => 77.89,
            '2000' => 78.42,
            '3000' => 78.95,
            '4000' => 79.47,
            '5000' => 80,
            '10000' => 80,
        ],
        'processes' => [
            [
                'name' => '圆角',
                'code' => 'rounded_corners',
                'markup' => 0.14,
                'rates' => [
                    '100' => 50,
                    '200' => 75,
                    '500' => 78.57,
                    '1000' => 80,
                    '2000' => 81.43,
                    '3000' => 84.29,
                    '4000' => 85,
                    '5000' => 85.74,
                    '10000' => 85.74,
                ],
            ],
            [
                'name' => '激光',
                'code' => 'laser',
                'markup' => 1,
                'rates' => [
                    '100' => 50,
                    '200' => 75,
                    '500' => 80,
                    '1000' => 82,
                    '2000' => 82.5,
                    '3000' => 83,
                    '4000' => 83.5,
                    '5000' => 84,
                    '10000' => 84,
                ],
            ],
            [
                'name' => '滚边',
                'code' => 'edge_coloring',
                'markup' => 1,
                'rates' => [
                    '100' => 50,
                    '200' => 75,
                    '500' => 80,
                    '1000' => 82,
                    '2000' => 82.5,
                    '3000' => 83,
                    '4000' => 83.5,
                    '5000' => 84,
                    '10000' => 84,
                ],
            ],
            [
                'name' => '对裱',
                'code' => 'double_mounting',
                'markup' => 1,
                'rates' => [
                    '100' => 50,
                    '200' => 75,
                    '500' => 80,
                    '1000' => 82,
                    '2000' => 82.5,
                    '3000' => 83,
                    '4000' => 83.5,
                    '5000' => 84,
                    '10000' => 84,
                ],
            ],
            [
                'name' => '异形模切',
                'code' => 'custom_die_cut',
                'markup' => 1,
                'rates' => [
                    '100' => 50,
                    '200' => 75,
                    '500' => 80,
                    '1000' => 82,
                    '2000' => 82.5,
                    '3000' => 83,
                    '4000' => 83.5,
                    '5000' => 84,
                    '10000' => 84,
                ],
            ],
        ],
        'rows' => [
            [
                'quantity' => 200,
                'preUnitPrice' => 6.04,
                'unitPrice' => 1.635,
                'totalPrice' => 327.004,
                'effectiveDiscountRate' => 72.9301,
            ],
            [
                'quantity' => 500,
                'preUnitPrice' => 6.04,
                'unitPrice' => 1.43,
                'totalPrice' => 715.011,
                'effectiveDiscountRate' => 76.3241,
            ],
            [
                'quantity' => 1000,
                'preUnitPrice' => 6.04,
                'unitPrice' => 1.1681,
                'totalPrice' => 1168.09,
                'effectiveDiscountRate' => 80.6608,
            ],
            [
                'quantity' => 2000,
                'preUnitPrice' => 6.04,
                'unitPrice' => 1.136,
                'totalPrice' => 2272.036,
                'effectiveDiscountRate' => 81.1918,
            ],
            [
                'quantity' => 3000,
                'preUnitPrice' => 6.04,
                'unitPrice' => 1.1019,
                'totalPrice' => 3305.832,
                'effectiveDiscountRate' => 81.7559,
            ],
            [
                'quantity' => 4000,
                'preUnitPrice' => 6.04,
                'unitPrice' => 1.0711,
                'totalPrice' => 4284.28,
                'effectiveDiscountRate' => 82.2671,
            ],
            [
                'quantity' => 5000,
                'preUnitPrice' => 6.04,
                'unitPrice' => 1.04,
                'totalPrice' => 5199.82,
                'effectiveDiscountRate' => 82.7821,
            ],
            [
                'quantity' => 10000,
                'preUnitPrice' => 6.04,
                'unitPrice' => 1.04,
                'totalPrice' => 10399.64,
                'effectiveDiscountRate' => 82.7821,
            ],
        ],
    ];

    public function up(): void
    {
        $product = DB::table('products')
            ->select(['id', 'product_config'])
            ->where('slug', self::PRODUCT_SLUG)
            ->first();

        if (! $product) {
            return;
        }

        $config = $this->decode($product->product_config ?? null);
        $pricing = is_array($config['pricing'] ?? null) ? $config['pricing'] : [];

        $config['pricing'] = array_replace($pricing, [
            'mode' => 'rule_based',
            'currency' => 'USD',
            'total_rounding' => 'nearest_integer',
            'scenarios' => [],
            'quantity_price_table' => [],
            'rules' => [[
                'id' => self::PRICING_RULE_ID,
                'match' => [],
                'pricing' => self::PRICING_PAYLOAD,
            ]],
        ]);

        DB::table('products')
            ->where('id', $product->id)
            ->update(['product_config' => $this->encode($config)]);
    }

    public function down(): void
    {
        $product = DB::table('products')
            ->select(['id', 'product_config'])
            ->where('slug', self::PRODUCT_SLUG)
            ->first();

        if (! $product) {
            return;
        }

        $config = $this->decode($product->product_config ?? null);
        $pricing = is_array($config['pricing'] ?? null) ? $config['pricing'] : [];
        $rules = is_array($pricing['rules'] ?? null) ? $pricing['rules'] : [];
        $remainingRules = array_values(array_filter(
            $rules,
            static fn (mixed $rule): bool => ! is_array($rule)
                || ($rule['id'] ?? null) !== self::PRICING_RULE_ID,
        ));

        if (count($remainingRules) === count($rules)) {
            return;
        }

        $pricing['rules'] = $remainingRules;
        $config['pricing'] = $pricing;

        DB::table('products')
            ->where('id', $product->id)
            ->update(['product_config' => $this->encode($config)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(mixed $encoded): array
    {
        if (is_array($encoded)) {
            return $encoded;
        }

        if (! is_string($encoded) || trim($encoded) === '') {
            return [];
        }

        $decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function encode(array $value): string
    {
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }
};
