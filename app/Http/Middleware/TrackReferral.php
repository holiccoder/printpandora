<?php

namespace App\Http\Middleware;

use App\Models\Affiliate;
use Closure;
use Illuminate\Http\Request;

class TrackReferral
{
    public function handle(Request $request, Closure $next)
    {
        $ref = $request->query('ref');
        if ($ref && Affiliate::where('referral_code', $ref)->where('status', 'active')->exists()) {
            cookie()->queue(cookie()->forever('affiliate_ref', $ref));
        }

        return $next($request);
    }
}
