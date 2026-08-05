<?php

namespace App\Services;

use App\Models\DesignServiceRequest;
use App\Models\Product;

class PricingService
{
    /**
     * Calculate the line price for a product with the given options.
     *
     * Returns the dynamic subtotal when the product has pricing JSON configured;
     * otherwise falls back to the product's static price.
     *
     * @param  int|string  $productId
     * @param  array<string, string>  $options
     */
    public function calculate(int|string $productId, array $options = []): float
    {
        $product = Product::find($productId);

        if (! $product) {
            return 0.0;
        }

        $base = $this->calculateDynamicPrice($product, $options)
            ?? (float) $product->price;

        return $base + $this->designServiceFee($options);
    }

    /**
     * One-time design service fee, resolved server-side from a valid
     * service code in the options. Unknown or missing codes add nothing.
     *
     * @param  array<string, string>  $options
     */
    private function designServiceFee(array $options): float
    {
        $code = $options['design_service'] ?? null;

        if (! is_string($code)) {
            return 0.0;
        }

        return (float) (DesignServiceRequest::DESIGN_SERVICE_FEES[$code] ?? 0.0);
    }

    /**
     * Attempt dynamic pricing. Returns null when not applicable.
     *
     * @param  array<string, string>  $options
     */
    private function calculateDynamicPrice(Product $product, array $options): ?float
    {
        $categorySlug = $product->category?->slug;

        if (! $categorySlug) {
            return null;
        }

        $optionsPath = base_path("content/product-options/{$categorySlug}/{$product->slug}.json");

        if (! file_exists($optionsPath)) {
            return null;
        }

        $productOptions = $this->decodeJson($optionsPath);

        if ($productOptions === null) {
            return null;
        }

        $pricingData = $this->loadDynamicPricingData($product->slug);

        if ($pricingData === null) {
            return null;
        }

        $sizeIndex = $this->findIndex($productOptions['sizes'] ?? [], $options['sizes'] ?? '');
        $finishIndex = $this->findIndex($productOptions['paper_finish'] ?? [], $options['paper_finish'] ?? '');
        $cornersIndex = $this->findIndex($productOptions['corners'] ?? [], $options['corners'] ?? '');
        $specialIndex = $this->findIndex($productOptions['special_finish'] ?? [], $options['special_finish'] ?? '');
        $quantity = (int) ($options['quantity'] ?? 0);

        if ($sizeIndex === null || $finishIndex === null || $cornersIndex === null || $quantity <= 0) {
            return null;
        }

        $scenarioKey = $this->resolveScenario($pricingData, $sizeIndex, $finishIndex);
        $scenario = $pricingData[$scenarioKey] ?? null;

        if (! $scenario) {
            return null;
        }

        $tiers = $this->computeTiers($scenario, $cornersIndex, $specialIndex ?? 0);

        foreach ($tiers as $tier) {
            if ($tier['qty'] === $quantity) {
                return (float) $tier['currentPrice'];
            }
        }

        return null;
    }

