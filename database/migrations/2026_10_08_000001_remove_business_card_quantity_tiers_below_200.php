<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MINIMUM_QUANTITY = 200;

    private const PRODUCT_SLUGS = [
        'classic-standard-business-cards',
        'classic-special-business-cards',
    ];

    private const TIER_MAP_KEYS = [
        'quantity_discounts_percent',
        'paperRates',
        'paper_rates',
        'rates',
        'unitMultipliers',
        'unit_multipliers',
    ];

    public function up(): void
    {
        DB::table('products')
            ->whereIn('slug', self::PRODUCT_SLUGS)
            ->select(['id', 'product_config', 'product_options'])
            ->orderBy('id')
            ->get()
            ->each(function (object $product): void {
                $updates = [];

                foreach (['product_config', 'product_options'] as $column) {
                    $payload = $this->decode($product->{$column} ?? null);

                    if ($payload === null || ! $this->removeSmallerTiers($payload)) {
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

    public function down(): void
    {
        // Removed tier prices cannot be reconstructed safely.
    }

    /**
     * Remove price tiers below the product minimum from all pricing shapes.
     *
     * @param  array<string|int, mixed>  $payload
     */
    private function removeSmallerTiers(array &$payload): bool
    {
        $changed = false;

        foreach ($payload as $key => &$value) {
            if (in_array((string) $key, self::TIER_MAP_KEYS, true) && is_array($value)) {
                foreach ($value as $quantity => $_) {
                    if (is_numeric($quantity) && (int) $quantity < self::MINIMUM_QUANTITY) {
                        unset($value[$quantity]);
                        $changed = true;
                    }
                }
            } elseif ($key === 'quantity_price_table' && is_array($value)) {
                $filtered = array_values(array_filter(
                    $value,
                    static fn (mixed $row): bool => ! is_array($row)
                        || ! is_numeric($row['quantity'] ?? null)
                        || (int) $row['quantity'] >= self::MINIMUM_QUANTITY,
                ));

                if (count($filtered) !== count($value)) {
                    $value = $filtered;
                    $changed = true;
                }
            } elseif ($key === 'quantity_discounts' && is_array($value)) {
                $filtered = array_values(array_filter(
                    $value,
                    static fn (mixed $row): bool => ! is_array($row)
                        || ! is_numeric($row['quantity'] ?? $row['qty'] ?? null)
                        || (int) ($row['quantity'] ?? $row['qty']) >= self::MINIMUM_QUANTITY,
                ));

                if (count($filtered) !== count($value)) {
                    $value = $filtered;
                    $changed = true;
                }
            }

            if (is_array($value)) {
                $changed = $this->removeSmallerTiers($value) || $changed;
            }
        }
        unset($value);

        return $changed;
    }

    /**
     * @return array<string|int, mixed>|null
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
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
};
