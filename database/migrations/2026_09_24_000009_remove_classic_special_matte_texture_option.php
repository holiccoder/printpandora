<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PRODUCT_SLUG = 'classic-special-business-cards';

    public function up(): void
    {
        $product = DB::table('products')
            ->where('slug', self::PRODUCT_SLUG)
            ->first();

        if ($product === null) {
            return;
        }

        $updates = [];

        $config = $this->decode($product->product_config ?? null);
        $normalizedConfig = $this->removeCanonicalMatte($config);

        if ($normalizedConfig !== $config) {
            $updates['product_config'] = $this->encode($normalizedConfig);
        }

        $legacy = $this->decode($product->product_options ?? null);
        $normalizedLegacy = $this->removeLegacyMatte($legacy);

        if ($normalizedLegacy !== $legacy) {
            $updates['product_options'] = $this->encode($normalizedLegacy);
        }

        if ($updates !== []) {
            DB::table('products')
                ->where('id', $product->id)
                ->update($updates);
        }
    }

    public function down(): void
    {
        // Removed product options are intentionally not restored on rollback.
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function removeCanonicalMatte(array $config): array
    {
        $options = is_array($config['options'] ?? null) ? $config['options'] : [];
        $group = is_array($options['texture'] ?? null) ? $options['texture'] : null;
        $values = is_array($group['values'] ?? null) ? $group['values'] : null;

        if ($group === null || $values === null) {
            return $config;
        }

        $filteredValues = array_values(array_filter(
            $values,
            fn (mixed $value): bool => ! $this->isMatte($value),
        ));

        $group['values'] = $filteredValues;

        $valueCodes = array_values(array_filter(
            array_map(
                static fn (mixed $value): string => is_array($value)
                    ? (string) ($value['code'] ?? '')
                    : '',
                $filteredValues,
            ),
            static fn (string $code): bool => $code !== '',
        ));

        if (! in_array((string) ($group['default'] ?? ''), $valueCodes, true)) {
            $group['default'] = $valueCodes[0] ?? null;
        }

        $options['texture'] = $group;
        $config['options'] = $options;

        return $config;
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @return array<string, mixed>
     */
    private function removeLegacyMatte(array $legacy): array
    {
        $values = $legacy['texture'] ?? null;

        if (! is_array($values)) {
            return $legacy;
        }

        $legacy['texture'] = array_values(array_filter(
            $values,
            fn (mixed $value): bool => ! $this->isMatte($value),
        ));

        return $legacy;
    }

    private function isMatte(mixed $value): bool
    {
        if (! is_array($value)) {
            return strtolower(trim((string) $value)) === 'matte';
        }

        foreach (['code', 'name', 'label'] as $key) {
            if (strtolower(trim((string) ($value[$key] ?? ''))) === 'matte') {
                return true;
            }
        }

        return false;
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
