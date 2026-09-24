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
                $normalizedLegacy = $this->normalizeLegacyPayload($legacy);

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
        // The removed sentinel and its old defaults are intentionally not
        // restored on rollback.
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeConfig(array $config): array
    {
        if (is_array($config['options'] ?? null)) {
            $config['options'] = $this->normalizeOptionMap($config['options']);
        }

        if (is_array($config['media'] ?? null)) {
            $config['media'] = $this->normalizeGalleryContainer($config['media']);
        }

        return $this->normalizeGalleryContainer($config);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeLegacyPayload(array $payload): array
    {
        return $this->normalizeGalleryContainer(
            $this->normalizeOptionMap($payload),
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function normalizeOptionMap(array $options): array
    {
        if (array_key_exists('special_finish', $options)) {
            $options['special_finish'] = $this->normalizeSpecialFinishGroup(
                $options['special_finish'],
            );
        }

        if (is_array($options['option_groups'] ?? null)) {
            foreach ($options['option_groups'] as $index => $group) {
                if (is_array($group) && ($group['key'] ?? null) === 'special_finish') {
                    $options['option_groups'][$index] = $this->normalizeSpecialFinishGroup($group);
                }
            }
        }

        return $options;
    }

    private function normalizeSpecialFinishGroup(mixed $group): mixed
    {
        if (! is_array($group)) {
            return $group;
        }

        $isCanonicalGroup = array_key_exists('values', $group)
            || array_key_exists('type', $group)
            || array_key_exists('required', $group)
            || array_key_exists('default', $group);

        if ($isCanonicalGroup && ! array_key_exists('values', $group)) {
            return $group;
        }

        $values = $isCanonicalGroup ? ($group['values'] ?? null) : $group;

        if (! is_array($values)) {
            return $group;
        }

        $hadNoFinishValue = $isCanonicalGroup
            && $this->isNoSpecialFinishValue($group['default'] ?? null);
        $filteredValues = [];

        foreach ($values as $value) {
            if ($this->isNoSpecialFinishValue($value)) {
                $hadNoFinishValue = true;

                continue;
            }

            $filteredValues[] = $value;
        }

        if (! $isCanonicalGroup) {
            return array_values($filteredValues);
        }

        $group['values'] = array_values($filteredValues);

        if ($hadNoFinishValue || $this->hasFoilValues($filteredValues)) {
            $group['type'] = 'multi_select';
            $group['required'] = false;
            $group['default'] = [];
        }

        return $group;
    }

    /**
     * Normalize gallery rules in canonical media and legacy gallery payloads.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeGalleryContainer(array $payload): array
    {
        foreach (['gallery_rules', 'galleries'] as $key) {
            if (! is_array($payload[$key] ?? null)) {
                continue;
            }

            foreach ($payload[$key] as $index => $rule) {
                if (! is_array($rule) || ! is_array($rule['match'] ?? null)) {
                    continue;
                }

                if ($this->isNoSpecialFinishValue($rule['match']['special_finish'] ?? null)) {
                    unset($rule['match']['special_finish']);
                    $payload[$key][$index] = $rule;
                }
            }
        }

        return $payload;
    }

    private function isNoSpecialFinishValue(mixed $value): bool
    {
        if (! is_array($value)) {
            return in_array($this->normalizeToken($value), [
                'none',
                'no',
                'no_finish',
                'no_special_finish',
                'no_foil',
            ], true);
        }

        if (array_is_list($value)) {
            foreach ($value as $item) {
                if ($this->isNoSpecialFinishValue($item)) {
                    return true;
                }
            }

            return false;
        }

        foreach (['code', 'name', 'label'] as $key) {
            if (in_array($this->normalizeToken($value[$key] ?? null), [
                'none',
                'no',
                'no_finish',
                'no_special_finish',
                'no_foil',
            ], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function hasFoilValues(array $values): bool
    {
        foreach ($values as $value) {
            $text = is_array($value)
                ? strtolower(implode(' ', array_filter([
                    (string) ($value['code'] ?? ''),
                    (string) ($value['name'] ?? ''),
                    (string) ($value['label'] ?? ''),
                    (string) ($value['description'] ?? ''),
                ])))
                : strtolower((string) $value);

            if (
                str_contains($text, 'foil')
                || preg_match('/(^|[ _-])(hot|cold)(?:[ _-]|$)/', $text) === 1
            ) {
                return true;
            }
        }

        return false;
    }

    private function normalizeToken(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

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
