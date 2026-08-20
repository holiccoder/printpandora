<?php

namespace Tests\Feature;

use App\Ai\Agents\CustomerSupportAgent;
use App\Ai\Tools\LookupOrder;
use App\Ai\Tools\TrackShipment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request as ToolRequest;
use Tests\TestCase;

class AiChatToolsTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(User $user, array $overrides = []): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'total' => 65,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'shipping_address' => '1 Main St',
            'shipping_city' => 'Springfield',
            'shipping_zip' => '00000',
            ...$overrides,
        ]);
    }

    public function test_lookup_order_requires_sign_in(): void
    {
        $result = (new LookupOrder(null))->handle(new ToolRequest([]));

        $this->assertStringContainsString('not signed in', $result);
    }

    public function test_lookup_order_lists_recent_orders_for_the_owner(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user);

        $result = (new LookupOrder($user))->handle(new ToolRequest([]));

        $this->assertStringContainsString("Order #{$order->id}", $result);
        $this->assertStringContainsString('processing', $result);
    }

    public function test_lookup_order_never_returns_another_customers_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = $this->makeOrder($owner);

        $result = (new LookupOrder($other))->handle(new ToolRequest(['order_id' => $order->id]));

        $this->assertStringNotContainsString('Order #', $result);
        $this->assertStringContainsString('No order', $result);
    }

    public function test_track_shipment_reports_stored_tracking_data(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, [
            'status' => 'shipped',
            'tracking_number' => 'CHANNEL-100',
            'tracking_url' => 'https://tracking.example/CHANNEL-100',
            'fourpx_tracking_response' => [
                'data' => [
                    'trackingList' => [
                        ['trackingContent' => 'Shipment received', 'trackingTime' => '2026-08-01 10:00'],
                    ],
                ],
            ],
        ]);

        $result = (new TrackShipment($user))->handle(new ToolRequest(['order_id' => $order->id]));

        $this->assertStringContainsString('CHANNEL-100', $result);
        $this->assertStringContainsString('Shipment received', $result);
    }

    public function test_track_shipment_for_unshipped_order(): void
    {
        $user = User::factory()->create();
        $this->makeOrder($user);

        $result = (new TrackShipment($user))->handle(new ToolRequest([]));

        $this->assertStringContainsString('not shipped', $result);
    }

    public function test_agent_instructions_mention_tools_only_when_signed_in(): void
    {
        $guest = new CustomerSupportAgent([], []);
        $this->assertStringContainsString('NOT signed in', (string) $guest->instructions());

        $user = new User(['name' => 'Test', 'email' => 't@example.com']);
        $member = new CustomerSupportAgent([], [], $user);
        $this->assertStringContainsString('LookupOrder', (string) $member->instructions());
    }
}
