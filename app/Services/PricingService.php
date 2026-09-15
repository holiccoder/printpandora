<?php

namespace App\Services;

use App\Models\DesignServiceRequest;
use App\Models\Product;
use App\Support\BusinessCardOptionCatalog;
use App\Support\StickerProductCatalog;
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
     * Returns the dynamic subtotal when the product has database-backed
     * pricing configured; otherwise falls back to the product's static price.
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
        $config = $this->configuration->canonicalConfig($product);

        if (BusinessCardOptionCatalog::isCottonBusinessCard((string) $product->slug)) {
            $options = $this->validateCottonOptions($options, $config);
        }

        $size = $options['sizes'] ?? null;
        $size = is_array($size) ? ($size[0] ?? null) : $size;
        $normalizedSize = $this->normalizeOptionValue($size);
        $customSizeValues = data_get($config, 'options.sizes.values', []);
        $customSizeValue = is_array($customSizeValues)
            ? collect($customSizeValues)->first(fn (mixed $value): bool => is_array($value)
                && $this->normalizeOptionValue($value['code'] ?? $value['label'] ?? '') === 'custom')
            : null;
        $hasCustomSize = is_array($customSizeValues) && collect($customSizeValues)
            ->contains(fn (mixed $value): bool => is_array($value)
                && $this->normalizeOptionValue($value['code'] ?? $value['label'] ?? '') === 'custom');

        if ($normalizedSize !== 'custom') {
            unset($options['custom_width'], $options['custom_height']);

        } else {
            if (! $hasCustomSize) {
                throw ValidationException::withMessages([
                    'options.sizes' => 'This product does not support custom sizes.',
                ]);
            }

            $bounds = $this->customSizeBounds(is_array($customSizeValue) ? $customSizeValue : []);
            $errors = [];
            $dimensions = [
                'custom_width' => '',
                'custom_height' => '',
            ];

            foreach (['width', 'height'] as $dimension) {
                $key = "custom_{$dimension}";
                $value = $options[$key] ?? null;
                $minimum = $bounds[$dimension]['min'];
                $maximum = $bounds[$dimension]['max'];
                $range = number_format($minimum, 2, '.', '').' and '.number_format($maximum, 2, '.', '');

                if (! is_numeric($value) || ! is_finite((float) $value)) {
                    $errors["options.{$key}"] = "Enter a {$dimension} between {$range} inches.";

                    continue;
                }

                $numericValue = (float) $value;

                if ($numericValue < $minimum || $numericValue > $maximum) {
                    $errors["options.{$key}"] = "The {$dimension} must be between {$range} inches.";

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
        }

        if (StickerProductCatalog::isStickerProduct((string) $product->slug)) {
            $sizeGroup = data_get($config, 'options.sizes', []);
            $options = $this->normalizeStickerPaperArea(
                $options,
                is_array($sizeGroup) ? $sizeGroup : [],
            );
        }

        return $options;
    }

    /**
     * Sticker pricing uses the selected dimensions as a square-metre multiplier.
     *
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $sizeGroup
     * @return array<string, mixed>
     */
    private function normalizeStickerPaperArea(array $options, array $sizeGroup): array
    {
        $area = $this->stickerPaperArea($sizeGroup, $options);

        if ($area === null || $area <= 0) {
            throw ValidationException::withMessages([
                'options.sizes' => 'Select a valid sticker size.',
            ]);
        }

        $options['paper_area'] = number_format($area, 8, '.', '');

        return $options;
    }

    /**
     * Resolve a sticker's area from inches into square metres.
     *
     * @param  array<string, mixed>  $sizeGroup
     * @param  array<string, mixed>  $options
     */
    private function stickerPaperArea(array $sizeGroup, array $options): ?float
    {
        $selectedSize = $options['sizes'] ?? null;
        $selectedSize = is_array($selectedSize) ? ($selectedSize[0] ?? null) : $selectedSize;
        $sizeCode = $this->normalizeOptionValue($selectedSize);

        $readPositiveNumber = static function (mixed $value): ?float {
            if (! is_numeric($value) || ! is_finite((float) $value) || (float) $value <= 0) {
                return null;
            }

            return (float) $value;
        };

        if ($sizeCode === 'custom') {
            $width = $readPositiveNumber($options['custom_width'] ?? null);
            $height = $readPositiveNumber($options['custom_height'] ?? null);

            return $width !== null && $height !== null
                ? StickerProductCatalog::areaInSquareMetres($width, $height)
                : null;
        }

        $values = is_array($sizeGroup['values'] ?? null) ? $sizeGroup['values'] : [];

        foreach ($values as $value) {
            if (! is_array($value)) {
                continue;
            }

            $code = $this->normalizeOptionValue($value['code'] ?? '');
            $label = $this->normalizeOptionValue($value['label'] ?? $value['name'] ?? '');

            if ($sizeCode === '' || ($sizeCode !== $code && $sizeCode !== $label)) {
                continue;
            }

            $width = $readPositiveNumber($value['width'] ?? null);
            $height = $readPositiveNumber($value['height'] ?? null);

            return $width !== null && $height !== null
                ? StickerProductCatalog::areaInSquareMetres($width, $height)
                : null;
        }

        return null;
    }

    /**
     * Cotton option groups are intentionally validated from the same
     * canonical catalog that renders them in the storefront.
     *
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function validateCottonOptions(array $options, array $config): array
    {
        $groups = is_array($config['options'] ?? null) ? $config['options'] : [];
        $normalized = $options;
        $errors = [];

        foreach (['sizes', 'corners', 'texture'] as $key) {
            $allowed = $this->allowedOptionCodes($groups[$key] ?? []);
            $submitted = $this->submittedOptionValues($options[$key] ?? null);
            $resolved = $this->resolveAllowedOptionCodes($submitted, $allowed);

            if (count($resolved) !== 1 || count($submitted) !== 1) {
                $errors["options.{$key}"] = match ($key) {
                    'sizes' => 'Select one size.',
                    'corners' => 'Select one corner style.',
                    default => 'Select one texture.',
                };

                continue;
            }

            $normalized[$key] = $resolved[0];
        }

        $specialFinishAllowed = $this->allowedOptionCodes($groups['special_finish'] ?? []);
        $specialFinishSubmitted = $this->submittedOptionValues($options['special_finish'] ?? null);
        $specialFinishResolved = $this->resolveAllowedOptionCodes(
            $specialFinishSubmitted,
            $specialFinishAllowed,
        );

        if ($specialFinishSubmitted === []) {
            $errors['options.special_finish'] = 'Select at least one special finish.';
        } elseif (
            count($specialFinishResolved) !== count(array_unique(array_map(
                fn (string $value): string => $this->normalizeOptionValue($value),
                $specialFinishSubmitted,
            )))
        ) {
            $errors['options.special_finish'] = 'Select only valid special finishes.';
        } else {
            $normalized['special_finish'] = $specialFinishResolved;
            $normalized['special_finish_on_sides'] = $this->normalizeCottonSpecialFinishSides(
                $specialFinishResolved,
                $options['special_finish_on_sides'] ?? null,
            );
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $normalized;
    }

    /**
     * Keep side selection independent for each selected cotton finish.
     * Missing or invalid side values intentionally fall back to single side.
     *
     * @param  array<int, string>  $selectedCodes
     * @return array<string, string>
     */
    private function normalizeCottonSpecialFinishSides(
        array $selectedCodes,
        mixed $submitted,
    ): array {
        $submittedSides = [];

        if (is_array($submitted)) {
            foreach ($submitted as $code => $side) {
                if (is_scalar($code) && is_scalar($side)) {
                    $submittedSides[$this->normalizeOptionValue((string) $code)] = (string) $side;
                }
            }
        } elseif (is_scalar($submitted)) {
            $submittedSides['*'] = (string) $submitted;
        }

        $normalized = [];

        foreach ($selectedCodes as $code) {
            $submittedSide = $submittedSides[$this->normalizeOptionValue($code)]
                ?? $submittedSides['*']
                ?? 'one_side';

            $normalized[$code] = in_array($submittedSide, ['one_side', 'both_sides'], true)
                ? $submittedSide
                : 'one_side';
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $group
     * @return array<string, string>
     */
    private function allowedOptionCodes(array $group): array
    {
        $allowed = [];
        $values = is_array($group['values'] ?? null) ? $group['values'] : [];

        foreach ($values as $value) {
            if (! is_array($value) || ! filled($value['code'] ?? null)) {
                continue;
            }

            $code = (string) $value['code'];
            $allowed[$this->normalizeOptionValue($code)] = $code;
        }

        return $allowed;
    }

    /**
     * @return array<int, string>
     */
    private function submittedOptionValues(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];

        return array_values(array_map(
            static fn (mixed $item): string => (string) $item,
            array_filter($values, static fn (mixed $item): bool => is_scalar($item)),
        ));
    }

    /**
     * @param  array<int, string>  $submitted
     * @param  array<string, string>  $allowed
     * @return array<int, string>
     */
    private function resolveAllowedOptionCodes(array $submitted, array $allowed): array
    {
        $resolved = [];

        foreach ($submitted as $value) {
            $normalized = $this->normalizeOptionValue($value);

            if (! array_key_exists($normalized, $allowed)) {
                continue;
            }

            if (! in_array($allowed[$normalized], $resolved, true)) {
                $resolved[] = $allowed[$normalized];
            }
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $customValue
     * @return array<string, array{min: float, max: float}>
     */
    private function customSizeBounds(array $customValue): array
    {
        $read = static function (array $value, string $key, float $fallback): float {
            return is_numeric($value[$key] ?? null)
                ? (float) $value[$key]
                : $fallback;
        };

        return [
            'width' => [
                'min' => $read($customValue, 'min_width', 2.1),
                'max' => $read($customValue, 'max_width', 3.5),
            ],
            'height' => [
                'min' => $read($customValue, 'min_height', 2.1),
                'max' => $read($customValue, 'max_height', 3.5),
            ],
        ];
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
        $pricingOptions = $options;

        if (StickerProductCatalog::isStickerProduct((string) $product->slug)) {
            $optionGroups = is_array($productOptions['option_groups'] ?? null)
                ? $productOptions['option_groups']
                : [];
            $sizeGroup = collect($optionGroups)->first(
                fn (mixed $group): bool => is_array($group) && ($group['key'] ?? null) === 'sizes',
            );
            $area = $this->stickerPaperArea(is_array($sizeGroup) ? $sizeGroup : [], $options);

            if ($area === null || $area <= 0) {
                return null;
            }

            $pricingOptions['paper_area'] = $area;
        }

        $pricingOptions = $this->optionsForPricing($pricingOptions, $product);

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
    private function optionsForPricing(array $options, ?Product $product = null): array
    {
        $size = $this->normalizeOptionValue($options['sizes'] ?? '');

        if (
            $size === 'custom'
            || ($product !== null
                && BusinessCardOptionCatalog::isCottonBusinessCard((string) $product->slug)
                && $size === 'compact')
        ) {
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
        $unitMultipliers = is_array($pricing['unitMultipliers'] ?? null)
            ? $pricing['unitMultipliers']
            : [];

        if ($startQuantity <= 0 || $quantity <= 0 || $basePrice < 0) {
            return null;
        }

        $quantities = array_values(array_unique(array_merge(
            [$startQuantity],
            array_filter(array_map('intval', array_keys($paperRates)), static fn (int $value): bool => $value >= $startQuantity),
            array_filter(array_map('intval', array_keys($unitMultipliers)), static fn (int $value): bool => $value >= $startQuantity),
        )));
        sort($quantities);

        if (! in_array($quantity, $quantities, true)) {
            return null;
        }

        $processes = is_array($pricing['processes'] ?? null) ? $pricing['processes'] : [];
        $unit = $basePrice;
        $unitMultiplier = $unitMultipliers[(string) $quantity] ?? $unitMultipliers[$quantity] ?? null;

        if (is_numeric($unitMultiplier)) {
            $unit = $basePrice * (float) $unitMultiplier;
        } else {
            $paperRate = (float) ($paperRates[(string) $quantity] ?? $paperRates[$quantity] ?? 0);
            $unit -= $basePrice * ($paperRate / 100);
        }

        foreach ($processes as $process) {
            if (! is_array($process) || ! $this->processIsSelected($process, $options)) {
                continue;
            }

            $markup = (float) ($process['markup'] ?? $process['markup_per_card'] ?? 0);

            if ($this->isFoilProcess($process, $options)) {
                $markup *= $this->foilSideMultiplier($options, $process);
            }

            $unit += $markup;

            $rates = is_array($process['rates'] ?? null)
                ? $process['rates']
                : (is_array($process['quantity_discounts_percent'] ?? null) ? $process['quantity_discounts_percent'] : []);
            $rate = (float) ($rates[(string) $quantity] ?? $rates[$quantity] ?? 0);
            $unit -= $markup * ($rate / 100);
        }

        $paperArea = 1.0;

        if (($pricing['area_based'] ?? false) === true) {
            $rawArea = $options['paper_area'] ?? null;

            if (! is_numeric($rawArea) || ! is_finite((float) $rawArea) || (float) $rawArea <= 0) {
                return null;
            }

            $paperArea = (float) $rawArea;
        }

        return (float) round($quantity * $unit * $paperArea);
    }

    /**
     * @param  array<string, mixed>  $process
     * @param  array<string, mixed>  $options
     */
    private function processIsSelected(array $process, array $options): bool
    {
        $rawName = strtolower(trim((string) ($process['name'] ?? '')));
        $code = $this->pricingProcessCode($process);
        $negativeValues = [
            '',
            'none',
            'no',
            'no_foil',
            'no_special_finish',
            'no_print_code',
            'no_print_code_or_magnetic_stripe',
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
            in_array($code, ['laser', 'edge_coloring', 'double_mounting', 'custom_die_cut'], true)
        ) {
            $values = is_array($options['special_finish'] ?? null)
                ? $options['special_finish']
                : [$options['special_finish'] ?? ''];

            foreach ($values as $value) {
                if ($this->normalizeOptionValue($value) === $code) {
                    return true;
                }
            }

            return false;
        }

        if (
            in_array($code, ['foil', 'special_finish'], true)
            || str_contains($rawName, 'foil')
            || str_contains($rawName, '烫金')
            || str_contains($rawName, '激光雕刻')
            || str_contains($rawName, '彩印')
            || str_contains($rawName, '镀色')
        ) {
            $specialFinishValues = is_array($options['special_finish'] ?? null)
                ? $options['special_finish']
                : [$options['special_finish'] ?? ''];

            foreach ($specialFinishValues as $item) {
                $normalized = $this->normalizeOptionValue($item);

                if (
                    ! in_array($normalized, $negativeValues, true)
                    && $this->foilProcessMatchesSelection($code, $rawName, $normalized)
                ) {
                    return true;
                }
            }

            foreach ($options as $key => $value) {
                $values = is_array($value) ? $value : [$value];

                foreach ($values as $item) {
                    $normalized = $this->normalizeOptionValue($item);

                    if (
                        $normalized === $code
                        || ($code === 'print_code_or_magnetic_stripe'
                            && $key === 'print_code_or_magnetic_stripe'
                            && ! in_array($normalized, $negativeValues, true))
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

    /**
     * Resolve the canonical option code for a pricing process.
     *
     * Cotton-card pricing has several independent special finishes. Older
     * pricing payloads used only display names, so keep the name aliases here
     * as a compatibility path while preferring an explicit process code.
     *
     * @param  array<string, mixed>  $process
     */
    private function pricingProcessCode(array $process): string
    {
        $explicitCode = trim((string) ($process['code'] ?? ''));

        if ($explicitCode !== '') {
            return $this->normalizeOptionValue($explicitCode);
        }

        $name = trim((string) ($process['name'] ?? ''));
        $normalizedName = $this->normalizeOptionValue($name);

        return match ($name) {
            '圆角' => 'rounded_corners',
            '激光' => 'laser',
            '滚边' => 'edge_coloring',
            '对裱' => 'double_mounting',
            '异形模切' => 'custom_die_cut',
            default => match ($normalizedName) {
                'rounded', 'rounded-corners', 'round' => 'rounded_corners',
                default => $normalizedName,
            },
        };
    }

    private function foilProcessMatchesSelection(
        string $processCode,
        string $processName,
        string $selectedCode,
    ): bool {
        if (in_array($processCode, ['foil', 'special_finish'], true)) {
            return true;
        }

        if ($selectedCode === $processCode) {
            return true;
        }

        $hasGenericFoilName = str_contains($processName, 'foil')
            || str_contains($processName, '烫金');
        $mentionsColdFoil = str_contains($processCode, 'cold')
            || str_contains($processName, 'cold foil')
            || str_contains($processName, '冷烫');
        $mentionsHotFoil = str_contains($processCode, 'hot')
            || str_contains($processName, 'hot foil')
            || str_contains($processName, '热烫');

        $isColdFoil = $mentionsColdFoil && ! $mentionsHotFoil;
        $isHotFoil = $mentionsHotFoil && ! $mentionsColdFoil;

        if ($hasGenericFoilName && ! $isColdFoil && ! $isHotFoil) {
            return true;
        }

        if ($isColdFoil) {
            return $this->isFoilOptionCode($selectedCode)
                && str_starts_with($selectedCode, 'cold_');
        }

        if ($isHotFoil) {
            return $this->isFoilOptionCode($selectedCode)
                && ! str_starts_with($selectedCode, 'cold_');
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

        if ($value === null || $value === '' || (is_array($value) && $value === [])) {
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

        $preferred = $isUv ? 'square_uv' : 'square';

        return isset($data[$preferred])
            ? $preferred
            : ($isUv ? 'uv' : 'rectangle');
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
            array_filter(
                array_map('intval', array_keys($scenario['unitMultipliers'] ?? [])),
                fn ($q) => $q >= $scenario['startQuantity']
            ),
        )));
        sort($quantities);

        $pricingOptions = $options;

        if (! array_key_exists('corners', $pricingOptions)) {
            $pricingOptions['corners'] = $cornersIndex === 1 ? 'rounded' : 'square';
        }

        if (! array_key_exists('special_finish', $pricingOptions)) {
            $pricingOptions['special_finish'] = $specialFinishIndex > 0 ? 'special_finish' : 'none';
        }

        $selectedProcesses = array_values(array_filter(
            is_array($scenario['processes'] ?? null) ? $scenario['processes'] : [],
            fn (mixed $process): bool => is_array($process)
                && $this->processIsSelected($process, $pricingOptions),
        ));
        return array_map(function (int $qty) use ($scenario, $selectedProcesses, $pricingOptions) {
            $isStart = $qty === (int) $scenario['startQuantity'];
            $unit = (float) $scenario['basePrice'];
            $unitMultipliers = is_array($scenario['unitMultipliers'] ?? null)
                ? $scenario['unitMultipliers']
                : [];
            $unitMultiplier = $unitMultipliers[$qty]
                ?? $unitMultipliers[(string) $qty]
                ?? null;

            if (is_numeric($unitMultiplier)) {
                $unit = (float) $scenario['basePrice'] * (float) $unitMultiplier;
            } else {
                $paperRate = (float) ($scenario['paperRates'][$qty] ?? 0);
                $unit -= $unit * ($paperRate / 100);
            }

            foreach ($selectedProcesses as $process) {
                $markup = (float) ($process['markup'] ?? $process['markup_per_card'] ?? 0);

                if ($this->isFoilProcess($process, $pricingOptions)) {
                    $markup *= $this->foilSideMultiplier($pricingOptions, $process);
                }

                $unit += $markup;

                $rates = is_array($process['rates'] ?? null)
                    ? $process['rates']
                    : (is_array($process['quantity_discounts_percent'] ?? null)
                        ? $process['quantity_discounts_percent']
                        : []);
                $rate = (float) ($rates[$qty] ?? $rates[(string) $qty] ?? 0);
                $unit -= $markup * ($rate / 100);
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
     * Two-sided foil uses the foil markup twice. The side map is keyed by the
     * selected special-finish code so multiple finishes can keep independent
     * side selections without changing the base pricing contract.
     *
     * @param  array<string, mixed>  $options
     */
    private function foilSideMultiplier(array $options, ?array $process = null): int
    {
        $selectedFinish = $options['special_finish'] ?? null;
        $selectedValues = is_array($selectedFinish) ? $selectedFinish : [$selectedFinish];
        $selectedCodes = [];

        foreach ($selectedValues as $value) {
            if (is_scalar($value)) {
                $normalized = $this->normalizeOptionValue($value);

                if ($normalized !== '') {
                    $selectedCodes[] = $normalized;
                }
            }
        }

        if ($selectedCodes === []) {
            return 1;
        }

        if ($process !== null) {
            $processCode = $this->pricingProcessCode($process);
            $processName = strtolower(trim((string) ($process['name'] ?? $process['label'] ?? '')));
            $hasGenericFoilName = str_contains($processName, 'foil')
                || str_contains($processName, '烫金');
            $mentionsColdFoil = str_contains($processCode, 'cold')
                || str_contains($processName, 'cold foil')
                || str_contains($processName, '冷烫');
            $mentionsHotFoil = str_contains($processCode, 'hot')
                || str_contains($processName, 'hot foil')
                || str_contains($processName, '热烫');
            $isColdFoil = $mentionsColdFoil && ! $mentionsHotFoil;
            $isHotFoil = $mentionsHotFoil && ! $mentionsColdFoil;
            $isGenericFoil = (in_array($processCode, ['foil', 'special_finish'], true)
                || $hasGenericFoilName)
                && ! $isColdFoil
                && ! $isHotFoil;

            if (! $isGenericFoil) {
                $selectedCodes = array_values(array_filter(
                    $selectedCodes,
                    function (string $code) use ($processCode, $isColdFoil, $isHotFoil): bool {
                        if ($code === $processCode) {
                            return true;
                        }

                        if ($isColdFoil) {
                            return $this->isFoilOptionCode($code) && str_starts_with($code, 'cold_');
                        }

                        if ($isHotFoil) {
                            return $this->isFoilOptionCode($code) && ! str_starts_with($code, 'cold_');
                        }

                        return false;
                    },
                ));
            }
        }

        if ($selectedCodes === []) {
            return 1;
        }

        $sides = $options['special_finish_on_sides'] ?? null;

        if (is_scalar($sides)) {
            return $this->normalizeOptionValue($sides) === 'both_sides' ? 2 : 1;
        }

        if (! is_array($sides)) {
            return 1;
        }

        foreach ($sides as $code => $side) {
            if (
                is_scalar($side)
                && $this->normalizeOptionValue($side) === 'both_sides'
                && in_array($this->normalizeOptionValue($code), $selectedCodes, true)
            ) {
                return 2;
            }
        }

        return 1;
    }

    /**
     * Identify a hot/cold foil pricing process without applying the side
     * multiplier to unrelated processes in condition-based pricing.
     *
     * @param  array<string, mixed>  $process
     */
    private function isFoilProcess(array $process, array $options = []): bool
    {
        $rawName = strtolower(trim((string) ($process['name'] ?? $process['label'] ?? '')));
        $code = $this->pricingProcessCode($process);

        if ($code === 'foil' || str_contains($code, 'foil')) {
            return true;
        }

        if ($code === 'special_finish') {
            return str_contains($rawName, 'foil')
                || str_contains($rawName, '冷烫')
                || str_contains($rawName, '热烫')
                || $this->hasFoilSelection($options['special_finish'] ?? null);
        }

        return $this->isFoilOptionCode($code)
            || str_contains($rawName, 'foil')
            || str_contains($rawName, '烫金');
    }

    private function hasFoilSelection(mixed $selectedFinish): bool
    {
        $values = is_array($selectedFinish) ? $selectedFinish : [$selectedFinish];

        foreach ($values as $value) {
            if (is_scalar($value) && $this->isFoilOptionCode($this->normalizeOptionValue($value))) {
                return true;
            }
        }

        return false;
    }

    private function isFoilOptionCode(string $code): bool
    {
        return str_contains($code, 'foil')
            || str_starts_with($code, 'cold_')
            || in_array($code, [
                'black_gold',
                'blue_gold',
                'bright_gold',
                'bright_silver',
                'green_gold',
                'matte_gold',
                'matte_silver',
                'red_gold',
                'rose_gold',
                'aged_gold',
                'muted_purple_gold',
            ], true);
    }
}
