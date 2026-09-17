<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const UV_SWATCH_IMAGE = '/images/product-options/uv-swatch.png';

    public function up(): void
    {
        DB::table('products')
            ->select(['id', 'product_config', 'product_options'])
            ->get()
            ->each(function (object $product): void {
                $updates = [];

                $config = $this->decode($product->product_config ?? null);
                [$config, $configChanged] = $this->migrateCanonicalConfig($config);

                if ($configChanged) {
                    $updates['product_config'] = $this->encode($config);
                }

                $legacy = $this->decode($product->product_options ?? null);
                [$legacy, $legacyChanged] = $this->migrateLegacyOptions($legacy);

                if ($legacyChanged) {
                    $updates['product_options'] = $this->encode($legacy);
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
        // The new UV option group is intentionally not reversed on rollback.
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function migrateCanonicalConfig(array $config): array
    {
        $original = $config;
        $options = is_array($config['options'] ?? null) ? $config['options'] : [];
        [$options, $optionsChanged] = $this->splitCanonicalOptions($options);

        if ($optionsChanged) {
            $config['options'] = $options;
        }

        if (is_array($config['media']['gallery_rules'] ?? null)) {
            $config['media']['gallery_rules'] = $this->migrateRules(
                $config['media']['gallery_rules'],
            );
        }

        if (is_array($config['pricing']['rules'] ?? null)) {
            $config['pricing']['rules'] = $this->migrateRules(
                $config['pricing']['rules'],
            );
        }

        return [$config, $config !== $original];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function splitCanonicalOptions(array $options): array
    {
        $original = $options;
        $paperFinish = is_array($options['paper_finish'] ?? null)
            ? $options['paper_finish']
            : [];
        $values = is_array($paperFinish['values'] ?? null)
            ? $paperFinish['values']
            : [];
        [$remainingValues, $hasMatte, $hasGloss, $hasUv] = $this->splitValues($values);
        $existingUvGroup = is_array($options['uv_finish'] ?? null)
            ? $options['uv_finish']
            : [];
        $existingUvValues = is_array($existingUvGroup['values'] ?? null)
            ? $existingUvGroup['values']
            : [];
        $hasSeparateUv = $this->hasUvSideValues($existingUvValues);

        if (! $hasMatte || ! $hasGloss || (! $hasUv && ! $hasSeparateUv)) {
            $orderedOptions = $this->placeUvGroupAfterPaperFinish($options);

            return [$orderedOptions, $orderedOptions !== $options];
        }

        $paperFinish['values'] = $remainingValues;
        $paperFinish['required'] = false;

        if (in_array($this->optionToken($paperFinish['default'] ?? null), ['', 'uv', '3duv'], true)) {
            $paperFinish['default'] = 'matte';
        }

        $options['paper_finish'] = $paperFinish;
        $options['uv_finish'] = $this->uvGroup(
            $existingUvGroup,
            false,
        );

        $options = $this->placeUvGroupAfterPaperFinish($options);

        return [$options, $options !== $original];
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function migrateLegacyOptions(array $legacy): array
    {
        $original = $legacy;
        $values = is_array($legacy['paper_finish'] ?? null)
            ? $legacy['paper_finish']
            : [];
        [$remainingValues, $hasMatte, $hasGloss, $hasUv] = $this->splitValues($values);

        if ($hasMatte && $hasGloss && $hasUv) {
            $legacy['paper_finish'] = $remainingValues;
            $legacy['uv_finish'] = $this->uvGroup(
                is_array($legacy['uv_finish'] ?? null) ? $legacy['uv_finish'] : [],
                true,
            )['values'];
        }

        $legacy = $this->placeUvGroupAfterPaperFinish($legacy);

        foreach (['galleries', 'pricing_rules'] as $rulesKey) {
            if (is_array($legacy[$rulesKey] ?? null)) {
                $legacy[$rulesKey] = $this->migrateRules($legacy[$rulesKey]);
            }
        }

        return [$legacy, $legacy !== $original];
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array{0: array<int, mixed>, 1: bool, 2: bool, 3: bool}
     */
    private function splitValues(array $values): array
    {
        $remaining = [];
        $hasMatte = false;
        $hasGloss = false;
        $hasUv = false;

        foreach ($values as $value) {
            if (! is_array($value)) {
                $remaining[] = $value;

                continue;
            }

            $code = $this->optionToken($value['code'] ?? null);
            $label = $this->optionToken($value['label'] ?? $value['name'] ?? null);

            if (in_array($code, ['uv', '3duv'], true) || in_array($label, ['uv', '3duv'], true)) {
                $hasUv = true;

                continue;
            }

            $hasMatte = $hasMatte || $code === 'matte' || $label === 'matte';
            $hasGloss = $hasGloss || $code === 'gloss' || $label === 'gloss';
            $remaining[] = $value;
        }

        return [array_values($remaining), $hasMatte, $hasGloss, $hasUv];
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function hasUvSideValues(array $values): bool
    {
        $codes = [];

        foreach ($values as $value) {
            if (! is_array($value)) {
                continue;
            }

            $codes[] = $this->optionToken($value['code'] ?? $value['name'] ?? $value['label'] ?? null);
        }

        return in_array('singlesideuv', $codes, true)
            && in_array('bothsidesuv', $codes, true);
    }

    /**
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    private function uvGroup(array $existing, bool $legacy): array
    {
        $existingValues = is_array($existing['values'] ?? null)
            ? $existing['values']
            : [];
        $existingByCode = [];

        foreach ($existingValues as $value) {
            if (is_array($value) && isset($value['code'])) {
                $existingByCode[(string) $value['code']] = $value;
            }
        }

        $values = [];

        foreach ([
            'single_side_uv' => 'single side UV',
            'both_sides_uv' => 'both sides UV',
        ] as $code => $label) {
            $base = $legacy
                ? [
                    'name' => $label,
                    'code' => $code,
                    'swatch_image' => self::UV_SWATCH_IMAGE,
                ]
                : [
                    'label' => $label,
                    'code' => $code,
                    'swatch_image' => self::UV_SWATCH_IMAGE,
                ];

            $values[] = array_replace(
                $base,
                $existingByCode[$code] ?? [],
                $legacy
                    ? ['name' => $label, 'code' => $code, 'swatch_image' => self::UV_SWATCH_IMAGE]
                    : ['label' => $label, 'code' => $code, 'swatch_image' => self::UV_SWATCH_IMAGE],
            );
        }

        if ($legacy) {
            return [
                'values' => $values,
            ];
        }

        return array_replace(
            [
                'label' => 'UV',
                'type' => 'select',
                'required' => false,
                'default' => null,
            ],
            $existing,
            [
                'label' => 'UV',
                'type' => 'select',
                'required' => false,
                'default' => null,
                'values' => $values,
            ],
        );
    }

    /**
     * @param  array<int, mixed>  $rules
     * @return array<int, mixed>
     */
    private function migrateRules(array $rules): array
    {
        $uvRules = [];
        $otherRules = [];

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                $otherRules[] = $rule;

                continue;
            }

            $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];

            if (
                ! array_key_exists('paper_finish', $match)
                || ! in_array($this->optionToken($match['paper_finish']), ['uv', '3duv'], true)
            ) {
                if (array_key_exists('uv_finish', $match)) {
                    $uvRules[] = $rule;
                } else {
                    $otherRules[] = $rule;
                }

                continue;
            }

            unset($match['paper_finish']);

            foreach (['single_side_uv', 'both_sides_uv'] as $index => $uvCode) {
                $migratedRule = $rule;
                $migratedRule['match'] = [...$match, 'uv_finish' => $uvCode];

                if ($index === 1 && isset($rule['id'])) {
                    $migratedRule['id'] = (string) $rule['id'].'-both-sides';
                }

                $uvRules[] = $migratedRule;
            }
        }

        return array_values([...$uvRules, ...$otherRules]);
    }

    /**
     * Keep the optional UV group directly after Paper Finish in persisted
     * option maps, regardless of whether it already existed or was just
     * created by this migration.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function placeUvGroupAfterPaperFinish(array $options): array
    {
        if (! array_key_exists('paper_finish', $options) || ! array_key_exists('uv_finish', $options)) {
            return $options;
        }

        $uvGroup = $options['uv_finish'];
        $ordered = [];

        foreach ($options as $key => $value) {
            if ($key === 'uv_finish') {
                continue;
            }

            $ordered[$key] = $value;

            if ($key === 'paper_finish') {
                $ordered['uv_finish'] = $uvGroup;
            }
        }

        return $ordered;
    }

    private function optionToken(mixed $value): string
    {
        return str_replace(['-', ' ', '_'], '', strtolower(trim((string) $value)));
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
