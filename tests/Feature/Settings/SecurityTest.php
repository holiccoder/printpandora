<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Notifications\PasswordChangeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_page_is_displayed()
    {
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/security')
                ->where('canManageTwoFactor', true)
                ->where('twoFactorEnabled', false),
            );
    }

    public function test_security_page_requires_password_confirmation_when_enabled()
    {
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        $user = User::factory()->create();

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('security.edit'));

        $response->assertRedirect(route('password.confirm'));
    }

    public function test_security_page_renders_without_two_factor_when_feature_is_disabled()
    {
        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        config(['fortify.features' => []]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/security')
                ->where('canManageTwoFactor', false)
                ->missing('twoFactorEnabled')
                ->missing('requiresConfirmation'),
            );
    }

    public function test_password_change_link_can_be_requested()
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('security.edit'))
            ->post(route('user-password.request'));

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'password-reset-link-sent')
            ->assertRedirect(route('security.edit'));

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
        Notification::assertSentTo($user, PasswordChangeNotification::class);
    }

    public function test_password_change_link_opens_a_new_password_page_and_updates_the_password()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('user-password.request'))
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, PasswordChangeNotification::class, function ($notification) use ($user) {
            $this->assertStringContainsString(
                route('password.change', [
                    'token' => $notification->token,
                    'email' => $user->email,
                ], false),
                $notification->toMail($user)->actionUrl,
            );

            $this->actingAs($user)
                ->get(route('password.change', [
                    'token' => $notification->token,
                    'email' => $user->email,
                ]))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('auth/change-password')
                    ->where('email', $user->email)
                    ->where('token', $notification->token),
                );

            $this->actingAs($user)
                ->post(route('password.change.update'), [
                    'token' => $notification->token,
                    'email' => $user->email,
                    'password' => 'newpassword123',
                    'password_confirmation' => 'newpassword123',
                ])
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });

        $this->assertTrue(Hash::check('newpassword123', $user->refresh()->password));
    }
}
