<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Models\User;
use App\Notifications\PasswordChangeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class SecurityController extends Controller
{
    /**
     * Show the user's security settings page.
     */
    public function edit(TwoFactorAuthenticationRequest $request): Response
    {
        $props = [
            'canManageTwoFactor' => Features::canManageTwoFactorAuthentication(),
            'status' => $request->session()->get('status'),
        ];

        if (Features::canManageTwoFactorAuthentication()) {
            $request->ensureStateIsValid();

            $props['twoFactorEnabled'] = $request->user()->hasEnabledTwoFactorAuthentication();
            $props['requiresConfirmation'] = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        return Inertia::render('settings/security', $props);
    }

    /**
     * Send a password change link to the authenticated user's email address.
     */
    public function requestPasswordReset(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $status = Password::broker(config('fortify.passwords'))->sendResetLink(
            ['email' => $user->email],
            function (User $user, string $token): string {
                $user->notify(new PasswordChangeNotification($token));

                return Password::RESET_LINK_SENT;
            },
        );

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withErrors(['password' => __($status)]);
        }

        return back()->with('status', 'password-reset-link-sent');
    }
}
