<?php

namespace App\Services;

use InvalidArgumentException;

class ShippingService
{
    /**
     * @return array<int, array{code: string, label: string, carrier: string, fee: float, description: string, estimated_delivery: string}>
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
     * @return array{code: string, label: string, carrier: string, fee: float, description: string, estimated_delivery: string}
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
}
