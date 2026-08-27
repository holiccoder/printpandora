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
     *
     * @return array<string, mixed>
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
     *
     * @return array<string, mixed>
     */
    public function captureOrder(string $paypalOrderId): array
    {
        $token = $this->accessToken();

        $response = Http::withToken($token)
            ->acceptJson()
            ->withBody('{}', 'application/json')
            ->post($this->baseUrl().'/v2/checkout/orders/'.$paypalOrderId.'/capture');

        if (! $response->successful()) {
            throw new RuntimeException('Failed to capture PayPal order: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Fetch the current state of a PayPal order.
     *
     * @return array<string, mixed>
     */
    public function showOrder(string $paypalOrderId): array
    {
        $token = $this->accessToken();

        $response = Http::withToken($token)
            ->acceptJson()
            ->get($this->baseUrl().'/v2/checkout/orders/'.$paypalOrderId);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to fetch PayPal order: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Verify a PayPal webhook with PayPal's postback verification endpoint.
     *
     * @param  array<string, string|null>  $headers
     * @param  array<string, mixed>  $event
     */
    public function verifyWebhookSignature(array $headers, array $event, string $webhookId): bool
    {
        $requiredHeaders = [
            'auth_algo',
            'cert_url',
            'transmission_id',
            'transmission_sig',
            'transmission_time',
        ];

        foreach ($requiredHeaders as $header) {
            if (empty($headers[$header])) {
                return false;
            }
        }

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post($this->baseUrl().'/v1/notifications/verify-webhook-signature', [
                'auth_algo' => $headers['auth_algo'],
                'cert_url' => $headers['cert_url'],
                'transmission_id' => $headers['transmission_id'],
                'transmission_sig' => $headers['transmission_sig'],
                'transmission_time' => $headers['transmission_time'],
                'webhook_id' => $webhookId,
                'webhook_event' => $event,
            ]);

        return $response->successful()
            && $response->json('verification_status') === 'SUCCESS';
    }

    /**
     * Extract the capture ID from a capture response or a shown order.
     *
     * @param  array<string, mixed>  $payload
     */
    public function captureId(array $payload): ?string
    {
        $captureId = data_get($payload, 'purchase_units.0.payments.captures.0.id');

        return is_string($captureId) && $captureId !== '' ? $captureId : null;
    }
}
