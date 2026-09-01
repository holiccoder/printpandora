<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class SocialAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google', [
            'client_id' => 'google-client-id',
            'client_secret' => 'google-client-secret',
            'redirect' => 'https://inkpavo.test/auth/google/callback',
        ]);
        config()->set('services.facebook', [
            'client_id' => 'facebook-client-id',
            'client_secret' => 'facebook-client-secret',
            'redirect' => 'https://inkpavo.test/auth/facebook/callback',
        ]);
    }

    public function test_social_redirect_sends_the_visitor_to_the_provider(): void
    {
        Socialite::fake('google');

        $response = $this->get(route('social.redirect', ['provider' => 'google']));

        $response->assertRedirect('https://socialite.fake/google/authorize');
    }

    public function test_authenticated_user_link_redirect_sets_a_short_lived_link_intent(): void
    {
        $user = User::factory()->create();
        Socialite::fake('google');

        $response = $this->actingAs($user)->get(route('profile.social.redirect', [
            'provider' => 'google',
        ]));

        $response->assertRedirect('https://socialite.fake/google/authorize');
        $response->assertSessionHas(
            'social_auth_linking',
            fn (array $intent): bool => $intent['user_id'] === $user->id
                && $intent['expires_at'] >= now()->timestamp,
        );
    }

    public function test_new_social_user_is_created_and_authenticated(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-123',
            'name' => 'Google Customer',
            'email' => 'customer@example.com',
            'email_verified' => true,
        ]));

        $response = $this->get(route('social.callback', ['provider' => 'google']));

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'google',
            'provider_id' => 'google-user-123',
            'provider_email' => 'customer@example.com',
        ]);
        $this->assertNotNull(User::where('email', 'customer@example.com')->firstOrFail()->email_verified_at);
    }

    public function test_a_linked_social_user_can_sign_in_again(): void
    {
        $user = User::factory()->create();
        $user->socialAccounts()->create([
            'provider' => 'facebook',
            'provider_id' => 'facebook-user-123',
            'provider_email' => 'customer@example.com',
        ]);

        Socialite::fake('facebook', SocialiteUser::fake([
            'id' => 'facebook-user-123',
            'email' => 'customer@example.com',
        ]));

        $response = $this->get(route('social.callback', ['provider' => 'facebook']));

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_verified_social_email_logs_into_and_connects_an_existing_email_account(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'customer@example.com']);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-456',
            'email' => 'customer@example.com',
            'email_verified' => true,
        ]));

        $response = $this->get(route('social.callback', ['provider' => 'google']));

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-user-456',
        ]);
        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_unverified_social_email_cannot_auto_connect_an_existing_email_account(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'customer@example.com']);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-457',
            'email' => 'customer@example.com',
            'email_verified' => false,
        ]));

        $response = $this->get(route('social.callback', ['provider' => 'google']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', fn (string $error): bool => str_contains($error, 'connect Google'));
        $this->assertGuest();
        $this->assertDatabaseMissing('social_accounts', [
            'provider' => 'google',
            'provider_id' => 'google-user-457',
        ]);
        $this->assertNull($user->refresh()->email_verified_at);
    }

    public function test_authenticated_user_can_connect_a_social_account(): void
    {
        $user = User::factory()->create();

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-789',
            'email' => 'customer@example.com',
        ]));

        $response = $this->actingAs($user)
            ->withSession([
                'social_auth_linking' => [
                    'user_id' => $user->id,
                    'expires_at' => now()->addMinutes(10)->timestamp,
                ],
            ])
            ->get(route('social.callback', ['provider' => 'google']));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('social_status', 'Google account connected successfully.');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-user-789',
        ]);
    }

    public function test_a_social_account_cannot_be_connected_to_two_users(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $owner->socialAccounts()->create([
            'provider' => 'facebook',
            'provider_id' => 'facebook-user-999',
        ]);

        Socialite::fake('facebook', SocialiteUser::fake([
            'id' => 'facebook-user-999',
            'email' => 'customer@example.com',
        ]));

        $response = $this->actingAs($otherUser)
            ->withSession([
                'social_auth_linking' => [
                    'user_id' => $otherUser->id,
                    'expires_at' => now()->addMinutes(10)->timestamp,
                ],
            ])
            ->get(route('social.callback', ['provider' => 'facebook']));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('error', 'That Facebook account is already connected to another user.');
        $this->assertDatabaseCount('social_accounts', 1);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $owner->id,
            'provider_id' => 'facebook-user-999',
        ]);
    }
}
