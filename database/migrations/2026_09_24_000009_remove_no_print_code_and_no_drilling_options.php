<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->select(['id', 'product_config', 'product_options'])
            ->orderBy('id')
            ->get()
            ->each(function (object $product): void {
                $updates = [];

                $config = $this->decode($product->product_config ?? null);
                $normalizedConfig = $this->normalizeConfig($config);

                if ($normalizedConfig !== $config) {
                    $updates['product_config'] = $this->encode($normalizedConfig);
                }

                $legacy = $this->decode($product->product_options ?? null);
                $normalizedLegacy = $this->normalizeOptionGroups($legacy);

                if ($normalizedLegacy !== $legacy) {
                    $updates['product_options'] = $this->encode($normalizedLegacy);
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
        // Removed option values are intentionally not restored on rollback.
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function normalizeConfig(array $config): array
    {
        if (is_array($config['options'] ?? null)) {
            $config['options'] = $this->normalizeOptionGroups($config['options']);
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function normalizeOptionGroups(array $options): array
    {
        if (is_array($options['option_groups'] ?? null)) {
            foreach ($options['option_groups'] as $index => $group) {
                if (! is_array($group) || ! is_string($group['key'] ?? null)) {
                    continue;
                }

                $options['option_groups'][$index] = $this->normalizeOptionGroup(
                    $group,
                    $group['key'],
                );
            }
        }

        foreach ($options as $groupKey => $group) {
            if ($groupKey === 'option_groups' || ! is_string($groupKey) || ! is_array($group)) {
                continue;
            }

            $options[$groupKey] = $this->normalizeOptionGroup($group, $groupKey);
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $group
     * @return array<string, mixed>
     */
    private function normalizeOptionGroup(array $group, string $groupKey): array
    {
        $sentinel = $this->sentinelForGroup($groupKey);

        if ($sentinel === null) {
            return $group;
        }

        $isCanonicalGroup = array_key_exists('values', $group)
            || array_key_exists('type', $group)
            || array_key_exists('required', $group)
            || array_key_exists('default', $group)
            || array_key_exists('key', $group);
        $values = $isCanonicalGroup ? ($group['values'] ?? null) : $group;

        if (! is_array($values)) {
            return $group;
        }

        $values = array_values(array_filter(
            $values,
            fn (mixed $value): bool => ! $this->isSentinel($value, $sentinel),
        ));

        if (! $isCanonicalGroup) {
            return $values;
        }

        $group['values'] = $values;
        $group['required'] = false;
        $group['default'] = null;

        return $group;
    }

    private function sentinelForGroup(string $groupKey): ?string
    {
        $normalizedKey = str_replace(['-', ' '], '_', strtolower(trim($groupKey)));

        return match ($normalizedKey) {
            'drill', 'drilling' => 'no_drilling',
            'print_code_or_magnetic_stripe' => 'no_print_code_or_magnetic_stripe',
            'print_code_or_signature_stripe' => 'no_print_code_or_signature_stripe',
            default => str_contains($normalizedKey, 'print_code')
                ? 'no_print_code'
                : null,
        };
    }

    private function isSentinel(mixed $value, string $sentinel): bool
    {
        if (! is_array($value)) {
            return $this->normalizeToken($value) === $sentinel;
        }

        foreach (['code', 'label', 'name'] as $key) {
            if ($this->normalizeToken($value[$key] ?? null) === $sentinel) {
                return true;
            }
        }

        return false;
    }

    private function normalizeToken(mixed $value): string
    {
        return str_replace(['-', ' '], '_', strtolower(trim((string) $value)));
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
