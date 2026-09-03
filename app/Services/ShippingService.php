<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class ShippingService
{
    public function __construct(
        private readonly SiteSettingsService $siteSettings,
    ) {}

    /**
     * Return the configured methods, optionally priced for a destination.
     *
     * `country_rates` is included in the response so the checkout can update
     * the displayed price immediately when the customer changes country. The
     * server still recalculates the selected country at order creation time.
     *
     * @return array<int, array{code: string, label: string, carrier: string, fee: float, country_rates: array<string, float>, description: string, estimated_delivery: string, max_business_days: int}>
     */
    public function methods(?string $countryCode = null, ?float $weightGrams = null): array
    {
        $configured = config('shipping.methods', []);
        $methods = [];
        $countryCode = $this->normalizeCountryCode($countryCode);

        if (! is_array($configured)) {
            return $methods;
        }

        foreach ($configured as $code => $method) {
            if (! is_array($method)) {
                continue;
            }

            $countryRates = $this->countryRates($method['country_rates'] ?? []);
            $rateCurrency = strtoupper((string) ($method['rate_currency'] ?? 'USD'));
            $weightTiers = $this->weightTiers($method['weight_tiers'] ?? []);
            $weightRoundingKg = $this->weightRoundingKg($method['weight_rounding_kg'] ?? null);
            $fuelSurchargeMultiplier = $this->fuelSurchargeMultiplier(
                $code === 'dhl_express'
                    ? $this->dhlFuelSurchargePercent($method['fuel_surcharge_percent'] ?? 0)
                    : ($method['fuel_surcharge_percent'] ?? 0),
            );
            $countryRates = $this->priceRates(
                $countryRates,
                $weightTiers,
                $rateCurrency,
                $weightGrams,
                $weightRoundingKg,
                $fuelSurchargeMultiplier,
            );
            $fallbackFee = $this->priceFallbackFee(
                (float) ($method['fee'] ?? 0),
                $rateCurrency,
                $weightGrams,
                $weightRoundingKg,
                $fuelSurchargeMultiplier,
            );

            $methods[] = [
                'code' => (string) $code,
                'label' => (string) ($method['label'] ?? $code),
                'carrier' => (string) ($method['carrier'] ?? $code),
                'fee' => $countryCode !== null && array_key_exists($countryCode, $countryRates)
                    ? $countryRates[$countryCode]
                    : $fallbackFee,
                'country_rates' => $countryRates,
                'description' => (string) ($method['description'] ?? ''),
                'estimated_delivery' => (string) ($method['estimated_delivery'] ?? ''),
                'max_business_days' => max(0, (int) ($method['max_business_days'] ?? 0)),
            ];
        }

        return $methods;
    }

    /**
     * @return array<int, string>
     */
    public function codes(): array
    {
        return array_column($this->methods(), 'code');
    }

    public function defaultMethod(): string
    {
        $configuredDefault = (string) config('shipping.default_method', 'standard');

        return in_array($configuredDefault, $this->codes(), true)
            ? $configuredDefault
            : ($this->codes()[0] ?? throw new InvalidArgumentException('No shipping methods are configured.'));
    }

    /**
     * @return array{code: string, label: string, carrier: string, fee: float, country_rates: array<string, float>, description: string, estimated_delivery: string, max_business_days: int}
     */
    public function get(string $code, ?string $countryCode = null, ?float $weightGrams = null): array
    {
        foreach ($this->methods($countryCode, $weightGrams) as $method) {
            if ($method['code'] === $code) {
                return $method;
            }
        }

        throw new InvalidArgumentException("Unknown shipping method [{$code}].");
    }

    public function fee(string $code, ?string $countryCode = null, ?float $weightGrams = null): float
    {
        return $this->get($code, $countryCode, $weightGrams)['fee'];
    }

    public function feeForWeight(string $code, float $weightGrams, ?string $countryCode = null): float
    {
        return $this->fee($code, $countryCode, $weightGrams);
    }

    public function latestDeliveryDate(string $code, ?DateTimeInterface $from = null): CarbonImmutable
    {
        $start = $from === null
            ? CarbonImmutable::now()
            : CarbonImmutable::instance($from);

        return $start
            ->startOfDay()
            ->addWeekdays($this->get($code)['max_business_days']);
    }

    public function rateBasisWeightKg(): float
    {
        return max(0.001, (float) config('shipping.rate_basis_weight_kg', 1));
    }

    public function defaultCountry(): string
    {
        return $this->normalizeCountryCode((string) config('shipping.default_country', 'US')) ?? 'US';
    }

    /**
     * @return array<string, float>
     */
    private function countryRates(mixed $configured): array
    {
        if (! is_array($configured)) {
            return [];
        }

        $rates = [];

        foreach ($configured as $countryCode => $fee) {
            if (! is_numeric($fee)) {
                continue;
            }

            $normalizedCountry = $this->normalizeCountryCode((string) $countryCode);
            if ($normalizedCountry === null) {
                continue;
            }

            $rates[$normalizedCountry] = round((float) $fee, 2);
        }

        return $rates;
    }

    /**
     * @param  array<string, float>  $rates
     * @param  array<string, array<int, array{max_weight_kg: float, min_weight_kg?: float, weight_rounding_kg?: float, freight_rmb_per_kg?: float, registration_rmb_per_ticket?: float, rate_rmb?: float}>>  $weightTiers
     * @return array<string, float>
     */
    private function priceRates(
        array $rates,
        array $weightTiers,
        string $rateCurrency,
        ?float $weightGrams,
        ?float $weightRoundingKg,
        float $fuelSurchargeMultiplier,
    ): array {
        foreach ($rates as $countryCode => $baseFee) {
            $rates[$countryCode] = $this->priceForCountry(
                $baseFee,
                $weightTiers[$countryCode] ?? [],
                $rateCurrency,
                $weightGrams,
                $weightRoundingKg,
                $fuelSurchargeMultiplier,
            );
        }

        return $rates;
    }

    /**
     * Normalize a method's optional weight-tier configuration.
     *
     * @return array<string, array<int, array{max_weight_kg: float, min_weight_kg?: float, weight_rounding_kg?: float, freight_rmb_per_kg?: float, registration_rmb_per_ticket?: float, rate_rmb?: float}>>
     */
    private function weightTiers(mixed $configured): array
    {
        if (! is_array($configured)) {
            return [];
        }

        $tiersByCountry = [];

        foreach ($configured as $countryCode => $configuredTiers) {
            $normalizedCountry = $this->normalizeCountryCode((string) $countryCode);

            if ($normalizedCountry === null || ! is_array($configuredTiers)) {
                continue;
            }

            $tiers = [];

            foreach ($configuredTiers as $tier) {
                if (! is_array($tier)) {
                    continue;
                }

                $maxWeight = $tier['max_weight_kg'] ?? null;
                $minWeight = $tier['min_weight_kg'] ?? null;
                $tierWeightRounding = $tier['weight_rounding_kg'] ?? null;
                $freight = $tier['freight_rmb_per_kg'] ?? null;
                $registration = $tier['registration_rmb_per_ticket'] ?? null;
                $fixedRate = $tier['rate_rmb'] ?? null;

                if (! is_numeric($maxWeight)) {
                    continue;
                }

                $maxWeight = (float) $maxWeight;

                if (! is_finite($maxWeight) || $maxWeight < 0) {
                    continue;
                }

                if ($minWeight !== null) {
                    if (! is_numeric($minWeight)) {
                        continue;
                    }

                    $minWeight = (float) $minWeight;

                    if (! is_finite($minWeight) || $minWeight < 0 || $minWeight > $maxWeight) {
                        continue;
                    }
                }

                if ($tierWeightRounding !== null) {
                    if (! is_numeric($tierWeightRounding)) {
                        continue;
                    }

                    $tierWeightRounding = (float) $tierWeightRounding;

                    if (! is_finite($tierWeightRounding) || $tierWeightRounding <= 0) {
                        continue;
                    }
                }

                if (is_numeric($fixedRate)) {
                    $fixedRate = (float) $fixedRate;

                    if (is_finite($fixedRate) && $fixedRate >= 0) {
                        $normalizedTier = [
                            'max_weight_kg' => $maxWeight,
                            'rate_rmb' => $fixedRate,
                        ];

                        if ($minWeight !== null) {
                            $normalizedTier['min_weight_kg'] = $minWeight;
                        }

                        if ($tierWeightRounding !== null) {
                            $normalizedTier['weight_rounding_kg'] = $tierWeightRounding;
                        }

                        $tiers[] = $normalizedTier;
                    }

                    continue;
                }

                if (! is_numeric($freight)) {
                    continue;
                }

                $freight = (float) $freight;
                $registration = is_numeric($registration) ? (float) $registration : 0.0;

                if (! is_finite($freight) || ! is_finite($registration)
                    || $freight < 0 || $registration < 0) {
                    continue;
                }

                $normalizedTier = [
                    'max_weight_kg' => $maxWeight,
                    'freight_rmb_per_kg' => $freight,
                    'registration_rmb_per_ticket' => $registration,
                ];

                if ($minWeight !== null) {
                    $normalizedTier['min_weight_kg'] = $minWeight;
                }

                if ($tierWeightRounding !== null) {
                    $normalizedTier['weight_rounding_kg'] = $tierWeightRounding;
                }

                $tiers[] = $normalizedTier;
            }

            usort($tiers, static fn (array $left, array $right): int => $left['max_weight_kg'] <=> $right['max_weight_kg']);

            if ($tiers !== []) {
                $tiersByCountry[$normalizedCountry] = $tiers;
            }
        }

        return $tiersByCountry;
    }

    /**
     * @param  array<int, array{max_weight_kg: float, min_weight_kg?: float, weight_rounding_kg?: float, freight_rmb_per_kg?: float, registration_rmb_per_ticket?: float, rate_rmb?: float}>  $tiers
     */
    private function priceForCountry(
        float $baseFee,
        array $tiers,
        string $rateCurrency,
        ?float $weightGrams,
        ?float $weightRoundingKg,
        float $fuelSurchargeMultiplier,
    ): float {
        $weightKg = $this->weightKg($weightGrams, $weightRoundingKg);
        $tier = $this->tierForWeight($tiers, $weightKg);

        if ($tier !== null) {
            $tierWeightKg = $this->weightKg(
                $weightGrams,
                $this->weightRoundingKg($tier['weight_rounding_kg'] ?? null) ?? $weightRoundingKg,
            );
            $sourceFee = array_key_exists('rate_rmb', $tier)
                ? $tier['rate_rmb']
                : (($tier['freight_rmb_per_kg'] ?? 0) * $tierWeightKg)
                    + ($tier['registration_rmb_per_ticket'] ?? 0);

            $sourceFee *= $fuelSurchargeMultiplier;

            return $this->convertFee($sourceFee, $rateCurrency);
        }

        $sourceFee = $weightGrams === null
            ? round($baseFee, 2)
            : $this->scaleFee($baseFee, $weightGrams, $weightRoundingKg);

        $sourceFee *= $fuelSurchargeMultiplier;

        return $this->convertFee($sourceFee, $rateCurrency);
    }

    /**
     * @param  array<int, array{max_weight_kg: float, min_weight_kg?: float, weight_rounding_kg?: float, freight_rmb_per_kg?: float, registration_rmb_per_ticket?: float, rate_rmb?: float}>  $tiers
     * @return array{max_weight_kg: float, min_weight_kg?: float, weight_rounding_kg?: float, freight_rmb_per_kg?: float, registration_rmb_per_ticket?: float, rate_rmb?: float}|null
     */
    private function tierForWeight(array $tiers, float $weightKg): ?array
    {
        foreach ($tiers as $tier) {
            if ($weightKg >= ($tier['min_weight_kg'] ?? 0)
                && $weightKg <= $tier['max_weight_kg']) {
                return $tier;
            }
        }

        return null;
    }

    private function priceFallbackFee(
        float $baseFee,
        string $rateCurrency,
        ?float $weightGrams,
        ?float $weightRoundingKg,
        float $fuelSurchargeMultiplier,
    ): float {
        $sourceFee = $weightGrams === null
            ? round($baseFee, 2)
            : $this->scaleFee($baseFee, $weightGrams, $weightRoundingKg);

        return $this->convertFee(
            $sourceFee * $fuelSurchargeMultiplier,
            $rateCurrency,
        );
    }

    private function weightKg(?float $weightGrams, ?float $weightRoundingKg = null): float
    {
        $weightKg = $weightGrams === null
            ? $this->rateBasisWeightKg()
            : max(0.0, $weightGrams) / 1000;

        if ($weightRoundingKg === null || $weightRoundingKg <= 0 || $weightKg <= 0) {
            return $weightKg;
        }

        return round(
            ceil(($weightKg - 0.000000001) / $weightRoundingKg) * $weightRoundingKg,
            4,
        );
    }

    private function convertFee(float $sourceFee, string $sourceCurrency): float
    {
        if (in_array(strtoupper($sourceCurrency), ['RMB', 'CNY'], true)
            && strtoupper((string) config('shipping.currency', 'USD')) === 'USD') {
            $sourceFee *= $this->rmbToUsdRate();
        }

        return round($sourceFee, 2);
    }

    private function rmbToUsdRate(): float
    {
        return max(0.000001, (float) config('shipping.rmb_to_usd_rate', 0.14));
    }

    private function dhlFuelSurchargePercent(mixed $fallback): mixed
    {
        $configured = $this->siteSettings->shipping()['dhl_fuel_surcharge'] ?? null;

        return is_numeric($configured) ? $configured : $fallback;
    }

    private function scaleFee(float $fee, ?float $weightGrams, ?float $weightRoundingKg = null): float
    {
        if ($weightGrams === null) {
            return round($fee, 2);
        }

        $weightKg = $this->weightKg($weightGrams, $weightRoundingKg);

        return round($fee * ($weightKg / $this->rateBasisWeightKg()), 2);
    }

    private function weightRoundingKg(mixed $configured): ?float
    {
        if (! is_numeric($configured)) {
            return null;
        }

        $roundingKg = (float) $configured;

        return is_finite($roundingKg) && $roundingKg > 0 ? $roundingKg : null;
    }

    private function fuelSurchargeMultiplier(mixed $configured): float
    {
        if (! is_numeric($configured)) {
            return 1.0;
        }

        $percentage = (float) $configured;

        if (! is_finite($percentage) || $percentage < 0) {
            return 1.0;
        }

        return 1 + ($percentage / 100);
    }

    private function normalizeCountryCode(?string $countryCode): ?string
    {
        $countryCode = strtoupper(trim((string) $countryCode));

        return $countryCode === '' ? null : $countryCode;
    }
}
