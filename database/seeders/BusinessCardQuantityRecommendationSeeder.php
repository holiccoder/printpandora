<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessCardQuantityRecommendationSeeder extends Seeder
{
    private const START_QUANTITY = 50;

    private const RECOMMENDED_QUANTITY = 200;

    /** @var array<string, int> */
    private const PRODUCT_START_QUANTITIES = [
        'classic-standard-business-cards' => 200,
        'classic-special-business-cards' => 200,
    ];

    /** @var array<int, string> */
    private const TIER_MAP_KEYS = [
        'quantity_discounts_percent',
        'paperRates',
        'paper_rates',
        'rates',
        'unitMultipliers',
        'unit_multipliers',
    ];

    public function run(): void
    {
        DB::table('products')
            ->leftJoin('product_categories', 'product_categories.id', '=', 'products.product_category_id')
            ->where(function ($query): void {
                $query
                    ->where('product_categories.slug', 'like', '%business-cards%')
                    ->orWhere('products.slug', 'like', '%business-card%')
                    ->orWhere('products.slug', 'like', '%pvc-card%');
            })
            ->select(['products.id', 'products.slug', 'products.product_config', 'products.product_options'])
            ->orderBy('products.id')
            ->get()
            ->each(function (object $product): void {
                $updates = [];
                $startQuantity = self::PRODUCT_START_QUANTITIES[$product->slug]
                    ?? self::START_QUANTITY;

                foreach (['product_config', 'product_options'] as $column) {
                    $payload = $this->decode($product->{$column} ?? null);

                    if ($payload === null || ! $this->normalize($payload, $startQuantity)) {
                        continue;
                    }

                    $updates[$column] = $this->encode($payload);
                }

                if ($updates !== []) {
                    DB::table('products')
                        ->where('id', $product->id)
                        ->update($updates);
                }
            });
    }

    /**
     * Normalize one pricing object and recurse through nested legacy shapes.
     *
     * @param  array<string|int, mixed>  $payload
     */
    private function normalize(array &$payload, int $startQuantity): bool
    {
        $changed = false;
        $startKey = array_key_exists('startQuantity', $payload)
            ? 'startQuantity'
            : (array_key_exists('start_quantity', $payload) ? 'start_quantity' : null);

        if ($startKey !== null && is_numeric($payload[$startKey])) {
            $startValue = is_string($payload[$startKey])
                ? (string) $startQuantity
                : $startQuantity;

            if ($payload[$startKey] !== $startValue) {
                $payload[$startKey] = $startValue;
                $changed = true;
            }

            $recommendedKey = $startKey === 'startQuantity'
                ? 'recommendedQuantity'
                : 'recommended_quantity';

            if (($payload[$recommendedKey] ?? null) !== self::RECOMMENDED_QUANTITY) {
                $payload[$recommendedKey] = self::RECOMMENDED_QUANTITY;
                $changed = true;
            }
        }

        foreach ($payload as $key => &$value) {
            if (
                in_array((string) $key, self::TIER_MAP_KEYS, true)
                && is_array($value)
            ) {
                foreach ($value as $quantity => $_) {
                    if (is_numeric($quantity) && (int) $quantity < $startQuantity) {
                        unset($value[$quantity]);
                        $changed = true;
                    }
                }
            } elseif ($key === 'quantity_price_table' && is_array($value)) {
                $changed = $this->removeSmallerRows($value, $startQuantity) || $changed;
                $changed = $this->normalizeQuantityPriceTable($value) || $changed;
            } elseif (
                in_array((string) $key, ['quantity_discounts', 'quantity_tiers'], true)
                && is_array($value)
            ) {
                $changed = $this->removeSmallerRows($value, $startQuantity) || $changed;
            }

            if (is_array($value)) {
                $changed = $this->normalize($value, $startQuantity) || $changed;
            }
        }
        unset($value);

        return $changed;
    }

    /**
     * @param  array<int|string, mixed>  $rows
     */
    private function removeSmallerRows(array &$rows, int $startQuantity): bool
    {
        $filtered = array_values(array_filter(
            $rows,
            static fn (mixed $row): bool => ! is_array($row)
                || ! is_numeric($row['quantity'] ?? $row['qty'] ?? null)
                || (int) ($row['quantity'] ?? $row['qty']) >= $startQuantity,
        ));

        if (count($filtered) === count($rows)) {
            return false;
        }

        $rows = $filtered;

        return true;
    }

    /**
     * Keep every explicit package while marking only the 200-card package as
     * recommended when a 200 row exists.
     *
     * @param  array<int|string, mixed>  $table
     */
    private function normalizeQuantityPriceTable(array &$table): bool
    {
        $hasRecommendedQuantity = false;

        foreach ($table as $row) {
            if (is_array($row) && (int) ($row['quantity'] ?? 0) === self::RECOMMENDED_QUANTITY) {
                $hasRecommendedQuantity = true;

                break;
            }
        }

        if (! $hasRecommendedQuantity) {
            return false;
        }

        $changed = false;

        foreach ($table as &$row) {
            if (! is_array($row)) {
                continue;
            }

            $isRecommended = (int) ($row['quantity'] ?? 0) === self::RECOMMENDED_QUANTITY;

            if (($row['is_recommended'] ?? null) !== $isRecommended) {
                $row['is_recommended'] = $isRecommended;
                $changed = true;
            }
        }
        unset($row);

        return $changed;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decode(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string|int, mixed>  $value
     */
    private function encode(array $value): string
    {
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }
}
