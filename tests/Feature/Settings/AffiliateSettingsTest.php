<?php

namespace Tests\Feature\Settings;

use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_payout_requests_only_accept_paypal(): void
    {
        $user = User::factory()->create();
        Affiliate::create([
            'user_id' => $user->id,
            'referral_code' => 'PAYPALTEST',
            'commission_rate' => 10,
            'status' => 'active',
            'total_earnings' => 25,
            'paid_earnings' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('affiliate.payout.request'), [
                'amount' => 10,
                'payment_method' => 'bank_transfer',
                'payment_details' => 'bank details',
            ])
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('affiliate_payouts', 0);

        $this->actingAs($user)
            ->post(route('affiliate.payout.request'), [
                'amount' => 10,
                'payment_method' => 'paypal',
                'payment_details' => 'affiliate@example.com',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('affiliate_payouts', [
            'amount' => 10,
            'payment_method' => 'paypal',
        ]);
    }
}
