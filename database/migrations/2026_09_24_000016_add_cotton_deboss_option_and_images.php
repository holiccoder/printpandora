<?php

use App\Support\BusinessCardOptionCatalog;
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
     * @var list<string>
     */
    private const SPECIAL_FINISH_CODES = [
        'laser',
        'edge_coloring',
        'double_mounting',
        'custom_die_cut',
        'emboss',
    ];

    public function up(): void
    {
        DB::table('products')
            ->whereIn('slug', self::PRODUCT_SLUGS)
            ->get()
            ->each(function (object $product): void {
                $updates = [];
                $config = $this->decode($product->product_config ?? null);

                if ($config !== []) {
                    $options = BusinessCardOptionCatalog::normalize(
                        (string) $product->slug,
                        is_array($config['options'] ?? null) ? $config['options'] : [],
                    );

                    if ($options !== null) {
                        $config['options'] = $options;
                    }

                    $media = is_array($config['media'] ?? null) ? $config['media'] : [];
                    $media['gallery_rules'] = BusinessCardOptionCatalog::normalizeCottonGalleryRules(
                        is_array($media['gallery_rules'] ?? null)
                            ? $media['gallery_rules']
                            : [],
                    );
                    $config['media'] = $media;

                    $pricing = is_array($config['pricing'] ?? null) ? $config['pricing'] : [];
                    $this->addDebossPricing($pricing);
                    $config['pricing'] = $pricing;

                    $updates['product_config'] = $this->encode($config);
                }

                $legacy = $this->decode($product->product_options ?? null);

                if ($legacy !== []) {
                    $legacyChanged = $this->addDebossOption($legacy);
                    $legacyChanged = $this->addDebossPricing($legacy) || $legacyChanged;

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
        // The supplied finish artwork and the new option are intentionally retained.
    }

    /**
     * Add Deboss to a legacy option payload without replacing unrelated
     * product-detail content.
     *
     * @param  array<string, mixed>  $options
     */
    private function addDebossOption(array &$options): bool
    {
        $changed = false;

        if (is_array($options['option_groups'] ?? null)) {
            foreach ($options['option_groups'] as &$group) {
                if (! is_array($group) || ($group['key'] ?? null) !== 'special_finish') {
                    continue;
                }

                $changed = $this->appendDebossValue($group) || $changed;
            }
            unset($group);
        }

        if (array_key_exists('special_finish', $options)) {
            $changed = $this->appendDebossValue($options['special_finish']) || $changed;
        }

        return $changed;
    }

    private function appendDebossValue(mixed &$group): bool
    {
        if (! is_array($group)) {
            return false;
        }

        $isCanonicalGroup = array_key_exists('values', $group);
        $values = $isCanonicalGroup ? $group['values'] ?? null : $group;

        if (! is_array($values)) {
            return false;
        }

        foreach ($values as $value) {
            if (is_array($value) && ($value['code'] ?? null) === 'deboss') {
                return false;
            }
        }

        $values[] = [
            'code' => 'deboss',
            'label' => 'Deboss',
            'description' => 'A recessed detail pressed into the card.',
            'swatch_image' => '/images/products/cotton/special-finishes/deboss-diagram.png',
        ];

        if ($isCanonicalGroup) {
            $group['values'] = array_values($values);
            $group['type'] = 'multi_select';
            $group['required'] = false;
            $group['default'] = [];
        } else {
            $group = array_values($values);
        }

        return true;
    }

    /**
     * Add Deboss pricing beside the existing Emboss / Deboss process wherever
     * a cotton pricing payload already contains named special finishes.
     *
     * @param  array<string, mixed>  $pricing
     */
    private function addDebossPricing(array &$pricing): bool
    {
        $changed = false;

        if (is_array($pricing['processes'] ?? null)) {
            $changed = $this->addDebossProcess($pricing['processes']) || $changed;
        }

        foreach (['rules', 'scenarios', 'pricing_data'] as $key) {
            if (! is_array($pricing[$key] ?? null)) {
                continue;
            }

            foreach ($pricing[$key] as &$entry) {
                if (is_array($entry)) {
                    $changed = $this->addDebossPricing($entry) || $changed;
                }
            }
            unset($entry);
        }

        if (is_array($pricing['pricing'] ?? null)) {
            $changed = $this->addDebossPricing($pricing['pricing']) || $changed;
        }

        return $changed;
    }

    /**
     * @param  array<int, mixed>  $processes
     */
    private function addDebossProcess(array &$processes): bool
    {
        $fallback = null;

        foreach ($processes as $process) {
            if (! is_array($process)) {
                continue;
            }

            $code = $this->processCode($process);

            if ($code === 'deboss') {
                return false;
            }

            if ($code === 'emboss') {
                $fallback = $process;
                break;
            }

            if ($fallback === null && in_array($code, self::SPECIAL_FINISH_CODES, true)) {
                $fallback = $process;
            }
        }

        if ($fallback === null) {
            return false;
        }

        $deboss = $fallback;
        $deboss['code'] = 'deboss';

        if (array_key_exists('name', $deboss)) {
            $deboss['name'] = 'Deboss';
        }

        if (array_key_exists('label', $deboss)) {
            $deboss['label'] = 'Deboss';
        }

        if (! array_key_exists('name', $deboss) && ! array_key_exists('label', $deboss)) {
            $deboss['name'] = 'Deboss';
        }

        $processes[] = $deboss;

        return true;
    }

    /**
     * @param  array<string, mixed>  $process
     */
    private function processCode(array $process): string
    {
        $value = $process['code'] ?? $process['name'] ?? $process['label'] ?? '';

        if ($value === 'Emboss / Deboss') {
            return 'emboss';
        }

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
