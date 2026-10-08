<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OPTION_CODE = 'both_sides_uv';

    public function up(): void
    {
        DB::table('products')
            ->select(['id', 'product_config', 'product_options'])
            ->orderBy('id')
            ->get()
            ->each(function (object $product): void {
                $updates = [];

                $config = $this->decode($product->product_config ?? null);
                $configOptions = is_array($config['options'] ?? null) ? $config['options'] : [];

                if ($this->renameLabels($configOptions)) {
                    $config['options'] = $configOptions;
                    $updates['product_config'] = $this->encode($config);
                }

                $legacyOptions = $this->decode($product->product_options ?? null);

                if ($this->renameLabels($legacyOptions)) {
                    $updates['product_options'] = $this->encode($legacyOptions);
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
        // The updated product swatch wording is intentionally not reverted.
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function renameLabels(array &$values): bool
    {
        $changed = false;

        foreach ($values as &$value) {
            if (! is_array($value)) {
                continue;
            }

            if (($value['code'] ?? null) === self::OPTION_CODE) {
                foreach (['label', 'name', 'description'] as $key) {
                    if (! is_string($value[$key] ?? null)) {
                        continue;
                    }

                    $renamed = str_ireplace('both sides', 'double sides', $value[$key]);

                    if ($renamed !== $value[$key]) {
                        $value[$key] = $renamed;
                        $changed = true;
                    }
                }
            }

            if ($this->renameLabels($value)) {
                $changed = true;
            }
        }

        unset($value);

        return $changed;
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
