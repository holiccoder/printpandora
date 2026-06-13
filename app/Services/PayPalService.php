<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayPalService
{
    public function isSandbox(): bool
    {
        return config('services.paypal.mode', 'sandbox') === 'sandbox';
    }

    public function baseUrl(): string
    {
        return $this->isSandbox()
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    public function clientId(): ?string
    {
        return config('services.paypal.client_id');
    }

    public function currency(): string
    {
        return config('services.paypal.currency', 'USD');
    }

    /**
     * Fetch an OAuth2 access token from PayPal.
     */
    protected function accessToken(): string
    {
        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.client_secret');

        if (empty($clientId) || empty($secret)) {
            throw new RuntimeException('PayPal credentials are not configured.');
        }

        $response = Http::asForm()
            ->withBasicAuth($clientId, $secret)
            ->post($this->baseUrl().'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to authenticate with PayPal: '.$response->body());
        }

        return $response->json('access_token');
    }

    /**
     * Create a PayPal order and return its id.
     */
    public function createOrder(float $amount, string $reference): array
    {
        $token = $this->accessToken();

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($this->baseUrl().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $reference,
                    'amount' => [
                        'currency_code' => $this->currency(),
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ]],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to create PayPal order: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Capture an approved PayPal order.
     */
    public function captureOrder(string $paypalOrderId): array
    {
        $token = $this->accessToken();

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($this->baseUrl().'/v2/checkout/orders/'.$paypalOrderId.'/capture');

        if (! $response->successful()) {
            throw new RuntimeException('Failed to capture PayPal order: '.$response->body());
        }

        return $response->json();
    }
}
