<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessCardQuantityRecommendationSeeder extends Seeder
{
    private const START_QUANTITY = 50;

    private const RECOMMENDED_QUANTITY = 200;

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
            ->select(['products.id', 'products.product_config', 'products.product_options'])
            ->orderBy('products.id')
            ->get()
            ->each(function (object $product): void {
                $updates = [];

                foreach (['product_config', 'product_options'] as $column) {
                    $payload = $this->decode($product->{$column} ?? null);

                    if ($payload === null || ! $this->normalize($payload)) {
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
    private function normalize(array &$payload): bool
    {
        $changed = false;
        $startKey = array_key_exists('startQuantity', $payload)
            ? 'startQuantity'
            : (array_key_exists('start_quantity', $payload) ? 'start_quantity' : null);

        if ($startKey !== null && is_numeric($payload[$startKey])) {
            $startValue = is_string($payload[$startKey])
                ? (string) self::START_QUANTITY
                : self::START_QUANTITY;

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

        if (is_array($payload['quantity_price_table'] ?? null)) {
            $changed = $this->normalizeQuantityPriceTable($payload['quantity_price_table']) || $changed;
        }

        foreach ($payload as &$value) {
            if (is_array($value)) {
                $changed = $this->normalize($value) || $changed;
            }
        }
        unset($value);

        return $changed;
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
