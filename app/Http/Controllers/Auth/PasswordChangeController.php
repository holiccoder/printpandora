<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordChangeController extends Controller
{
    /**
     * Show the password form reached from an account password change link.
     */
    public function create(Request $request, string $token): Response
    {
        return Inertia::render('auth/change-password', [
            'email' => $request->string('email')->toString(),
            'token' => $token,
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }
}
