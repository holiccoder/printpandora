<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class ShippingService
{
    /**
     * @return array<int, array{code: string, label: string, carrier: string, fee: float, description: string, estimated_delivery: string, max_business_days: int}>
     */
    public function methods(): array
    {
        $configured = config('shipping.methods', []);
        $methods = [];

        if (! is_array($configured)) {
            return $methods;
        }

        foreach ($configured as $code => $method) {
            if (! is_array($method)) {
                continue;
            }

            $methods[] = [
                'code' => (string) $code,
                'label' => (string) ($method['label'] ?? $code),
                'carrier' => (string) ($method['carrier'] ?? $code),
                'fee' => round((float) ($method['fee'] ?? 0), 2),
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
     * @return array{code: string, label: string, carrier: string, fee: float, description: string, estimated_delivery: string, max_business_days: int}
     */
    public function get(string $code): array
    {
        foreach ($this->methods() as $method) {
            if ($method['code'] === $code) {
                return $method;
            }
        }

        throw new InvalidArgumentException("Unknown shipping method [{$code}].");
    }

    public function fee(string $code): float
    {
        return $this->get($code)['fee'];
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
}
