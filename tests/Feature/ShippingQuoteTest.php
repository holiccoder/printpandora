<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipping_quote_uses_the_server_shipping_service(): void
    {
        $response = $this->postJson('/api/shipping/quote', [
            'country' => 'us',
            'product_type' => 'postcards',
            'quantity' => 1,
        ]);

        $response->assertOk()
            ->assertJsonPath('country', 'US')
            ->assertJsonPath('product_type', 'postcards')
            ->assertJsonPath('quantity', 1)
            ->assertJsonPath('shipping_weight_grams', 255)
            ->assertJsonPath('methods.0.code', 'standard')
            ->assertJsonPath('methods.0.estimated_delivery', '7 - 12 business days')
            ->assertJsonPath('methods.1.code', 'dhl_express')
            ->assertJsonPath('methods.1.estimated_delivery', '2 - 5 business days');
    }

    public function test_shipping_quote_uses_standard_and_express_weight_tiers(): void
    {
        $this->postJson('/api/shipping/quote', [
            'country' => 'US',
            'product_type' => 'postcards',
            'quantity' => 1,
        ])->assertOk()
            ->assertJsonPath('methods.0.fee', 21.14)
            ->assertJsonPath('methods.1.fee', 30.12);
    }

    public function test_shipping_quote_changes_with_quantity(): void
    {
        $small = $this->postJson('/api/shipping/quote', [
            'country' => 'US',
            'product_type' => 'postcards',
            'quantity' => 1,
        ])->assertOk();

        $large = $this->postJson('/api/shipping/quote', [
            'country' => 'US',
            'product_type' => 'postcards',
            'quantity' => 100,
        ])->assertOk();

        $this->assertNotSame(
            $small->json('shipping_weight_grams'),
            $large->json('shipping_weight_grams'),
        );
        $this->assertNotSame(
            $small->json('methods.0.fee'),
            $large->json('methods.0.fee'),
        );
    }

    public function test_shipping_quote_rejects_unknown_product_types(): void
    {
        $this->postJson('/api/shipping/quote', [
            'country' => 'US',
            'product_type' => 'unknown',
            'quantity' => 100,
        ])->assertUnprocessable();
    }
}