    /**
     * Find the index of an option whose code matches the selected value.
     */
    private function findIndex(array $list, string $value): ?int
    {
        foreach ($list as $i => $item) {
            $code = $item['code'] ?? strtolower($item['name'] ?? '');

            if ($code === $value) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Resolve which pricing scenario applies.
     *
     * Mirrors resources/js/lib/pricing.ts::resolvePricingScenario.
     */
    private function resolveScenario(array $data, int $sizeIndex, int $finishIndex): string
    {
        $hasUv = isset($data['uv']);
        $isUv = $hasUv && $finishIndex === 2;

        if ($sizeIndex === 0) {
            return $isUv ? 'uv' : 'rectangle';
        }

        return $isUv ? 'square_uv' : 'square';
    }

    /**
     * Compute quantity tiers for a scenario.
     *
     * Mirrors resources/js/lib/pricing.ts::computeDynamicTiers.
     */
    private function computeTiers(array $scenario, int $cornersIndex, int $specialFinishIndex): array
    {
        $quantities = array_values(array_unique(array_merge(
            [$scenario['startQuantity']],
            array_filter(
                array_map('intval', array_keys($scenario['paperRates'] ?? [])),
                fn ($q) => $q >= $scenario['startQuantity']
            ),
        )));
        sort($quantities);

        $roundedProcess = $this->findProcess($scenario['processes'] ?? [], '圆角');
        $foilProcess = $this->findProcess($scenario['processes'] ?? [], ['烫金', 'nfc']);

        $rounded = $cornersIndex === 1 && $roundedProcess !== null;
        $foiled = $specialFinishIndex > 0 && $foilProcess !== null;

        return array_map(function ($qty) use ($scenario, $rounded, $foiled, $roundedProcess, $foilProcess) {
            $isStart = $qty === $scenario['startQuantity'];
            $unit = (float) $scenario['basePrice'];

            if (! $isStart) {
                $paperRate = (float) ($scenario['paperRates'][$qty] ?? 0);
                $unit -= $unit * ($paperRate / 100);
            }

            if ($rounded) {
                $unit += (float) $roundedProcess['markup'];

                if (! $isStart) {
                    $rate = (float) ($roundedProcess['rates'][$qty] ?? 0);
                    $unit -= (float) $roundedProcess['markup'] * ($rate / 100);
                }
            }

            if ($foiled) {
                $unit += (float) $foilProcess['markup'];

                if (! $isStart) {
                    $rate = (float) ($foilProcess['rates'][$qty] ?? 0);
                    $unit -= (float) $foilProcess['markup'] * ($rate / 100);
                }
            }

            return [
                'qty' => $qty,
                'pricePerCard' => $unit,
                'currentPrice' => round($qty * $unit),
                'originalPrice' => null,
                'recommended' => $isStart,
            ];
        }, $quantities);
    }

    /**
     * Find a process by name, or by one of several names.
     */
    private function findProcess(array $processes, string|array $name): ?array
    {
        $names = is_array($name) ? $name : [$name];

        foreach ($processes as $process) {
            if (in_array($process['name'] ?? '', $names, true)) {
                return $process;
            }
        }

        return null;
    }

    /**
     * Load dynamic pricing data for a product slug.
     *
     * Mirrors App\Http\Controllers\Shop\ProductController::loadDynamicPricingData.
     */
    private function loadDynamicPricingData(string $slug): ?array
    {
        $configs = [
            'classic-standard-business-cards' => [
                'dir' => '300g铜版纸',
                'files' => [
                    'rectangle' => '300g铜版纸 长方形.json',
                    'uv' => '300g铜版纸 uv.json',
                    'square' => '300g铜版纸 正方形.json',
                    'square_uv' => '300g铜版纸 正方形uv.json',
                ],
            ],
            'classic-special-business-cards' => [
                'dir' => '300g艺术纸',
                'files' => [
                    'rectangle' => '300g艺术纸-荷兰白卡.json',
                    'square' => '300g艺术纸-荷兰白卡-正方形.json',
                ],
            ],
            'classic-quality-business-cards' => [
                'dir' => '320g铜版纸',
                'files' => [
                    'rectangle' => '320g铜版纸.json',
                    'square' => '320g铜版纸-正方形.json',
                ],
            ],
            'classic-solid-business-cards' => [
                'dir' => '350g白卡',
                'files' => [
                    'rectangle' => '350g白卡.json',
                    'square' => '350g白卡-正方形.json',
                ],
            ],
            'basic-cotton-business-card' => [
                'dir' => '棉纸',
                'files' => [
                    'rectangle' => '棉纸-基础型.json',
                ],
            ],
            'classic-cotton-business-card' => [
                'dir' => '棉纸',
                'files' => [
                    'rectangle' => '棉纸-经典型.json',
                ],
            ],
            'premium-cotton-business-card' => [
                'dir' => '棉纸',
                'files' => [
                    'rectangle' => '棉纸-高级型.json',
                ],
            ],
            'luxe-cotton-business-card' => [
                'dir' => '棉纸',
                'files' => [
                    'rectangle' => '棉纸-豪华型.json',
                ],
            ],
            'grand-cotton-business-card' => [
                'dir' => '棉纸',
                'files' => [
                    'rectangle' => '棉纸-奢华型.json',
                ],
            ],
            'standard-pvc-card' => [
                'dir' => 'pvc',
                'files' => [
                    'rectangle' => 'pvc0.38.json',
                ],
            ],
            'premium-pvc-card' => [
                'dir' => 'pvc',
                'files' => [
                    'rectangle' => 'pvc0.76.json',
                ],
            ],
            'super-business-cards' => [
                'dir' => '350g精品纸',
                'files' => [
                    'rectangle' => '350g精品纸.json',
                    'square' => '350g精品纸-正方形.json',
                ],
            ],
            'luxe-business-cards' => [
                'dir' => '700g精品纸',
                'files' => [
                    'rectangle' => '700g精品纸.json',
                    'square' => '700g精品纸-正方形.json',
                ],
            ],
        ];

        $config = $configs[$slug] ?? null;

        if ($config === null) {
            return null;
        }

        $basePath = base_path('storage/from-tool/数据文档/'.$config['dir']);
        $data = [];

        foreach ($config['files'] as $key => $file) {
            $path = $basePath.'/'.$file;

            if (! file_exists($path)) {
                return null;
            }

            $decoded = $this->decodeJson($path);

            if ($decoded === null) {
                return null;
            }

            $data[$key] = $decoded;
        }

        return $data;
    }

    /**
     * Decode a JSON file, returning null on failure.
     */
    private function decodeJson(string $path): ?array
    {
        $content = file_get_contents($path);

        if ($content === false) {
            return null;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }
}
