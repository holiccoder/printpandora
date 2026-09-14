<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Design-service content is not a purchasable business-card product and
     * is intentionally excluded from this cleanup.
     *
     * @var list<string>
     */
    private const BUSINESS_CARD_PRODUCT_SLUGS = [
        'basic-cotton-business-card',
        'classic-cotton-business-card',
        'premium-cotton-business-card',
        'luxe-cotton-business-card',
        'grand-cotton-business-card',
        'super-business-cards',
        'luxe-business-cards',
        'basic-pvc-card',
        'standard-pvc-card',
        'premium-pvc-card',
        'classic-metal-business-cards',
        'premium-metal-business-cards',
        'luxe-metal-business-cards',
        'classic-standard-business-cards',
        'classic-special-business-cards',
        'classic-quality-business-cards',
        'classic-solid-business-cards',
    ];

    /**
     * @var array<string, array<string, string>>
     */
    private const PRODUCT_COPY = [
        'premium-pvc-card' => [
            'subtitle' => '0.03 inches premium PVC cards with optional print code.',
            'description' => '<p>Premium PVC cards at 0.03 inches combine durable construction with optional print-code functionality.</p>',
        ],
        'premium-metal-business-cards' => [
            'subtitle' => 'Premium metal business cards with engraving, color, or plating options.',
        ],
        'luxe-metal-business-cards' => [
            'description' => '<p>Luxe metal business cards designed for standout introductions, with premium engraving, color, and plating options.</p>',
        ],
    ];

    /**
     * @var list<string>
     */
    private const LEGACY_OPTION_GROUPS = [
        'sizes',
        'corners',
        'paper_finish',
        'special_finish',
        'print_code',
        'drill',
        'finish',
        'texture',
        'thickness',
        'print_code_or_signature_stripe',
        'print_code_or_magnetic_stripe',
        'with_nfc',
        'nfc',
        'no_nfc',
    ];

    public function up(): void
    {
        $products = DB::table('products')
            ->select([
                'id',
                'slug',
                'subtitle',
                'description',
                'product_config',
                'product_options',
            ])
            ->whereIn('slug', self::BUSINESS_CARD_PRODUCT_SLUGS)
            ->get();

        foreach ($products as $product) {
            $updates = [];

            $config = $this->decode($product->product_config ?? null);
            $originalConfig = $config;

            if (is_array($config['options'] ?? null)) {
                $config['options'] = $this->removeNfcOptions($config['options']);
            }

            if (is_array($config['pricing'] ?? null)) {
                $config['pricing'] = $this->removeNfcPricingProcesses($config['pricing']);
            }

            $media = is_array($config['media'] ?? null) ? $config['media'] : [];

            if (is_array($media['gallery_rules'] ?? null)) {
                $media['gallery_rules'] = array_values(array_filter(
                    $media['gallery_rules'],
                    fn (mixed $rule): bool => ! $this->galleryRuleUsesNfc($rule),
                ));
            }

            if ($media !== []) {
                $config['media'] = $media;
            }

            $copy = self::PRODUCT_COPY[$product->slug] ?? [];

            if (is_array($config['product'] ?? null)) {
                foreach ($copy as $field => $replacement) {
                    if ($this->containsNfc($config['product'][$field] ?? null)) {
                        $config['product'][$field] = $replacement;
                    }
                }
            }

            if ($config !== $originalConfig) {
                $updates['product_config'] = $this->encode($config);
            }

            $legacy = $this->decode($product->product_options ?? null);
            $originalLegacy = $legacy;
            $legacy = $this->removeLegacyNfcOptions($legacy);

            if (is_array($legacy['pricing_data'] ?? null)) {
                $legacy['pricing_data'] = $this->removeNfcPricingProcesses([
                    'pricing_data' => $legacy['pricing_data'],
                ])['pricing_data'];
            }

            if (is_array($legacy['pricing'] ?? null)) {
                $legacy['pricing'] = $this->removeNfcPricingProcesses($legacy['pricing']);
            }

            if ($legacy !== $originalLegacy) {
                $updates['product_options'] = $this->encode($legacy);
            }

            foreach ($copy as $field => $replacement) {
                if ($this->containsNfc($product->{$field} ?? null)) {
                    $updates[$field] = $replacement;
                }
            }

            if ($updates !== []) {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update($updates);
            }
        }
    }

    public function down(): void
    {
        // Removed product options and copy are intentionally not restored.
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function removeNfcOptions(array $options): array
    {
        foreach (array_keys($options) as $groupKey) {
            if ($this->isNfcToken($groupKey)) {
                unset($options[$groupKey]);

                continue;
            }

            $group = $options[$groupKey] ?? null;

            if (! is_array($group)) {
                continue;
            }

            $isCanonicalGroup = array_key_exists('values', $group);
            $values = $isCanonicalGroup ? ($group['values'] ?? null) : $group;

            if (! is_array($values)) {
                continue;
            }

            $filteredValues = array_values(array_filter(
                $values,
                fn (mixed $value): bool => ! $this->isNfcOptionValue($value),
            ));

            if ($values !== [] && $filteredValues === []) {
                unset($options[$groupKey]);

                continue;
            }

            if ($isCanonicalGroup) {
                $group['values'] = $filteredValues;

                if ($this->isNfcOptionValue($group['default'] ?? null)) {
                    $group['default'] = $this->optionValueCode($filteredValues[0] ?? null);
                }

                $options[$groupKey] = $group;
            } else {
                $options[$groupKey] = $filteredValues;
            }
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $legacy
     * @return array<string, mixed>
     */
    private function removeLegacyNfcOptions(array $legacy): array
    {
        foreach (self::LEGACY_OPTION_GROUPS as $groupKey) {
            if (! array_key_exists($groupKey, $legacy)) {
                continue;
            }

            $filtered = $this->removeNfcOptions([$groupKey => $legacy[$groupKey]]);

            if (array_key_exists($groupKey, $filtered)) {
                $legacy[$groupKey] = $filtered[$groupKey];
            } else {
                unset($legacy[$groupKey]);
            }
        }

        if (is_array($legacy['options'] ?? null)) {
            $legacy['options'] = $this->removeNfcOptions($legacy['options']);
        }

        return $legacy;
    }

    /**
     * Recursively handle canonical scenarios/rules and legacy pricing_data.
     *
     * @param  array<string, mixed>  $pricing
     * @return array<string, mixed>
     */
    private function removeNfcPricingProcesses(array $pricing): array
    {
        if (is_array($pricing['processes'] ?? null)) {
            $pricing['processes'] = array_values(array_filter(
                $pricing['processes'],
                fn (mixed $process): bool => ! $this->isNfcProcess($process),
            ));
        }

        foreach (['scenarios', 'rules', 'pricing_data'] as $key) {
            if (! is_array($pricing[$key] ?? null)) {
                continue;
            }

            foreach ($pricing[$key] as $entryKey => $entry) {
                if (is_array($entry)) {
                    $pricing[$key][$entryKey] = $this->removeNfcPricingProcesses($entry);
                }
            }
        }

        if (is_array($pricing['pricing'] ?? null)) {
            $pricing['pricing'] = $this->removeNfcPricingProcesses($pricing['pricing']);
        }

        return $pricing;
    }

    private function galleryRuleUsesNfc(mixed $rule): bool
    {
        if (! is_array($rule) || ! is_array($rule['match'] ?? null)) {
            return false;
        }

        foreach ($rule['match'] as $value) {
            if (is_array($value)) {
                foreach ($value as $nestedValue) {
                    if ($this->isNfcToken($nestedValue)) {
                        return true;
                    }
                }

                continue;
            }

            if ($this->isNfcToken($value)) {
                return true;
            }
        }

        return false;
    }

    private function isNfcProcess(mixed $process): bool
    {
        if (is_scalar($process)) {
            return $this->isNfcToken($process);
        }

        if (! is_array($process)) {
            return false;
        }

        foreach (['code', 'name', 'label'] as $key) {
            if ($this->isNfcToken($process[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function isNfcOptionValue(mixed $value): bool
    {
        if (is_array($value)) {
            if (array_is_list($value) && ! array_key_exists('code', $value)) {
                foreach ($value as $nestedValue) {
                    if ($this->isNfcOptionValue($nestedValue)) {
                        return true;
                    }
                }
            }

            foreach (['code', 'label', 'name'] as $key) {
                if ($this->isNfcToken($value[$key] ?? null)) {
                    return true;
                }
            }

            return false;
        }

        return $this->isNfcToken($value);
    }

    private function isNfcToken(mixed $value): bool
    {
        if (! is_scalar($value) && $value !== null) {
            return false;
        }

        $token = str_replace(['-', ' '], '_', strtolower(trim((string) $value)));

        return in_array($token, ['nfc', 'with_nfc', 'no_nfc'], true);
    }

    private function optionValueCode(mixed $value): ?string
    {
        if (is_scalar($value)) {
            $code = trim((string) $value);

            return $code !== '' ? $code : null;
        }

        if (! is_array($value)) {
            return null;
        }

        $code = trim((string) ($value['code'] ?? ''));

        return $code !== '' ? $code : null;
    }

    private function containsNfc(mixed $value): bool
    {
        return is_string($value) && stripos($value, 'nfc') !== false;
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
