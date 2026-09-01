<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Contracts\User as SocialiteContractUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google', 'facebook'];

    private const LINK_INTENT_SESSION_KEY = 'social_auth_linking';

    /**
     * Redirect a visitor to the selected OAuth provider.
     */
    public function redirect(string $provider): SymfonyRedirectResponse
    {
        $this->validateProvider($provider);

        if (! $this->providerIsConfigured($provider)) {
            return $this->loginError($this->providerLabel($provider).' sign-in is not configured yet.');
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Complete a visitor's OAuth sign-in.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        if (! $this->providerIsConfigured($provider)) {
            return $this->loginError($this->providerLabel($provider).' sign-in is not configured yet.');
        }

        if ($this->isLinkCallback($request)) {
            return $this->completeLink($request, $provider);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
            $providerId = trim((string) $socialUser->getId());

            if ($providerId === '') {
                return $this->loginError('The '.$this->providerLabel($provider).' account did not provide a usable identity.');
            }

            $account = SocialAccount::query()
                ->where('provider', $provider)
                ->where('provider_id', $providerId)
                ->with('user')
                ->first();

            if ($account !== null) {
                $accountUser = $account->user;

                if ($accountUser === null) {
                    return $this->loginError('The connected account is no longer available.');
                }

                $this->loginUser($request, $accountUser);

                return redirect()->intended(route('dashboard', absolute: false));
            }

            $email = $this->normaliseEmail($socialUser->getEmail());

            if ($email === null) {
                return $this->loginError(
                    'We could not get an email address from your '.$provider.' account. Please use email sign-in instead.'
                );
            }

            $existingUser = $this->findUserByEmail($email);

            if ($existingUser !== null) {
                if (! $this->socialEmailIsVerified($socialUser)) {
                    return $this->loginError(
                        'An account already exists for this email. Sign in with your password, then connect '.$this->providerLabel($provider).' from Profile settings.'
                    );
                }

                DB::transaction(function () use ($existingUser, $provider, $providerId, $email): void {
                    $existingUser->socialAccounts()->create([
                        'provider' => $provider,
                        'provider_id' => $providerId,
                        'provider_email' => $email,
                    ]);

                    if ($existingUser->email_verified_at === null) {
                        $existingUser->forceFill([
                            'email_verified_at' => now(),
                        ])->save();
                    }
                });

                $this->loginUser($request, $existingUser);

                return redirect()->intended(route('dashboard', absolute: false));
            }

            $user = DB::transaction(function () use ($email, $provider, $providerId, $socialUser): User {
                $user = User::create([
                    'name' => $this->socialName($socialUser),
                    'email' => $email,
                    'password' => Str::random(64),
                ]);

                if ($this->socialEmailIsVerified($socialUser)) {
                    $user->forceFill(['email_verified_at' => now()])->save();
                }

                $user->socialAccounts()->create([
                    'provider' => $provider,
                    'provider_id' => $providerId,
                    'provider_email' => $email,
                ]);

                // Automatically enroll in the affiliate program
                \App\Models\Affiliate::create([
                    'user_id' => $user->id,
                    'referral_code' => \App\Models\Affiliate::generateReferralCode(),
                    'commission_rate' => 10.00,
                    'status' => 'active',
                ]);

                $refCode = request()->cookie('affiliate_ref');
                if ($refCode) {
                    $affiliate = \App\Models\Affiliate::where('referral_code', $refCode)->where('status', 'active')->first();
                    if ($affiliate) {
                        \App\Models\AffiliateReferral::create([
                            'affiliate_id' => $affiliate->id,
                            'referred_user_id' => $user->id,
                            'status' => 'registered',
                        ]);
                    }
                }

                return $user;
            });

            $this->loginUser($request, $user);

            return redirect()->intended(route('dashboard', absolute: false));
        } catch (Throwable $exception) {
            $this->logProviderFailure($provider, $exception);

            return $this->loginError(
                'We could not complete '.$provider.' sign-in. Please try again or use email sign-in.'
            );
        }
    }

    /**
     * Redirect an authenticated user to connect an OAuth provider.
     */
    public function linkRedirect(Request $request, string $provider): SymfonyRedirectResponse
    {
        $this->validateProvider($provider);

        if (! $this->providerIsConfigured($provider)) {
            return $this->profileError($this->providerLabel($provider).' sign-in is not configured yet.');
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return $this->loginError('Only customer accounts can connect social providers.');
        }

        $request->session()->put(self::LINK_INTENT_SESSION_KEY, [
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Attach an OAuth identity to the currently authenticated user.
     */
    private function completeLink(Request $request, string $provider): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
            $providerId = trim((string) $socialUser->getId());
            $user = $request->user();

            if (! $user instanceof User) {
                return $this->profileError('Only customer accounts can connect social providers.');
            }

            if ($providerId === '') {
                return $this->profileError('The '.$this->providerLabel($provider).' account did not provide a usable identity.');
            }

            $account = SocialAccount::query()
                ->where('provider', $provider)
                ->where('provider_id', $providerId)
                ->first();

            if ($account !== null && $account->user_id !== $user->id) {
                return $this->profileError('That '.$this->providerLabel($provider).' account is already connected to another user.');
            }

            $email = $this->normaliseEmail($socialUser->getEmail());

            if ($account === null) {
                $user->socialAccounts()->create([
                    'provider' => $provider,
                    'provider_id' => $providerId,
                    'provider_email' => $email,
                ]);
            } else {
                $account->update(['provider_email' => $email]);
            }

            return to_route('profile.edit')->with(
                'social_status',
                $this->providerLabel($provider).' account connected successfully.'
            );
        } catch (Throwable $exception) {
            $this->logProviderFailure($provider, $exception);

            return $this->profileError(
                'We could not connect '.$provider.'. Please try again.'
            );
        }
    }

    private function isLinkCallback(Request $request): bool
    {
        $intent = $request->session()->pull(self::LINK_INTENT_SESSION_KEY);

        if (! is_array($intent)) {
            return false;
        }

        $userId = filter_var($intent['user_id'] ?? null, FILTER_VALIDATE_INT);
        $expiresAt = filter_var($intent['expires_at'] ?? null, FILTER_VALIDATE_INT);
        $user = $request->user();

        return $user instanceof User
            && $userId !== false
            && $expiresAt !== false
            && $expiresAt >= now()->timestamp
            && $user->id === $userId;
    }

    private function validateProvider(string $provider): void
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);
    }

    private function providerIsConfigured(string $provider): bool
    {
        $config = config('services.'.$provider);

        return is_array($config)
            && filled($config['client_id'] ?? null)
            && filled($config['client_secret'] ?? null)
            && filled($config['redirect'] ?? null);
    }

    private function normaliseEmail(?string $email): ?string
    {
        $email = Str::lower(trim((string) $email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function findUserByEmail(string $email): ?User
    {
        return User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
    }

    private function providerLabel(string $provider): string
    {
        return Str::headline($provider);
    }

    private function socialName(SocialiteContractUser $socialUser): string
    {
        $name = trim((string) ($socialUser->getName() ?: $socialUser->getNickname()));

        return $name !== '' ? $name : 'InkPavo customer';
    }

    private function socialEmailIsVerified(SocialiteContractUser $socialUser): bool
    {
        if (! $socialUser instanceof AbstractUser) {
            return false;
        }

        $raw = $socialUser->getRaw();

        return filter_var(
            $raw['email_verified'] ?? $raw['verified_email'] ?? false,
            FILTER_VALIDATE_BOOL,
        );
    }

    private function loginUser(Request $request, User $user): void
    {
        Auth::login($user, remember: true);
        $request->session()->regenerate();
    }

    private function loginError(string $message): RedirectResponse
    {
        return redirect()->route('login')->with('error', $message);
    }

    private function profileError(string $message): RedirectResponse
    {
        return to_route('profile.edit')->with('error', $message);
    }

    private function logProviderFailure(string $provider, Throwable $exception): void
    {
        Log::warning('Social authentication failed.', [
            'provider' => $provider,
            'exception' => $exception::class,
        ]);
    }
}
