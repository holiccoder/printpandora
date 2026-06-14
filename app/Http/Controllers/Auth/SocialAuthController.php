<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

/**
 * Stub controller for social authentication providers (Google, Facebook).
 *
 * The login/register pages link buttons at /auth/{provider}/redirect. This
 * controller exists so those clicks don't 404 while OAuth credentials are
 * not yet configured. Once Laravel Socialite is wired up, replace the
 * redirect() calls with Socialite::driver($provider)->redirect() and
 * implement callback() to log the user in.
 */
class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google', 'facebook'];

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        return redirect()->route('login')->with(
            'status',
            'Social sign-in with '.ucfirst($provider).' is coming soon — please log in with your email for now.'
        );
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        return redirect()->route('login');
    }
}
