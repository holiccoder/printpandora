<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

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
