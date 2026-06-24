<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;

class ReferralController extends Controller
{
    public function show(string $code)
    {
        $affiliate = Affiliate::where('referral_code', $code)->where('status', 'active')->first();

        if (! $affiliate) {
            return redirect()->route('register')->with('error', 'Invalid referral link.');
        }

        return redirect()->route('register')
            ->withCookie(cookie()->forever('affiliate_ref', $code));
    }
}
