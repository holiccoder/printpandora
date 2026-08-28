<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class ShippingService
{
    /**
     * Return the configured methods, optionally priced for a destination.
     *
     * `country_rates` is included in the response so the checkout can update
     * the displayed price immediately when the customer changes country. The
     * server still recalculates the selected country at order creation time.
     *
     * @return array<int, array{code: string, label: string, carrier: string, fee: float, country_rates: array<string, float>, description: string, estimated_delivery: string, max_business_days: int}>
     */
    public function methods(?string $countryCode = null): array
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
            $fallbackFee = round((float) ($method['fee'] ?? 0), 2);

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
    public function get(string $code, ?string $countryCode = null): array
    {
        foreach ($this->methods($countryCode) as $method) {
            if ($method['code'] === $code) {
                return $method;
            }
        }

        throw new InvalidArgumentException("Unknown shipping method [{$code}].");
    }

    public function fee(string $code, ?string $countryCode = null): float
    {
        return $this->get($code, $countryCode)['fee'];
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

    private function normalizeCountryCode(?string $countryCode): ?string
    {
        $countryCode = strtoupper(trim((string) $countryCode));

        return $countryCode === '' ? null : $countryCode;
    }
}
