<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const FINISH_LABELS = [
        'matte' => 'Matte',
        'gloss' => 'Gloss',
        'soft_touch_film' => 'Soft-Touch',
        'matte_lamination' => 'Matte',
        'gloss_lamination' => 'Gloss',
        'soft_touch_lamination' => 'Soft-Touch',
    ];

    public function up(): void
    {
        Product::query()
            ->select(['id', 'product_config', 'product_options'])
            ->orderBy('id')
            ->get()
            ->each(function (Product $product): void {
                $config = is_array($product->product_config) ? $product->product_config : [];
                $legacy = is_array($product->product_options) ? $product->product_options : [];
                $normalizedConfig = $this->normalizeConfig($config);
                $normalizedLegacy = $this->normalizeOptions($legacy);
                $updates = [];

                if ($normalizedConfig !== $config) {
                    $updates['product_config'] = $normalizedConfig;
                }

                if ($normalizedLegacy !== $legacy) {
                    $updates['product_options'] = $normalizedLegacy;
                }

                if ($updates !== []) {
                    $product->forceFill($updates)->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        // The shorter labels are the canonical storefront values and are not
        // reverted because the previous labels varied across product sources.
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function normalizeConfig(array $config): array
    {
        if (is_array($config['options'] ?? null)) {
            $config['options'] = $this->normalizeOptions($config['options']);
        }

        return $config;
    }

    /**
     * Normalize both canonical option groups and legacy flat option arrays.
     * Only paper-finish/texture groups are touched so unrelated labels such as
     * foil names and pricing process names remain unchanged.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function normalizeOptions(array $options): array
    {
        foreach (['paper_finish', 'texture'] as $groupKey) {
            if (array_key_exists($groupKey, $options)) {
                $options[$groupKey] = $this->normalizeGroup($options[$groupKey]);
            }
        }

        if (is_array($options['option_groups'] ?? null)) {
            foreach ($options['option_groups'] as $index => $group) {
                if (! is_array($group) || ! in_array($group['key'] ?? null, ['paper_finish', 'texture'], true)) {
                    continue;
                }

                $options['option_groups'][$index] = $this->normalizeCanonicalGroup($group);
            }
        }

        return $options;
    }

    private function normalizeGroup(mixed $group): mixed
    {
        if (! is_array($group)) {
            return $group;
        }

        if (array_key_exists('values', $group)) {
            return $this->normalizeCanonicalGroup($group);
        }

        return array_values($this->normalizeValues($group, true));
    }

    /**
     * @param  array<string, mixed>  $group
     * @return array<string, mixed>
     */
    private function normalizeCanonicalGroup(array $group): array
    {
        if (is_array($group['values'] ?? null)) {
            $group['values'] = array_values($this->normalizeValues($group['values'], false));
        }

        return $group;
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @return array<int|string, mixed>
     */
    private function normalizeValues(array $values, bool $legacy): array
    {
        foreach ($values as $index => $value) {
            if (! is_array($value)) {
                continue;
            }

            $label = self::FINISH_LABELS[(string) ($value['code'] ?? '')] ?? null;

            if ($label === null) {
                continue;
            }

            if (array_key_exists('label', $value) || ! $legacy) {
                $value['label'] = $label;
            }

            if (array_key_exists('name', $value) || $legacy) {
                $value['name'] = $label;
            }

            $values[$index] = $value;
        }

        return $values;
    }
};
