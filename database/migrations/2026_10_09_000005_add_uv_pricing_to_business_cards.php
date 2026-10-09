<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PRODUCT_SLUGS = [
        'standard-quality-business-cards',
        'solid-quality-business-cards',
    ];

    /**
     * 3D UV uses the same single-side markup and quantity discount schedule
     * as the combined UV / cold foil / hot foil process in the source sheet.
     * The pricing engine doubles this process when both sides are selected.
     *
     * @var array<string, mixed>
     */
    private const UV_PROCESS = [
        'code' => 'uv_finish',
        'label' => '3D UV',
        'markup_per_card' => 0.4,
        'quantity_discounts_percent' => [
            '100' => 50,
            '200' => 75,
            '500' => 77.5,
            '1000' => 80,
            '2000' => 80,
            '3000' => 82.5,
            '4000' => 82.5,
            '5000' => 85,
            '10000' => 85,
        ],
    ];

    public function up(): void
    {
        DB::table('products')
            ->whereIn('slug', self::PRODUCT_SLUGS)
            ->select(['id', 'product_config'])
            ->orderBy('id')
            ->get()
            ->each(function (object $product): void {
                $config = $this->decode($product->product_config ?? null);
                $scenarios = data_get($config, 'pricing.scenarios');

                if (! is_array($scenarios)) {
                    return;
                }

                $changed = false;

                foreach (['rectangle', 'square'] as $scenarioKey) {
                    if (! is_array($scenarios[$scenarioKey] ?? null)) {
                        continue;
                    }

                    $scenario = $scenarios[$scenarioKey];
                    $processes = is_array($scenario['processes'] ?? null)
                        ? array_values($scenario['processes'])
                        : [];
                    $uvProcessIndex = null;

                    foreach ($processes as $index => $process) {
                        if (! is_array($process)) {
                            continue;
                        }

                        $code = strtolower(str_replace('-', '_', trim((string) ($process['code'] ?? ''))));

                        if (in_array($code, ['uv_finish', '3d_uv'], true)) {
                            $uvProcessIndex = $index;
                            break;
                        }
                    }

                    if ($uvProcessIndex === null) {
                        $processes[] = self::UV_PROCESS;
                        $changed = true;
                    } else {
                        $updatedProcess = array_merge($processes[$uvProcessIndex], self::UV_PROCESS);

                        if ($updatedProcess !== $processes[$uvProcessIndex]) {
                            $processes[$uvProcessIndex] = $updatedProcess;
                            $changed = true;
                        }
                    }

                    $scenario['processes'] = array_values($processes);
                    $scenarios[$scenarioKey] = $scenario;
                }

                if (! $changed) {
                    return;
                }

                data_set($config, 'pricing.scenarios', $scenarios);

                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['product_config' => $this->encode($config)]);
            });
    }

    public function down(): void
    {
        DB::table('products')
            ->whereIn('slug', self::PRODUCT_SLUGS)
            ->select(['id', 'product_config'])
            ->orderBy('id')
            ->get()
            ->each(function (object $product): void {
                $config = $this->decode($product->product_config ?? null);
                $scenarios = data_get($config, 'pricing.scenarios');

                if (! is_array($scenarios)) {
                    return;
                }

                $changed = false;

                foreach (['rectangle', 'square'] as $scenarioKey) {
                    $scenario = $scenarios[$scenarioKey] ?? null;

                    if (! is_array($scenario) || ! is_array($scenario['processes'] ?? null)) {
                        continue;
                    }

                    $processes = array_values(array_filter(
                        $scenario['processes'],
                        static function (mixed $process): bool {
                            if (! is_array($process)) {
                                return true;
                            }

                            $code = strtolower(str_replace('-', '_', trim((string) ($process['code'] ?? ''))));

                            return ! in_array($code, ['uv_finish', '3d_uv'], true);
                        },
                    ));

                    if (count($processes) !== count($scenario['processes'])) {
                        $scenario['processes'] = $processes;
                        $scenarios[$scenarioKey] = $scenario;
                        $changed = true;
                    }
                }

                if (! $changed) {
                    return;
                }

                data_set($config, 'pricing.scenarios', $scenarios);

                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['product_config' => $this->encode($config)]);
            });
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
