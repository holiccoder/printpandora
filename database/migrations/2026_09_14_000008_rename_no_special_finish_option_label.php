<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OPTION_CODE = 'no_special_finish';

    public function up(): void
    {
        DB::table('products')
            ->select(['id', 'product_config', 'product_options'])
            ->orderBy('id')
            ->get()
            ->each(function (object $product): void {
                $updates = [];

                $config = $this->decode($product->product_config ?? null);

                if (
                    is_array($config['options'] ?? null)
                    && is_array($config['options']['special_finish'] ?? null)
                    && is_array($config['options']['special_finish']['values'] ?? null)
                ) {
                    $configValues =& $config['options']['special_finish']['values'];

                    if ($this->renameOptionLabels($configValues)) {
                        $updates['product_config'] = $this->encode($config);
                    }

                    unset($configValues);
                }

                $legacy = $this->decode($product->product_options ?? null);

                if (is_array($legacy['special_finish'] ?? null)) {
                    if (is_array($legacy['special_finish']['values'] ?? null)) {
                        $legacyValues =& $legacy['special_finish']['values'];
                    } else {
                        $legacyValues =& $legacy['special_finish'];
                    }

                    if ($this->renameOptionLabels($legacyValues)) {
                        $updates['product_options'] = $this->encode($legacy);
                    }

                    unset($legacyValues);
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
        // The new product-option title is intentionally not reverted.
    }

    /**
     * @param  mixed  $values
     */
    private function renameOptionLabels(mixed &$values): bool
    {
        if (! is_array($values)) {
            return false;
        }

        $changed = false;

        foreach ($values as &$value) {
            if (! is_array($value) || $this->normalizeCode($value['code'] ?? null) !== self::OPTION_CODE) {
                continue;
            }

            foreach (['label', 'name'] as $labelKey) {
                if (array_key_exists($labelKey, $value) && $value[$labelKey] !== 'No finish') {
                    $value[$labelKey] = 'No finish';
                    $changed = true;
                }
            }
        }
        unset($value);

        return $changed;
    }

    private function normalizeCode(mixed $value): string
    {
        return strtolower(str_replace('-', '_', trim((string) $value)));
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
