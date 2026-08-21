<?php

namespace App\Services;

use App\Models\DesignServiceRequest;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
     * Validate and normalize option values submitted by the storefront.
     *
     * Custom dimensions are deliberately validated on the server as well as
     * in the browser because cart requests are user-controlled.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function validateOptions(Product $product, array $options): array
    {
        $size = $options['sizes'] ?? null;
        $size = is_array($size) ? ($size[0] ?? null) : $size;
        $normalizedSize = $this->normalizeOptionValue($size);
        $customSizeValues = data_get(
            $this->configuration->canonicalConfig($product),
            'options.sizes.values',
            [],
        );
        $hasCustomSize = is_array($customSizeValues) && collect($customSizeValues)
            ->contains(fn (mixed $value): bool => is_array($value)
                && $this->normalizeOptionValue($value['code'] ?? $value['label'] ?? '') === 'custom');

        if ($normalizedSize !== 'custom') {
            unset($options['custom_width'], $options['custom_height']);

            return $options;
        }

        if (! $hasCustomSize) {
            throw ValidationException::withMessages([
                'options.sizes' => 'This product does not support custom sizes.',
            ]);
        }

        $errors = [];
        $dimensions = [
            'custom_width' => '',
            'custom_height' => '',
        ];

        foreach (['width', 'height'] as $dimension) {
            $key = "custom_{$dimension}";
            $value = $options[$key] ?? null;

            if (! is_numeric($value) || ! is_finite((float) $value)) {
                $errors["options.{$key}"] = "Enter a {$dimension} between 2.1 and 3.5 inches.";

                continue;
            }

            $numericValue = (float) $value;

            if ($numericValue < 2.1 || $numericValue > 3.5) {
                $errors["options.{$key}"] = "The {$dimension} must be between 2.1 and 3.5 inches.";

                continue;
            }

            $dimensions[$key] = number_format($numericValue, 2, '.', '');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $options['sizes'] = 'custom';
        $options['custom_width'] = $dimensions['custom_width'];
        $options['custom_height'] = $dimensions['custom_height'];

        return $options;
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

        // Custom sizes use the same base pricing as the standard rectangular
        // card. The dimensions are still preserved in the cart options and
        // validated above; only the pricing rule key is normalized here.
        $pricingOptions = $this->optionsForPricing($options);

        if ($pricingRules !== []) {
            $rule = $this->findMatchingPricingRule($pricingRules, $pricingOptions);

            return $rule === null
                ? null
                : $this->calculateRulePrice($rule, $pricingOptions);
        }

        if ($pricingData === null) {
            return null;
        }

        $sizeIndex = $this->optionIndexOrDefault($productOptions, 'sizes', $pricingOptions['sizes'] ?? '');
        $finishIndex = $this->optionIndexOrDefault($productOptions, 'paper_finish', $pricingOptions['paper_finish'] ?? '');
        $cornersIndex = $this->optionIndexOrDefault($productOptions, 'corners', $pricingOptions['corners'] ?? '');
        $specialIndex = $this->optionIndexOrDefault($productOptions, 'special_finish', $pricingOptions['special_finish'] ?? '');
        $quantity = (int) ($pricingOptions['quantity'] ?? 0);

        if ($sizeIndex === null || $finishIndex === null || $cornersIndex === null || $quantity <= 0) {
            return null;
        }

        $scenarioKey = $this->resolveScenario($pricingData, $sizeIndex, $finishIndex);
        $scenario = $pricingData[$scenarioKey] ?? null;

        if (! $scenario) {
            return null;
        }

        $tiers = $this->computeTiers($scenario, $cornersIndex, $specialIndex ?? 0, $pricingOptions);

        foreach ($tiers as $tier) {
            if ($tier['qty'] === $quantity) {
                return (float) $tier['currentPrice'];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function optionsForPricing(array $options): array
    {
        if ($this->normalizeOptionValue($options['sizes'] ?? '') === 'custom') {
            $options['sizes'] = 'standard';
        }

        return $options;
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
        $negativeValues = [
            '',
            'none',
            'no',
            'no_foil',
            'no_special_finish',
            'no_print_code',
            'no_print_code_or_magnetic_stripe',
            'no_nfc',
            'no_magnetic_stripe',
            'no_signature_stripe',
        ];

        if (
            $code === 'print_code_or_magnetic_stripe'
            && array_key_exists('print_code_or_magnetic_stripe', $options)
        ) {
            $values = is_array($options['print_code_or_magnetic_stripe'])
                ? $options['print_code_or_magnetic_stripe']
                : [$options['print_code_or_magnetic_stripe']];

            foreach ($values as $value) {
                if (! in_array($this->normalizeOptionValue($value), $negativeValues, true)) {
                    return true;
                }
            }

            return false;
        }

        if (
            $code === 'print_code'
            || $code === 'print_code_or_magnetic_stripe'
            || str_contains($rawName, 'print code')
            || str_contains($rawName, '打码')
        ) {
            foreach (['print_code', 'print_code_or_signature_stripe', 'print_code_or_magnetic_stripe'] as $key) {
                $values = is_array($options[$key] ?? null) ? $options[$key] : [$options[$key] ?? ''];

                foreach ($values as $value) {
                    if ($this->normalizeOptionValue($value) === 'print_code') {
                        return true;
                    }
                }
            }

            return false;
        }

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
            in_array($code, ['foil', 'nfc', 'special_finish'], true)
            || str_contains($rawName, 'foil')
            || str_contains($rawName, '烫金')
            || str_contains($rawName, '激光雕刻')
            || str_contains($rawName, '彩印')
            || str_contains($rawName, '镀色')
            || $rawName === 'nfc'
        ) {
            foreach ($options as $key => $value) {
                $values = is_array($value) ? $value : [$value];

                foreach ($values as $item) {
                    $normalized = $this->normalizeOptionValue($item);

                    if (
                        $normalized === $code
                        || ($code === 'nfc' && $key === 'with_nfc' && $normalized === 'with_nfc')
                        || ($code === 'print_code_or_magnetic_stripe'
                            && $key === 'print_code_or_magnetic_stripe'
                            && ! in_array($normalized, $negativeValues, true))
                        || ($key === 'special_finish' && ! in_array($normalized, $negativeValues, true))
                    ) {
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
            if (! in_array($this->normalizeOptionValue($value), $negativeValues, true)) {
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
     * Products with a single pricing scenario do not need every standard
     * business-card option group. Treat an omitted group as the first/default
     * scenario index instead of falling back to the static product price.
     *
     * @param  array<string, mixed>  $productOptions
     */
    private function optionIndexOrDefault(array $productOptions, string $key, mixed $value): ?int
    {
        $list = $productOptions[$key] ?? [];

        if (! is_array($list) || $list === []) {
            return 0;
        }

        return $this->findIndex($list, $value);
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
    private function computeTiers(array $scenario, int $cornersIndex, int $specialFinishIndex, array $options = []): array
    {
        $quantities = array_values(array_unique(array_merge(
            [$scenario['startQuantity']],
            array_filter(
                array_map('intval', array_keys($scenario['paperRates'] ?? [])),
                fn ($q) => $q >= $scenario['startQuantity']
            ),
        )));
        sort($quantities);

        $roundedProcess = $this->findProcess($scenario['processes'] ?? [], [
            'rounded_corners',
            'rounded',
            '圆角',
            '鍦嗚',
        ]);
        $foilProcess = $this->findProcess($scenario['processes'] ?? [], [
            'special_finish',
            'foil',
            'nfc',
            '烫金',
            '立体uv/冷烫/热烫单面',
            '鐑噾',
        ]);

        $printCodeProcess = $this->findProcess($scenario['processes'] ?? [], [
            'print_code',
            'print_code_or_magnetic_stripe',
            '打码',
            '鎵撶爜',
        ]);

        $rounded = $roundedProcess !== null && $this->processIsSelected($roundedProcess, $options);
        $foiled = $foilProcess !== null && $this->processIsSelected($foilProcess, $options);
        $printCodeSelected = $printCodeProcess !== null && $this->processIsSelected($printCodeProcess, $options);

        return array_map(function ($qty) use ($scenario, $rounded, $foiled, $printCodeSelected, $roundedProcess, $foilProcess, $printCodeProcess) {
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

            if ($printCodeSelected) {
                $unit += (float) $printCodeProcess['markup'];

                if (! $isStart) {
                    $rate = (float) ($printCodeProcess['rates'][$qty] ?? 0);
                    $unit -= (float) $printCodeProcess['markup'] * ($rate / 100);
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
