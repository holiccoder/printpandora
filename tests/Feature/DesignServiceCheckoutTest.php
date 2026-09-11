<?php

namespace Tests\Feature;

use App\Models\DesignServiceRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DesignServiceCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_design_service_submission_becomes_a_checkout_order_item(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'email' => 'design-client@example.com',
        ]);

        $this->post(route('business-card-design-service.store'), [
            'email' => $user->email,
            'business_name' => 'Design Client',
            'business_card_type' => 'Classic Standard Business Cards',
            'design_service_code' => 'card_design',
            'return_to' => '/checkout',
            'terms_accepted' => true,
            'logo_file' => UploadedFile::fake()->image('logo.png'),
            'example_files' => [
                UploadedFile::fake()->image('example-one.png'),
                UploadedFile::fake()->create('example-two.pdf', 1, 'application/pdf'),
            ],
        ])->assertRedirect('/checkout');

        $designRequest = DesignServiceRequest::query()->firstOrFail();
        $cart = app(Cart::class)->all();
        $this->assertCount(1, $cart);

        $cartItem = array_values($cart)[0];
        $this->assertSame('Design Service', $cartItem['name']);
        $this->assertSame(79.0, $cartItem['price']);
        $this->assertSame('card_design', $cartItem['options']['design_service']);
        $this->assertSame(
            $designRequest->getKey(),
            $cartItem['options']['design_service_request_id'],
        );
        $this->assertSame(
            [$designRequest->getKey()],
            session('pending_design_service_request_ids'),
        );

        $checkoutResponse = $this->actingAs($user)
            ->get(route('shop.checkout'));
        $checkoutResponse->assertOk();
        $this->rememberSessionCookie($checkoutResponse);

        $pendingOrder = Order::with('items.product')->firstOrFail();
        $this->assertCount(1, $pendingOrder->items);
        $this->assertSame('Design Service', $pendingOrder->items[0]->product->name);
        $this->assertSame('79.00', $pendingOrder->items[0]->unit_price);
        $this->assertSame($pendingOrder->id, $designRequest->fresh()->order_id);

        $this->post(route('shop.checkout.store'), $this->checkoutData())
            ->assertRedirect(route('shop.orders.show', $pendingOrder->id));

        $order = $pendingOrder->fresh('items.product');
        $this->assertNotNull($order);
        $this->assertNull($order->checkout_token);
        $this->assertSame('Design Service', $order->items[0]->product->name);
        $this->assertSame('79.00', $order->items[0]->unit_price);
        $this->assertSame($order->id, $designRequest->fresh()->order_id);
        $this->assertSame(0, app(Cart::class)->count());
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_order_shows_and_downloads_design_service_attachments_for_owner_only(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->post(route('business-card-design-service.store'), [
            'email' => $user->email,
            'business_name' => 'Attachment Client',
            'business_card_type' => 'Classic Standard Business Cards',
            'design_service_code' => 'card_layout',
            'return_to' => '/checkout',
            'terms_accepted' => true,
            'logo_file' => UploadedFile::fake()->image('logo.png'),
            'example_files' => [
                UploadedFile::fake()->create('example.pdf', 1, 'application/pdf'),
            ],
        ])->assertRedirect('/checkout');

        $this->post(route('shop.checkout.store'), $this->checkoutData())
            ->assertRedirect();

        $order = Order::query()->firstOrFail();
        $designRequest = DesignServiceRequest::query()->firstOrFail();

        $this->get(route('shop.orders.show', $order->id))
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('shop/orders/show')
                ->where('order.design_service_attachments.0.label', 'Logo')
                ->where('order.design_service_attachments.1.label', 'Example 1')
                ->has('order.design_service_attachments', 2));

        $this->get(route('shop.orders.design-service-attachment', [
            'id' => $order->id,
            'designServiceRequest' => $designRequest->id,
            'attachment' => 'logo',
        ]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=design-service-logo.png');

        $this->get(route('shop.orders.design-service-attachment', [
            'id' => $order->id,
            'designServiceRequest' => $designRequest->id,
            'attachment' => 'example-0',
        ]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=design-service-example-0.pdf');

        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->get(route('shop.orders.design-service-attachment', [
                'id' => $order->id,
                'designServiceRequest' => $designRequest->id,
                'attachment' => 'logo',
            ]))
            ->assertNotFound();
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
