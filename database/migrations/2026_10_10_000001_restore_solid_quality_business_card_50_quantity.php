<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PRODUCT_SLUGS = [
        'solid-quality-business-cards',
        'classic-solid-business-cards',
    ];

    private const START_QUANTITY = 50;

    private const RECOMMENDED_QUANTITY = 200;

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

                    if ($payload === null || ! $this->restoreStartQuantities($payload)) {
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
        // The prior product-specific starting quantity cannot be inferred safely.
    }

    /**
     * Restore the 50-card starting tier and keep the 200-card recommendation.
     *
     * @param  array<string|int, mixed>  $payload
     */
    private function restoreStartQuantities(array &$payload): bool
    {
        $changed = false;

        foreach ($payload as $key => &$value) {
            if (in_array((string) $key, ['startQuantity', 'start_quantity'], true) && is_numeric($value)) {
                $startValue = is_string($value)
                    ? (string) self::START_QUANTITY
                    : self::START_QUANTITY;

                if ($value !== $startValue) {
                    $value = $startValue;
                    $changed = true;
                }

                $recommendedKey = $key === 'startQuantity'
                    ? 'recommendedQuantity'
                    : 'recommended_quantity';
                $recommendedValue = is_string($value)
                    ? (string) self::RECOMMENDED_QUANTITY
                    : self::RECOMMENDED_QUANTITY;

                if (($payload[$recommendedKey] ?? null) !== $recommendedValue) {
                    $payload[$recommendedKey] = $recommendedValue;
                    $changed = true;
                }

                continue;
            }

            if (is_array($value)) {
                $changed = $this->restoreStartQuantities($value) || $changed;
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
