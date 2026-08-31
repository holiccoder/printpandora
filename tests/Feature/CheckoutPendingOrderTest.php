<?php

namespace Tests\Feature;

use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CheckoutPendingOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_one_pending_order_and_reuses_it_on_refresh(): void
    {
        [$user] = $this->createCartForUser();

        $this->actingAs($user);
        $this->rememberSessionCookie($this->get(route('shop.checkout')));

        $order = Order::with('items')->firstOrFail();

        $this->assertSame('pending', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame($user->id, $order->user_id);
        $this->assertCount(1, $order->items);
        $this->assertNull($order->shipping_address);

        $this->get(route('shop.checkout'))->assertOk();

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame($order->id, Order::firstOrFail()->id);
    }

    public function test_paypal_creation_reuses_the_pending_order_from_checkout(): void
    {
        config([
            'services.paypal.mode' => 'sandbox',
            'services.paypal.client_id' => 'sandbox-client-id',
            'services.paypal.client_secret' => 'sandbox-client-secret',
            'services.paypal.currency' => 'USD',
        ]);

        [$user] = $this->createCartForUser();
        $this->actingAs($user);
        $this->rememberSessionCookie($this->get(route('shop.checkout')));

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'sandbox-access-token',
            ]),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'PAYPAL-ORDER-CHECKOUT',
                'status' => 'CREATED',
            ]),
        ]);

        $this->withCredentials()->postJson(
            route('shop.checkout.paypal.create'),
            $this->checkoutData(),
        )->assertOk()->assertJson([
            'id' => 'PAYPAL-ORDER-CHECKOUT',
        ]);

        $order = Order::firstOrFail();

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame('paypal', $order->payment_method);
        $this->assertSame('PAYPAL-ORDER-CHECKOUT', $order->paypal_order_id);
        $this->assertSame('1 Main Street', $order->shipping_address);
    }

    public function test_opening_checkout_does_not_redeem_a_discount(): void
    {
        [$user] = $this->createCartForUser();
        $discount = DiscountCode::create([
            'code' => 'checkout-draft',
            'type' => 'percent',
            'value' => 10,
        ]);
        app(Cart::class)->applyDiscountCode($discount->code);

        $this->actingAs($user)->get(route('shop.checkout'))->assertOk();

        $this->assertSame(0, $discount->fresh()->usage_count);
        $this->assertDatabaseCount('discount_redemptions', 0);
    }

    public function test_manual_checkout_reuses_the_pending_order_and_clears_the_token(): void
    {
        [$user] = $this->createCartForUser();
        $this->actingAs($user);
        $this->rememberSessionCookie($this->get(route('shop.checkout')));

        $this->post(route('shop.checkout.store'), $this->checkoutData())
            ->assertRedirect();

        $order = Order::firstOrFail();

        $this->assertDatabaseCount('orders', 1);
        $this->assertNull($order->checkout_token);
        $this->assertSame('1 Main Street', $order->shipping_address);
        $this->assertSame(0, app(Cart::class)->count());
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
            'name' => 'Checkout test products',
            'slug' => 'checkout-test-'.uniqid(),
        ]);

        return Product::create([
            'name' => 'Checkout test product',
            'slug' => 'checkout-test-product-'.uniqid(),
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

    private function rememberSessionCookie(TestResponse $response): void
    {
        $cookie = $response->getCookie(config('session.cookie'), false);

        $this->assertNotNull($cookie);
        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
    }
}
