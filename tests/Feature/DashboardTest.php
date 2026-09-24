<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_profile_can_update_the_shipping_address(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('dashboard.profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'shipping_address' => '1 Main Street',
                'shipping_city' => 'Austin',
                'shipping_state' => 'TX',
                'shipping_zip' => '78701',
                'shipping_country' => 'US',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard.profile'));

        $this->assertSame('1 Main Street', $user->refresh()->shipping_address);
        $this->assertSame('US', $user->shipping_country);
    }
}
