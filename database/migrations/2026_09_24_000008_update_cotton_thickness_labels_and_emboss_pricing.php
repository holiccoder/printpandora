<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const PRODUCT_SLUGS = [
        'basic-cotton-business-card',
        'classic-cotton-business-card',
        'premium-cotton-business-card',
        'luxe-cotton-business-card',
        'grand-cotton-business-card',
    ];

    /**
     * @var array<string, string>
     */
    private const THICKNESS_LABELS = [
        '300_360g' => '15.8-24pt',
        '360_450g' => '24-35.4pt',
        '450_700g' => '35.4-44pt',
    ];

    /**
     * @var list<string>
     */
    private const SPECIAL_FINISH_CODES = [
        'laser',
        'edge_coloring',
        'double_mounting',
        'custom_die_cut',
    ];

    public function up(): void
    {
        DB::table('products')
            ->whereIn('slug', self::PRODUCT_SLUGS)
            ->get()
            ->each(function (object $product): void {
                $updates = [];

                $config = $this->decode($product->product_config ?? null);
                $configChanged = false;

                if ($config !== []) {
                    $options = is_array($config['options'] ?? null)
                        ? $config['options']
                        : [];
                    $pricing = is_array($config['pricing'] ?? null)
                        ? $config['pricing']
                        : [];

                    $configChanged = $this->updateThicknessLabels($options);
                    $configChanged = $this->addEmbossPricing($pricing) || $configChanged;

                    if ($configChanged) {
                        $config['options'] = $options;
                        $config['pricing'] = $pricing;
                        $updates['product_config'] = $this->encode($config);
                    }
                }

                $legacy = $this->decode($product->product_options ?? null);
                $legacyChanged = false;

                if ($legacy !== []) {
                    $legacyChanged = $this->updateThicknessLabels($legacy);
                    $legacyChanged = $this->addEmbossPricing($legacy) || $legacyChanged;

                    if ($legacyChanged) {
                        $updates['product_options'] = $this->encode($legacy);
                    }
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
        // The updated cotton labels and pricing are intentionally retained.
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function updateThicknessLabels(array &$options): bool
    {
        $group = $options['thickness'] ?? null;

        if (! is_array($group)) {
            return false;
        }

        $isCanonicalGroup = array_key_exists('values', $group);
        $values = $isCanonicalGroup && is_array($group['values'] ?? null)
            ? $group['values']
            : $group;
        $changed = false;

        foreach ($values as &$value) {
            if (! is_array($value)) {
                continue;
            }

            $code = (string) ($value['code'] ?? '');
            $label = self::THICKNESS_LABELS[$code] ?? null;

            if ($label === null) {
                continue;
            }

            if (array_key_exists('label', $value)) {
                $changed = $changed || $value['label'] !== $label;
                $value['label'] = $label;
            }

            if (array_key_exists('name', $value)) {
                $changed = $changed || $value['name'] !== $label;
                $value['name'] = $label;
            }

            if (! array_key_exists('label', $value) && ! array_key_exists('name', $value)) {
                $value['label'] = $label;
                $changed = true;
            }
        }
        unset($value);

        if ($isCanonicalGroup) {
            $group['values'] = $values;
            $options['thickness'] = $group;
        } else {
            $options['thickness'] = $values;
        }

        return $changed;
    }

    /**
     * Add emboss/deboss anywhere a cotton pricing payload already prices the
     * other named special finishes. This supports both the canonical rule
     * shape and the older scenario/pricing_data shape.
     *
     * @param  array<string, mixed>  $pricing
     */
    private function addEmbossPricing(array &$pricing): bool
    {
        $changed = false;

        if (is_array($pricing['processes'] ?? null)) {
            $changed = $this->addEmbossProcess($pricing['processes']) || $changed;
        }

        foreach (['rules', 'scenarios', 'pricing_data'] as $key) {
            if (! is_array($pricing[$key] ?? null)) {
                continue;
            }

            foreach ($pricing[$key] as &$entry) {
                if (is_array($entry)) {
                    $changed = $this->addEmbossPricing($entry) || $changed;
                }
            }
            unset($entry);
        }

        if (is_array($pricing['pricing'] ?? null)) {
            $changed = $this->addEmbossPricing($pricing['pricing']) || $changed;
        }

        return $changed;
    }

    /**
     * @param  array<int, mixed>  $processes
     */
    private function addEmbossProcess(array &$processes): bool
    {
        $source = null;

        foreach ($processes as $process) {
            if (! is_array($process)) {
                continue;
            }

            $code = $this->processCode($process);

            if ($code === 'emboss') {
                return false;
            }

            if ($source === null && in_array($code, self::SPECIAL_FINISH_CODES, true)) {
                $source = $process;
            }
        }

        if ($source === null) {
            return false;
        }

        $emboss = $source;
        $emboss['code'] = 'emboss';

        if (array_key_exists('name', $emboss)) {
            $emboss['name'] = 'Emboss / Deboss';
        }

        if (array_key_exists('label', $emboss)) {
            $emboss['label'] = 'Emboss / Deboss';
        }

        $processes[] = $emboss;

        return true;
    }

    /**
     * @param  array<string, mixed>  $process
     */
    private function processCode(array $process): string
    {
        $value = $process['code'] ?? $process['name'] ?? $process['label'] ?? '';
        $code = Str::slug((string) $value, '_');

        return $code === 'emboss_deboss' ? 'emboss' : $code;
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
