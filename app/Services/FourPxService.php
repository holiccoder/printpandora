<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class FourPxService
{
    /**
     * Return whether the minimum credentials and product configuration exist.
     */
    public function isConfigured(): bool
    {
        return $this->configurationErrors() === [];
    }

    /**
     * @return array<int, string>
     */
    public function configurationErrors(bool $requireProductCode = true): array
    {
        $errors = [];

        if (! (bool) config('services.four_px.enabled', false)) {
            $errors[] = 'Set FOURPX_ENABLED=true.';
        }

        $requiredConfiguration = [
            'app_key' => 'FOURPX_APP_KEY',
            'app_secret' => 'FOURPX_APP_SECRET',
            'access_token' => 'FOURPX_ACCESS_TOKEN',
        ];

        if ($requireProductCode) {
            $requiredConfiguration['logistics_product_code'] = 'FOURPX_LOGISTICS_PRODUCT_CODE';
        }

        foreach ($requiredConfiguration as $key => $environmentVariable) {
            if (blank(config('services.four_px.'.$key))) {
                $errors[] = "Set {$environmentVariable}.";
            }
        }

        return $errors;
    }

    /**
     * Create a 4PX direct-shipping pre-alert order.
     *
     * The order reference is stored before the API call so a retry can reuse
     * the same reference and avoid creating a new local shipment identity.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function createShipment(Order $order, array $overrides = []): Order
    {
        $hasProductOverride = array_key_exists('logistics_product_code', $overrides)
            && trim((string) $overrides['logistics_product_code']) !== '';

        $this->assertConfigured(! $hasProductOverride);

        if ($order->fourpx_consignment_no && $order->fourpx_status !== 'failed') {
            return $this->refreshShipment($order);
        }

        $order->loadMissing('items.product');

        $reference = $order->fourpx_ref_no ?: $this->referenceFor($order);
        $payload = $this->buildCreatePayload($order, $reference, $overrides);

        $order->forceFill([
            'fourpx_ref_no' => $reference,
            'fourpx_status' => 'creating',
            'fourpx_last_error' => null,
        ])->save();

        try {
            $result = $this->request(
                'ds.xms.order.create',
                $payload,
                (string) config('services.four_px.order_version', '1.1'),
            );

            return $this->applyConsignmentResponse($order, $result, 'P');
        } catch (Throwable $exception) {
            $this->markFailure($order, $exception);

            throw $exception;
        }
    }

    /**
     * Refresh the 4PX order status and any available tracking numbers.
     */
    public function refreshShipment(Order $order): Order
    {
        $this->assertConfigured(false);

        $requestNo = $order->fourpx_consignment_no
            ?: $order->fourpx_ref_no
            ?: $order->fourpx_tracking_number;

        if (blank($requestNo)) {
            throw new RuntimeException('Create the 4PX shipment before refreshing it.');
        }

        try {
            $result = $this->request(
                'ds.xms.order.get',
                ['request_no' => $requestNo],
                (string) config('services.four_px.query_version', '1.1'),
            );

            return $this->applyConsignmentResponse($order, $result);
        } catch (Throwable $exception) {
            $this->markFailure($order, $exception);

            throw $exception;
        }
    }

    /**
     * Ask 4PX for a PDF label URL when the account/product is authorized for it.
     */
    public function fetchLabel(Order $order): Order
    {
        $this->assertConfigured(false);

        $requestNo = $order->fourpx_consignment_no
            ?: $order->fourpx_ref_no
            ?: $order->fourpx_tracking_number;

        if (blank($requestNo)) {
            throw new RuntimeException('Create the 4PX shipment before requesting a label.');
        }

        try {
            $result = $this->request(
                'ds.xms.label.get',
                [
                    'request_no' => $requestNo,
                    'response_label_format' => 'PDF',
                    'is_print_time' => 'N',
                    'is_print_buyer_id' => 'N',
                    'is_print_pick_info' => 'N',
                    'is_print_declaration_list' => 'N',
                    'create_package_label' => 'N',
                ],
                (string) config('services.four_px.label_version', '1.1'),
            );

            $data = $result['data'];
            $labelUrl = data_get($data, 'label_url_info.logistics_label')
                ?: data_get($data, 'logistics_label')
                ?: (is_string($data) ? $data : null);

            if (blank($labelUrl)) {
                throw new RuntimeException('4PX did not return a label URL.');
            }

            $order->forceFill([
                'fourpx_label_url' => $labelUrl,
                'fourpx_response' => $this->envelopeWithDecodedData($result),
                'fourpx_last_error' => null,
            ])->save();

            return $order->refresh();
        } catch (Throwable $exception) {
            $this->markFailure($order, $exception);

            throw $exception;
        }
    }

    /**
     * Fetch the latest tracking events for the best available 4PX number.
     */
    public function refreshTracking(Order $order): Order
    {
        $this->assertConfigured(false);

        $trackingNumber = $order->fourpx_logistics_channel_no
            ?: $order->fourpx_tracking_number
            ?: $order->tracking_number;

        if (blank($trackingNumber)) {
            throw new RuntimeException('4PX has not returned a tracking number yet.');
        }

        try {
            $result = $this->request(
                'tr.order.tracking.get',
                ['deliveryOrderNo' => $trackingNumber],
                (string) config('services.four_px.tracking_version', '1.0'),
            );

            $order->forceFill([
                'fourpx_tracking_response' => $this->envelopeWithDecodedData($result),
                'fourpx_last_error' => null,
            ])->save();

            return $order->refresh();
        } catch (Throwable $exception) {
            $this->markFailure($order, $exception);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function buildCreatePayload(Order $order, string $reference, array $overrides): array
    {
        $weight = (int) $this->valueFrom(
            $overrides,
            'weight_grams',
            $order->shipping_weight_grams ?: config('services.four_px.default_weight_grams'),
        );

        if ($weight <= 0) {
            throw new RuntimeException('Enter the parcel weight in grams before creating the 4PX shipment.');
        }

        $length = $this->decimalValue($this->valueFrom(
            $overrides,
            'length_cm',
            $order->shipping_length_cm ?: config('services.four_px.default_length_cm'),
        ));
        $width = $this->decimalValue($this->valueFrom(
            $overrides,
            'width_cm',
            $order->shipping_width_cm ?: config('services.four_px.default_width_cm'),
        ));
        $height = $this->decimalValue($this->valueFrom(
            $overrides,
            'height_cm',
            $order->shipping_height_cm ?: config('services.four_px.default_height_cm'),
        ));

        $dimensions = [$length, $width, $height];
        if (count(array_filter($dimensions, static fn (?float $value): bool => $value !== null)) > 0
            && count(array_filter($dimensions, static fn (?float $value): bool => $value !== null)) < 3) {
            throw new RuntimeException('Enter all three parcel dimensions in centimetres, or leave them all empty.');
        }

        $productCode = trim((string) $this->valueFrom(
            $overrides,
            'logistics_product_code',
            config('services.four_px.logistics_product_code'),
        ));

        if ($productCode === '') {
            throw new RuntimeException('Set FOURPX_LOGISTICS_PRODUCT_CODE to the 4PX standard product code.');
        }

        $sender = $this->configuredAddress('sender');
        $this->validateAddress($sender, ['first_name', 'phone', 'country', 'city'], '4PX sender');

        $recipient = $this->recipientAddress($order);
        $this->validateAddress($recipient, ['first_name', 'phone', 'country', 'city', 'street'], '4PX recipient');

        $currency = strtoupper((string) config('services.four_px.declare_currency', 'USD'));
        $items = $order->items;
        $parcelValue = round((float) $items->sum('subtotal'), 2);
        $products = [];
        $declarations = [];

        foreach ($items as $item) {
            $name = Str::limit((string) ($item->product?->name ?: 'Printed products'), 64, '');
            $quantity = max(1, (int) $item->quantity);
            $unitPrice = round((float) $item->unit_price, 2);

            $products[] = [
                'sku_code' => 'product-'.$item->product_id,
                'product_name' => $name,
                'product_description' => $name,
                'product_unit_price' => $unitPrice,
                'currency' => $currency,
                'qty' => $quantity,
            ];

            $declarations[] = [
                'declare_product_name_en' => $name,
                'declare_product_code_qty' => $quantity,
                'unit_declare_product' => 'pcs',
                'origin_country' => strtoupper((string) config('services.four_px.origin_country', 'CN')),
                'declare_unit_price_export' => $unitPrice,
                'currency_export' => $currency,
            ];
        }

        if ($products === []) {
            throw new RuntimeException('The order has no items to declare to 4PX.');
        }

        $parcel = [
            'weight' => $weight,
            'parcel_value' => $parcelValue,
            'currency' => $currency,
            'include_battery' => (string) config('services.four_px.include_battery', 'N'),
            'product_list' => $products,
            'declare_product_info' => $declarations,
        ];

        if ($length !== null && $width !== null && $height !== null) {
            $parcel['length'] = $length;
            $parcel['width'] = $width;
            $parcel['height'] = $height;
        }

        if (filled(config('services.four_px.battery_type'))) {
            $parcel['battery_type'] = (string) config('services.four_px.battery_type');
        }

        $returnInfo = [
            'is_return_on_domestic' => (string) config('services.four_px.return_domestic', 'N'),
            'is_return_on_oversea' => (string) config('services.four_px.return_overseas', 'N'),
        ];

        if ($returnInfo['is_return_on_domestic'] === 'Y') {
            $returnInfo['domestic_return_addr'] = $this->configuredAddress('return_domestic_address');
            $this->validateAddress(
                $returnInfo['domestic_return_addr'],
                ['first_name', 'phone', 'post_code', 'country', 'city', 'street'],
                '4PX domestic return',
            );
        }

        if ($returnInfo['is_return_on_oversea'] === 'Y') {
            $returnInfo['oversea_return_addr'] = $this->configuredAddress('return_overseas_address');
            $this->validateAddress(
                $returnInfo['oversea_return_addr'],
                ['first_name', 'phone', 'post_code', 'country', 'city', 'street'],
                '4PX overseas return',
            );
        }

        $deliverType = (string) config('services.four_px.deliver_type', '3');
        if (! in_array($deliverType, ['2', '3', '5'], true)) {
            throw new RuntimeException('FOURPX_DELIVER_TYPE must be 2, 3, or 5 until pickup details are configured.');
        }

        $payload = [
            'ref_no' => $reference,
            'business_type' => (string) config('services.four_px.business_type', 'BDS'),
            'duty_type' => (string) config('services.four_px.duty_type', 'U'),
            'cargo_type' => (string) config('services.four_px.cargo_type', '5'),
            'parcel_qty' => 1,
            'logistics_service_info' => [
                'logistics_product_code' => $productCode,
                'customs_service' => (string) config('services.four_px.customs_service', 'N'),
                'signature_service' => (string) config('services.four_px.signature_service', 'N'),
                'value_added_services' => (string) config('services.four_px.value_added_services', ''),
            ],
            'return_info' => $returnInfo,
            'parcel_list' => [$parcel],
            'is_insure' => 'N',
            'insurance_info' => [],
            'sender' => $sender,
            'recipient_info' => $recipient,
            'deliver_type_info' => array_filter([
                'deliver_type' => $deliverType,
                'warehouse_code' => config('services.four_px.warehouse_code'),
            ], static fn (mixed $value): bool => $value !== null && $value !== ''),
        ];

        if ((bool) config('services.four_px.request_label', false)) {
            $payload['label_config_info'] = [
                'label_size' => 'label_80x90',
                'response_label_format' => 'PDF',
                'create_logistics_label' => 'Y',
                'create_package_label' => 'N',
            ];
        }

        $this->persistPackageMeasurements($order, $weight, $length, $width, $height);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    protected function recipientAddress(Order $order): array
    {
        $name = trim((string) $order->customer_name);
        $parts = preg_split('/\s+/', $name, 2) ?: [$name];

        return array_filter([
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? null,
            'phone' => $order->customer_phone,
            'email' => $order->customer_email,
            'post_code' => $order->shipping_zip,
            'country' => strtoupper((string) $order->shipping_country),
            'state' => $order->shipping_state,
            'city' => $order->shipping_city,
            'street' => $order->shipping_address,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @return array<string, mixed>
     */
    protected function configuredAddress(string $name): array
    {
        $address = config('services.four_px.'.$name, []);

        return is_array($address)
            ? array_filter($address, static fn (mixed $value): bool => $value !== null && $value !== '')
            : [];
    }

    /**
     * @param  array<string, mixed>  $address
     * @param  array<int, string>  $required
     */
    protected function validateAddress(array $address, array $required, string $label): void
    {
        foreach ($required as $field) {
            if (blank($address[$field] ?? null)) {
                throw new RuntimeException("{$label} is missing {$field}.");
            }
        }
    }

    protected function referenceFor(Order $order): string
    {
        return (string) config('services.four_px.reference_prefix', 'PRINTPANDORA-').$order->getKey();
    }

    protected function decimalValue(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = (float) $value;

        return $number > 0 ? round($number, 2) : null;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function valueFrom(array $overrides, string $key, mixed $fallback): mixed
    {
        return array_key_exists($key, $overrides) && $overrides[$key] !== ''
            ? $overrides[$key]
            : $fallback;
    }

    protected function persistPackageMeasurements(
        Order $order,
        int $weight,
        ?float $length,
        ?float $width,
        ?float $height,
    ): void {
        $order->forceFill([
            'shipping_weight_grams' => $weight,
            'shipping_length_cm' => $length,
            'shipping_width_cm' => $width,
            'shipping_height_cm' => $height,
        ])->save();
    }

    /**
     * @param  array{envelope: array<string, mixed>, data: mixed}  $result
     */
    protected function applyConsignmentResponse(Order $order, array $result, ?string $fallbackStatus = null): Order
    {
        $data = is_array($result['data']) ? $result['data'] : [];
        $info = is_array($data['consignment_info'] ?? null)
            ? $data['consignment_info']
            : $data;

        $consignmentNo = $this->stringValue($info['ds_consignment_no'] ?? null);
        $fourPxTracking = $this->stringValue($info['4px_tracking_no'] ?? null);
        $channelNo = $this->stringValue($info['logistics_channel_no'] ?? null);
        $status = $this->stringValue($info['consignment_status'] ?? null) ?: $fallbackStatus;
        $reference = $this->stringValue($info['ref_no'] ?? null);
        $trackingNumber = $channelNo ?: $fourPxTracking;

        $changes = [
            'fourpx_ref_no' => $reference ?: $order->fourpx_ref_no,
            'fourpx_consignment_no' => $consignmentNo ?: $order->fourpx_consignment_no,
            'fourpx_tracking_number' => $fourPxTracking ?: $order->fourpx_tracking_number,
            'fourpx_logistics_channel_no' => $channelNo ?: $order->fourpx_logistics_channel_no,
            'fourpx_status' => $status ?: $order->fourpx_status,
            'fourpx_response' => $this->envelopeWithDecodedData($result),
            'fourpx_last_error' => null,
        ];

        if ($trackingNumber) {
            $changes['tracking_number'] = $trackingNumber;
            $changes['tracking_url'] = $this->trackingUrl($trackingNumber) ?: $order->tracking_url;
        }

        $order->forceFill($changes)->save();

        return $order->refresh();
    }

    protected function trackingUrl(string $trackingNumber): ?string
    {
        $template = config('services.four_px.tracking_url_template');

        return filled($template)
            ? str_replace('{tracking}', rawurlencode($trackingNumber), (string) $template)
            : null;
    }

    /**
     * Keep the useful nested payload decoded when it is stored on the order.
     * 4PX returns the data field as a JSON string for several interfaces.
     *
     * @param  array{envelope: array<string, mixed>, data: mixed}  $result
     * @return array<string, mixed>
     */
    protected function envelopeWithDecodedData(array $result): array
    {
        $envelope = $result['envelope'];
        $envelope['data'] = $result['data'];

        return $envelope;
    }

    protected function stringValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    protected function assertConfigured(bool $requireProductCode = true): void
    {
        $errors = $this->configurationErrors($requireProductCode);

        if ($errors !== []) {
            throw new RuntimeException('4PX is not configured: '.implode(' ', $errors));
        }
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array{envelope: array<string, mixed>, data: mixed}
     */
    protected function request(string $method, array $body, string $version): array
    {
        $appKey = (string) config('services.four_px.app_key');
        $appSecret = (string) config('services.four_px.app_secret');
        $bodyJson = json_encode(
            $body,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        );

        $publicParameters = [
            'app_key' => $appKey,
            'format' => 'json',
            'method' => $method,
            'timestamp' => (string) ((int) floor(microtime(true) * 1000)),
            'v' => $version,
        ];

        ksort($publicParameters, SORT_STRING);

        $signatureSource = '';
        foreach ($publicParameters as $name => $value) {
            $signatureSource .= $name.$value;
        }

        $signatureSource .= $bodyJson.$appSecret;
        $query = $publicParameters + ['sign' => md5($signatureSource)];

        foreach ([
            'access_token' => config('services.four_px.access_token'),
            'language' => config('services.four_px.language'),
        ] as $name => $value) {
            if (filled($value)) {
                $query[$name] = (string) $value;
            }
        }

        $baseUrl = config('services.four_px.environment', 'test') === 'production'
            ? config('services.four_px.base_url')
            : config('services.four_px.test_base_url');

        $url = rtrim((string) $baseUrl, '?&')
            .'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        $response = Http::timeout((int) config('services.four_px.timeout', 30))
            ->acceptJson()
            ->withBody($bodyJson, 'application/json')
            ->post($url);

        if (! $response->successful()) {
            throw new RuntimeException('4PX HTTP request failed ('.$response->status().'): '.$response->body());
        }

        $envelope = $response->json();
        if (! is_array($envelope)) {
            throw new RuntimeException('4PX returned an invalid JSON response.');
        }

        if ((string) ($envelope['result'] ?? '0') !== '1') {
            $errors = $envelope['errors'] ?? null;
            $errorText = is_string($errors) ? $errors : json_encode($errors, JSON_UNESCAPED_UNICODE);
            throw new RuntimeException(trim((string) ($envelope['msg'] ?? '4PX request failed').' '.($errorText ?: '')));
        }

        return [
            'envelope' => $envelope,
            'data' => $this->decodeData($envelope['data'] ?? null),
        ];
    }

    protected function decodeData(mixed $data): mixed
    {
        for ($attempt = 0; $attempt < 2 && is_string($data); $attempt++) {
            $decoded = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                break;
            }
            $data = $decoded;
        }

        return $data;
    }

    protected function markFailure(Order $order, Throwable $exception): void
    {
        try {
            $order->forceFill([
                'fourpx_status' => 'failed',
                'fourpx_last_error' => Str::limit($exception->getMessage(), 2000, '...'),
            ])->save();
        } catch (Throwable $saveException) {
            Log::error('Unable to persist 4PX error state', [
                'order_id' => $order->getKey(),
                'error' => $saveException->getMessage(),
            ]);
        }
    }
}
