<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const COTTON_PRODUCT_SLUGS = [
        'basic-cotton-business-card',
        'classic-cotton-business-card',
        'premium-cotton-business-card',
        'luxe-cotton-business-card',
        'grand-cotton-business-card',
    ];

    public function up(): void
    {
        $products = DB::table('products')
            ->whereIn('slug', self::COTTON_PRODUCT_SLUGS)
            ->get();

        foreach ($products as $product) {
            $updates = [];
            $configChanged = false;
            $pricingChanged = false;

            $config = $this->decode($product->product_config ?? null);
            $options = is_array($config['options'] ?? null) ? $config['options'] : [];

            if (array_key_exists('with_nfc', $options)) {
                unset($options['with_nfc']);
                $config['options'] = $options;
                $configChanged = true;
            }

            $pricing = is_array($config['pricing'] ?? null) ? $config['pricing'] : [];
            $scenarios = is_array($pricing['scenarios'] ?? null) ? $pricing['scenarios'] : [];

            foreach ($scenarios as $scenarioKey => $scenario) {
                if (! is_array($scenario) || ! is_array($scenario['processes'] ?? null)) {
                    continue;
                }

                $processes = array_values(array_filter(
                    $scenario['processes'],
                    static function (mixed $process): bool {
                        if (! is_array($process)) {
                            return true;
                        }

                        $code = strtolower(trim((string) ($process['code'] ?? $process['name'] ?? '')));

                        return $code !== 'nfc';
                    },
                ));

                if (count($processes) !== count($scenario['processes'])) {
                    $scenarios[$scenarioKey]['processes'] = $processes;
                    $configChanged = true;
                    $pricingChanged = true;
                }
            }

            if ($configChanged) {
                if ($pricingChanged) {
                    $pricing['scenarios'] = $scenarios;
                    $config['pricing'] = $pricing;
                }

                $updates['product_config'] = $this->encode($config);
            }

            $legacy = $this->decode($product->product_options ?? null);

            if (array_key_exists('with_nfc', $legacy)) {
                unset($legacy['with_nfc']);
                $updates['product_options'] = $this->encode($legacy);
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
        // Removed product options are intentionally not restored on rollback.
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
