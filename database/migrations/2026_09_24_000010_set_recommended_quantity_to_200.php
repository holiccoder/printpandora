<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const RECOMMENDED_QUANTITY = 200;

    public function up(): void
    {
        DB::table('products')
            ->select(['id', 'product_config', 'product_options'])
            ->where(function ($query): void {
                $query
                    ->whereNotNull('product_config')
                    ->orWhereNotNull('product_options');
            })
            ->orderBy('id')
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

    public function down(): void
    {
        // The previous recommendation varied by product and cannot be
        // reconstructed safely once all products use the 200-card tier.
    }

    /**
     * Update both dynamic pricing starts and explicit fixed-tier flags in a
     * legacy payload. The recursive walk covers canonical configs as well as
     * the older pricing_data/scenarios shapes.
     *
     * @param  array<string|int, mixed>  $payload
     */
    private function normalize(array &$payload): bool
    {
        $changed = false;

        foreach ($payload as $key => &$value) {
            if (in_array((string) $key, ['startQuantity', 'start_quantity'], true)) {
                if (is_numeric($value) && (int) $value !== self::RECOMMENDED_QUANTITY) {
                    $value = is_string($value)
                        ? (string) self::RECOMMENDED_QUANTITY
                        : self::RECOMMENDED_QUANTITY;
                    $changed = true;
                }

                continue;
            }

            if ((string) $key === 'quantity_price_table' && is_array($value)) {
                $changed = $this->normalizeQuantityPriceTable($value) || $changed;

                continue;
            }

            if (is_array($value)) {
                $changed = $this->normalize($value) || $changed;
            }
        }
        unset($value);

        return $changed;
    }

    /**
     * Only rewrite explicit recommendations when the table contains a 200
     * row. Legacy single-row tables do not contain enough pricing data to
     * invent a new 200-card price.
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
