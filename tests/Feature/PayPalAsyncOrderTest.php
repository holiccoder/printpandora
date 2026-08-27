<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\Cart;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayPalAsyncOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paypal.mode' => 'sandbox',
            'services.paypal.client_id' => 'sandbox-client-id',
            'services.paypal.client_secret' => 'sandbox-client-secret',
            'services.paypal.webhook_id' => 'sandbox-webhook-id',
            'services.paypal.currency' => 'USD',
        ]);
    }

    public function test_paypal_create_persists_a_pending_local_order(): void
    {
        [$user] = $this->createCartForUser();
        $this->fakePayPalCreate();

        $response = $this->actingAs($user)->postJson(
            route('shop.checkout.paypal.create'),
            $this->checkoutData(),
        );

        $response->assertOk()->assertJson([
            'id' => 'PAYPAL-ORDER-1',
        ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'payment_method' => 'paypal',
            'payment_status' => 'pending',
            'status' => 'pending',
            'paypal_order_id' => 'PAYPAL-ORDER-1',
            'payment_id' => null,
        ]);
    }

    public function test_paypal_capture_settles_the_existing_order_and_clears_the_cart(): void
    {
        [$user] = $this->createCartForUser();
        $this->fakePayPalCreate();

        $this->actingAs($user)->postJson(
            route('shop.checkout.paypal.create'),
            $this->checkoutData(),
        )->assertOk();

        $captureRequestBody = null;

        Http::fake(function (HttpRequest $request) use (&$captureRequestBody) {
            if (str_ends_with($request->url(), '/v1/oauth2/token')) {
                return Http::response([
                    'access_token' => 'sandbox-access-token',
                ]);
            }

            if (str_ends_with($request->url(), '/v2/checkout/orders/PAYPAL-ORDER-1/capture')) {
                $captureRequestBody = $request->body();

                return Http::response([
                    'id' => 'PAYPAL-ORDER-1',
                    'status' => 'COMPLETED',
                    'purchase_units' => [[
                        'payments' => [
                            'captures' => [[
                                'id' => 'PAYPAL-CAPTURE-1',
                                'status' => 'COMPLETED',
                            ]],
                        ],
                    ]],
                ]);
            }

            return Http::response([], 404);
        });

        $response = $this->actingAs($user)->postJson(
            route('shop.checkout.paypal.capture'),
            ['paypal_order_id' => 'PAYPAL-ORDER-1'],
        );

        $response->assertOk()->assertJsonPath('redirect', route('shop.orders.show', 1));

        $this->assertDatabaseHas('orders', [
            'payment_method' => 'paypal',
            'payment_status' => 'paid',
            'status' => 'processing',
            'paypal_order_id' => 'PAYPAL-ORDER-1',
            'payment_id' => 'PAYPAL-CAPTURE-1',
        ]);
        $this->assertSame('{}', $captureRequestBody);
        $this->assertSame(0, app(Cart::class)->count());
    }

    public function test_completed_capture_webhook_settles_and_deduplicates_the_order(): void
    {
        [$user, $order] = $this->createPendingPayPalOrder();
        $event = $this->captureCompletedEvent($order);
        $this->fakeWebhookVerification();

        $response = $this->postJson(
            route('shop.checkout.paypal.webhook'),
            $event,
            $this->webhookHeaders(),
        );

        $response->assertOk()->assertJson(['received' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => 'processing',
            'payment_id' => 'PAYPAL-CAPTURE-1',
        ]);
        $this->assertDatabaseHas('paypal_webhook_events', [
            'event_id' => 'PAYPAL-EVENT-1',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'order_id' => $order->id,
        ]);

        $this->postJson(
            route('shop.checkout.paypal.webhook'),
            $event,
            $this->webhookHeaders(),
        )->assertOk();

        $this->assertDatabaseCount('paypal_webhook_events', 1);
        $this->assertSame('paid', Order::findOrFail($order->id)->payment_status);
    }

    public function test_approved_webhook_captures_a_payment_when_the_browser_does_not_finish(): void
    {
        [$user, $order] = $this->createPendingPayPalOrder();
        $this->fakeWebhookVerification();
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'sandbox-access-token',
            ]),
            'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response([
                'verification_status' => 'SUCCESS',
            ]),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/PAYPAL-ORDER-1/capture' => Http::response([
                'id' => 'PAYPAL-ORDER-1',
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'payments' => [
                        'captures' => [[
                            'id' => 'PAYPAL-CAPTURE-1',
                            'status' => 'COMPLETED',
                        ]],
                    ],
                ]],
            ]),
        ]);

        $event = [
            'id' => 'PAYPAL-EVENT-APPROVED-1',
            'event_type' => 'CHECKOUT.ORDER.APPROVED',
            'resource' => [
                'id' => 'PAYPAL-ORDER-1',
            ],
        ];

        $this->postJson(
            route('shop.checkout.paypal.webhook'),
            $event,
            $this->webhookHeaders('PAYPAL-EVENT-APPROVED-1'),
        )->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'payment_id' => 'PAYPAL-CAPTURE-1',
        ]);
    }

    public function test_invalid_paypal_webhook_signature_is_rejected(): void
    {
        [$user, $order] = $this->createPendingPayPalOrder();
        $event = $this->captureCompletedEvent($order);

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'sandbox-access-token',
            ]),
            'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response([
                'verification_status' => 'FAILURE',
            ]),
        ]);

        $this->postJson(
            route('shop.checkout.paypal.webhook'),
            $event,
            $this->webhookHeaders(),
        )->assertUnauthorized();

        $this->assertSame('pending', Order::findOrFail($order->id)->payment_status);
        $this->assertDatabaseCount('paypal_webhook_events', 0);
    }

    /** @return array{0: User, 1: Order} */
    private function createPendingPayPalOrder(): array
    {
        [$user] = $this->createCartForUser();
        $this->fakePayPalCreate();

        $this->actingAs($user)->postJson(
            route('shop.checkout.paypal.create'),
            $this->checkoutData(),
        )->assertOk();

        return [$user, Order::query()->where('paypal_order_id', 'PAYPAL-ORDER-1')->firstOrFail()];
    }

    /** @return array{0: User, 1: Product} */
    private function createCartForUser(): array
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user);
        app(Cart::class)->add($product->id);

        return [$user, $product];
    }

    private function makeProduct(): Product
    {
        $category = ProductCategory::create([
            'name' => 'PayPal test products',
            'slug' => 'paypal-test-'.uniqid(),
        ]);

        return Product::create([
            'name' => 'PayPal test product',
            'slug' => 'paypal-test-product-'.uniqid(),
            'product_category_id' => $category->id,
        ]);
    }

    /** @return array<string, string> */
    private function checkoutData(): array
    {
        return [
            'shipping_address' => '1 Main Street',
            'shipping_city' => 'Austin',
            'shipping_state' => 'TX',
            'shipping_zip' => '78701',
            'shipping_country' => 'US',
            'shipping_method' => 'standard',
            'notes' => '',
        ];
    }

    private function fakePayPalCreate(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'sandbox-access-token',
            ]),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'PAYPAL-ORDER-1',
                'status' => 'CREATED',
            ]),
        ]);
    }

    private function fakeWebhookVerification(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'sandbox-access-token',
            ]),
            'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response([
                'verification_status' => 'SUCCESS',
            ]),
        ]);
    }

    /** @return array<string, mixed> */
    private function captureCompletedEvent(Order $order): array
    {
        return [
            'id' => 'PAYPAL-EVENT-1',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource_type' => 'capture',
            'resource' => [
                'id' => 'PAYPAL-CAPTURE-1',
                'status' => 'COMPLETED',
                'amount' => [
                    'value' => number_format((float) $order->total, 2, '.', ''),
                    'currency_code' => 'USD',
                ],
                'supplementary_data' => [
                    'related_ids' => [
                        'order_id' => 'PAYPAL-ORDER-1',
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, string> */
    private function webhookHeaders(string $eventId = 'PAYPAL-EVENT-1'): array
    {
        return [
            'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            'PAYPAL-CERT-URL' => 'https://api-m.sandbox.paypal.com/certs/test',
            'PAYPAL-TRANSMISSION-ID' => 'transmission-'.$eventId,
            'PAYPAL-TRANSMISSION-SIG' => 'test-signature',
            'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String(),
        ];
    }
}
