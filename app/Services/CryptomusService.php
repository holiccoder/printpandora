<?php

namespace App\Services;

use Cryptomus\Api\Client;
use Cryptomus\Api\RequestBuilderException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CryptomusService
{
    /**
     * Determine whether Cryptomus payment credentials are configured.
     */
    public function isConfigured(): bool
    {
        return ! empty(config('services.cryptomus.merchant_uuid'))
            && ! empty(config('services.cryptomus.payment_key'));
    }

    /**
     * Whether Cryptomus is running in test mode.
     */
    public function isTest(): bool
    {
        return (bool) config('services.cryptomus.test', true);
    }

    /**
     * The fiat currency used for Cryptomus invoices.
     */
    public function currency(): string
    {
        return config('services.cryptomus.currency', 'USD');
    }

    /**
     * Create a Cryptomus payment invoice and return the API result.
     *
     * @throws RuntimeException
     */
    public function createInvoice(float $amount, int|string $orderId, ?string $returnUrl = null, ?string $callbackUrl = null): array
    {
        $client = $this->paymentClient();

        $payload = [
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $this->currency(),
            'order_id' => (string) $orderId,
            'url_return' => $returnUrl ?? route('shop.orders.show', $orderId),
            'url_callback' => $callbackUrl ?? route('shop.checkout.cryptomus.webhook'),
            'lifetime' => '7200',
        ];

        try {
            $result = $client->create($payload);
        } catch (RequestBuilderException $e) {
            Log::error('Cryptomus create invoice failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to create Cryptomus invoice: '.$e->getMessage());
        }

        if (empty($result) || ! is_array($result)) {
            throw new RuntimeException('Cryptomus returned an empty invoice response.');
        }

        return $result;
    }

    /**
     * Fetch information about an existing Cryptomus invoice.
     *
     * @throws RuntimeException
     */
    public function invoiceInfo(string $uuid): array
    {
        try {
            return $this->paymentClient()->info(['uuid' => $uuid]);
        } catch (RequestBuilderException $e) {
            Log::error('Cryptomus invoice info failed', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to fetch Cryptomus invoice info: '.$e->getMessage());
        }
    }

    /**
     * Verify a Cryptomus webhook signature.
     *
     * Cryptomus signs the base64-encoded JSON body with the payment key using
     * the same algorithm the SDK uses for outgoing requests:
     *   sign = md5(base64_encode($body) . $secretKey)
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $key = config('services.cryptomus.payment_key');

        if (empty($key) || empty($signature)) {
            return false;
        }

        $computed = md5(base64_encode($payload).$key);

        return hash_equals($computed, $signature);
    }

    /**
     * Get the configured Cryptomus payment client.
     *
     * @throws RuntimeException
     */
    protected function paymentClient(): \Cryptomus\Api\Payment
    {
        $key = config('services.cryptomus.payment_key');
        $uuid = config('services.cryptomus.merchant_uuid');

        if (empty($key) || empty($uuid)) {
            throw new RuntimeException('Cryptomus credentials are not configured.');
        }

        return Client::payment($key, $uuid);
    }
}
