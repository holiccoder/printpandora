<?php

use App\Support\PostcardProductCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REMOVED_CODES = ['3d_uv', 'custom_die_cut'];

    public function up(): void
    {
        DB::table('products')
            ->whereIn('slug', PostcardProductCatalog::slugs())
            ->select(['id', 'product_config', 'product_options'])
            ->orderBy('id')
            ->get()
            ->each(function (object $product): void {
                $updates = [];

                foreach (['product_config', 'product_options'] as $column) {
                    $payload = $this->decode($product->{$column} ?? null);

                    if ($payload === null || ! $this->removeOptions($payload)) {
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
        // The removed choices are intentionally not restored by rollback.
    }

    /**
     * @param  array<string|int, mixed>  $payload
     */
    private function removeOptions(array &$payload): bool
    {
        $changed = false;

        foreach ($payload as $key => &$value) {
            if ((string) $key === 'special_finish' && is_array($value)) {
                if (is_array($value['values'] ?? null)) {
                    $filteredValues = array_values(array_filter(
                        $value['values'],
                        static fn (mixed $option): bool => ! is_array($option)
                            || ! in_array(
                                strtolower((string) ($option['code'] ?? '')),
                                self::REMOVED_CODES,
                                true,
                            ),
                    ));

                    if ($filteredValues !== $value['values']) {
                        $value['values'] = $filteredValues;
                        $changed = true;
                    }
                }

                if (is_array($value['default'] ?? null)) {
                    $filteredDefault = array_values(array_filter(
                        $value['default'],
                        static fn (mixed $option): bool => ! in_array(
                            strtolower((string) $option),
                            self::REMOVED_CODES,
                            true,
                        ),
                    ));

                    if ($filteredDefault !== $value['default']) {
                        $value['default'] = $filteredDefault;
                        $changed = true;
                    }
                }
            }

            if (is_array($value)) {
                $changed = $this->removeOptions($value) || $changed;
            }
        }
        unset($value);

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
};
