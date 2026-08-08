<?php

namespace App\Services;

use App\Models\DesignServiceRequest;
use App\Models\Product;
use Illuminate\Support\Str;

class PricingService
{
    public function __construct(
        private ProductConfigurationService $configuration,
    ) {}

    /**
     * Calculate the line price for a product with the given options.
     *
     * Returns the dynamic subtotal when the product has pricing JSON configured;
     * otherwise falls back to the product's static price.
     *
     * @param  array<string, mixed>  $options
     */
    public function calculate(int|string $productId, array $options = []): float
    {
        $product = Product::find($productId);

        if (! $product) {
            return 0.0;
        }

        $base = $this->calculateDynamicPrice($product, $options)
            ?? (float) ($product->getAttribute('price') ?? 0);

        return $base + $this->designServiceFee($options);
    }

    /**
     * One-time design service fee, resolved server-side from a valid
     * service code in the options. Unknown or missing codes add nothing.
     *
     * @param  array<string, mixed>  $options
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
     * @param  array<string, mixed>  $options
     */
    private function calculateDynamicPrice(Product $product, array $options): ?float
    {
        $categorySlug = $product->category?->slug;

        if (! $categorySlug) {
            return null;
        }

        $productOptions = $this->configuration->storefrontOptions($product);

        if ($productOptions === null) {
            return null;
        }

        $pricingData = is_array($productOptions['pricing_data'] ?? null)
            ? $productOptions['pricing_data']
            : null;

        $pricingRules = is_array($productOptions['pricing_rules'] ?? null)
            ? $productOptions['pricing_rules']
            : [];

        if ($pricingRules !== []) {
            $rule = $this->findMatchingPricingRule($pricingRules, $options);

            return $rule === null
                ? null
                : $this->calculateRulePrice($rule, $options);
        }

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
     * @param  array<int, array<string, mixed>>  $rules
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>|null
     */
    private function findMatchingPricingRule(array $rules, array $options): ?array
    {
        usort($rules, static function (array $left, array $right): int {
            return count(is_array($right['match'] ?? null) ? $right['match'] : [])
                <=> count(is_array($left['match'] ?? null) ? $left['match'] : []);
        });

        foreach ($rules as $rule) {
            $match = is_array($rule['match'] ?? null) ? $rule['match'] : [];
            $matches = true;

            foreach ($match as $key => $expected) {
                if (! array_key_exists($key, $options)) {
                    $matches = false;
                    break;
                }

                $selectedValues = is_array($options[$key])
                    ? $options[$key]
                    : [$options[$key]];
                $expectedValue = $this->normalizeOptionValue($expected);
                $valueMatches = false;

                foreach ($selectedValues as $selectedValue) {
                    if ($this->normalizeOptionValue($selectedValue) === $expectedValue) {
                        $valueMatches = true;
                        break;
                    }
                }

                if (! $valueMatches) {
                    $matches = false;
                    break;
                }
            }

            if ($matches && is_array($rule['pricing'] ?? null)) {
                return $rule['pricing'];
            }
        }

        return null;
    }

    /**
     * Calculate a price from the JSON shape entered in the Product form.
     *
     * @param  array<string, mixed>  $pricing
     * @param  array<string, mixed>  $options
     */
    private function calculateRulePrice(array $pricing, array $options): ?float
    {
        $startQuantity = (int) ($pricing['startQuantity'] ?? 0);
        $quantity = (int) ($options['quantity'] ?? 0);
        $basePrice = (float) ($pricing['basePrice'] ?? 0);
        $paperRates = is_array($pricing['paperRates'] ?? null) ? $pricing['paperRates'] : [];

        if ($startQuantity <= 0 || $quantity <= 0 || $basePrice < 0) {
            return null;
        }

        $quantities = array_values(array_unique(array_merge(
            [$startQuantity],
            array_filter(array_map('intval', array_keys($paperRates)), static fn (int $value): bool => $value >= $startQuantity),
        )));
        sort($quantities);

        if (! in_array($quantity, $quantities, true)) {
            return null;
        }

        $processes = is_array($pricing['processes'] ?? null) ? $pricing['processes'] : [];
        $unit = $basePrice;

        if ($quantity !== $startQuantity) {
            $unit -= $basePrice * ((float) ($paperRates[(string) $quantity] ?? $paperRates[$quantity] ?? 0) / 100);
        }

        foreach ($processes as $process) {
            if (! is_array($process) || ! $this->processIsSelected($process, $options)) {
                continue;
            }

            $markup = (float) ($process['markup'] ?? $process['markup_per_card'] ?? 0);
            $unit += $markup;

            if ($quantity !== $startQuantity) {
                $rates = is_array($process['rates'] ?? null)
                    ? $process['rates']
                    : (is_array($process['quantity_discounts_percent'] ?? null) ? $process['quantity_discounts_percent'] : []);
                $unit -= $markup * ((float) ($rates[(string) $quantity] ?? $rates[$quantity] ?? 0) / 100);
            }
        }

        return (float) round($quantity * $unit);
    }

    /**
     * @param  array<string, mixed>  $process
     * @param  array<string, mixed>  $options
     */
    private function processIsSelected(array $process, array $options): bool
    {
        $rawName = strtolower(trim((string) ($process['name'] ?? '')));
        $code = $this->normalizeOptionValue($process['code'] ?? $process['name'] ?? '');

        if (
            $code === 'rounded_corners'
            || str_contains($rawName, 'rounded')
            || str_contains($rawName, '圆角')
        ) {
            $cornerValues = is_array($options['corners'] ?? null)
                ? $options['corners']
                : [$options['corners'] ?? ''];

            foreach ($cornerValues as $cornerValue) {
                if (in_array($this->normalizeOptionValue($cornerValue), ['rounded', 'rounded_corners', 'round'], true)) {
                    return true;
                }
            }

            return false;
        }

        if (
            in_array($code, ['foil', 'nfc'], true)
            || str_contains($rawName, 'foil')
            || str_contains($rawName, '烫金')
            || $rawName === 'nfc'
        ) {
            foreach ($options as $key => $value) {
                $values = is_array($value) ? $value : [$value];

                foreach ($values as $item) {
                    $normalized = $this->normalizeOptionValue($item);

                    if ($normalized === $code || ($key === 'special_finish' && ! in_array($normalized, ['', 'none', 'no_foil', 'no_special_finish'], true))) {
                        return true;
                    }
                }
            }

            return false;
        }

        if (! array_key_exists($code, $options)) {
            return false;
        }

        $values = is_array($options[$code]) ? $options[$code] : [$options[$code]];

        foreach ($values as $value) {
            if (! in_array($this->normalizeOptionValue($value), ['', 'none', 'no'], true)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeOptionValue(mixed $value): string
    {
        return Str::slug(strtolower(trim((string) $value)), '_');
    }

    /**
     * Find the index of an option whose code matches the selected value.
     *
     * @param  array<int, array<string, mixed>>  $list
     */
    private function findIndex(array $list, mixed $value): ?int
    {
        $selectedValues = is_array($value) ? $value : [$value];

        foreach ($list as $i => $item) {
            $code = $item['code'] ?? strtolower($item['name'] ?? '');

            foreach ($selectedValues as $selectedValue) {
                if ($code === $selectedValue) {
                    return (int) $i;
                }
            }
        }

        return null;
    }

    /**
     * Resolve which pricing scenario applies.
     *
     * Mirrors resources/js/lib/pricing.ts::resolvePricingScenario.
     *
     * @param  array<string, array<string, mixed>>  $data
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
     *
     * @param  array<string, mixed>  $scenario
     * @return array<int, array<string, mixed>>
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
     *
     * @param  array<int, array<string, mixed>>  $processes
     * @param  string|array<int, string>  $name
     * @return array<string, mixed>|null
     */
    private function findProcess(array $processes, string|array $name): ?array
    {
        $names = is_array($name) ? $name : [$name];

        foreach ($processes as $process) {
            if (
                in_array($process['name'] ?? '', $names, true) ||
                in_array($process['code'] ?? '', $names, true)
            ) {
                return $process;
            }
        }

        return null;
    }
}
