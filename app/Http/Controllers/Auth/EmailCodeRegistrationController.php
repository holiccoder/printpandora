<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmailCodeRegistrationController extends Controller
{
    private const SESSION_KEY = 'registration_email_verification';

    public function sendCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
        ]);
        $email = Str::lower(trim($data['email']));
        $code = (string) random_int(100000, 999999);

        $request->session()->put(self::SESSION_KEY, [
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10)->timestamp,
            'attempts' => 0,
        ]);

        try {
            Mail::raw(
                "Your InkPavo verification code is {$code}. It expires in 10 minutes.",
                fn ($message) => $message
                    ->to($email)
                    ->subject('Your InkPavo verification code'),
            );
        } catch (Throwable $exception) {
            report($exception);
            $request->session()->forget(self::SESSION_KEY);

            return back()->withErrors([
                'email' => 'We could not send a verification code right now. Please try again.',
            ]);
        }

        return back()->with('status', 'A verification code was sent to your email address.');
    }

    public function register(Request $request, CreateNewUser $creator): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'email_code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $challenge = $request->session()->get(self::SESSION_KEY);
        $email = Str::lower(trim($data['email']));

        if (! is_array($challenge)
            || ($challenge['email'] ?? null) !== $email
            || (int) ($challenge['expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget(self::SESSION_KEY);

            throw ValidationException::withMessages([
                'email_code' => 'Request a new verification code for this email address.',
            ]);
        }

        if (! Hash::check($data['email_code'], (string) ($challenge['code_hash'] ?? ''))) {
            $attempts = (int) ($challenge['attempts'] ?? 0) + 1;

            if ($attempts >= 5) {
                $request->session()->forget(self::SESSION_KEY);
            } else {
                $challenge['attempts'] = $attempts;
                $request->session()->put(self::SESSION_KEY, $challenge);
            }

            throw ValidationException::withMessages([
                'email_code' => $attempts >= 5
                    ? 'Too many incorrect codes. Request a new verification code.'
                    : 'The verification code is incorrect.',
            ]);
        }

        $user = $creator->create([
            'name' => $data['name'],
            'email' => $email,
            'password' => $data['password'],
            'password_confirmation' => $data['password_confirmation'],
        ]);

        $user->markEmailAsVerified();
        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->intended(config('fortify.home'));
    }
}
