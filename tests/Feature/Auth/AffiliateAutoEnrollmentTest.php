<?php

namespace Tests\Feature\Auth;

use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateAutoEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_newly_registered_users_are_automatically_enrolled_as_affiliates()
    {
        $this->post(route('register.store'), [
            'name' => 'Affiliate User',
            'email' => 'affiliate_test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();

        $user = User::where('email', 'affiliate_test@example.com')->first();
        $this->assertNotNull($user);

        $affiliate = Affiliate::where('user_id', $user->id)->first();
        $this->assertNotNull($affiliate);
        $this->assertEquals('active', $affiliate->status);
        $this->assertEquals(10.00, (float) $affiliate->commission_rate);
        $this->assertNotEmpty($affiliate->referral_code);
    }

    public function test_existing_users_are_automatically_enrolled_when_visiting_affiliate_settings()
    {
        $user = User::factory()->create();

        // Ensure user does not have an affiliate record
        $this->assertNull(Affiliate::where('user_id', $user->id)->first());

        $this->actingAs($user);

        $response = $this->get(route('affiliate.edit'));

        $response->assertOk();

        // Check that an affiliate record has been automatically created
        $affiliate = Affiliate::where('user_id', $user->id)->first();
        $this->assertNotNull($affiliate);
        $this->assertEquals('active', $affiliate->status);
        $this->assertEquals(10.00, (float) $affiliate->commission_rate);
        $this->assertNotEmpty($affiliate->referral_code);
    }
}
