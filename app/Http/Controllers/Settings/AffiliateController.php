<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AffiliateController extends Controller
{
    public function edit(Request $request): Response
    {
        $affiliate = Affiliate::where('user_id', $request->user()->id)->first();

        $props = [
            'isAffiliate' => $affiliate !== null,
            'affiliate' => $affiliate ? [
                'id' => $affiliate->id,
                'referral_code' => $affiliate->referral_code,
                'commission_rate' => (float) $affiliate->commission_rate,
                'status' => $affiliate->status,
                'total_earnings' => (float) $affiliate->total_earnings,
                'paid_earnings' => (float) $affiliate->paid_earnings,
                'pending_earnings' => $affiliate->pendingEarnings(),
                'referral_url' => route('referral.show', $affiliate->referral_code),
            ] : null,
            'commissions' => $affiliate
                ? $affiliate->commissions()->with('order')->latest()->limit(20)->get()
                    ->map(fn ($c) => [
                        'id' => $c->id,
                        'amount' => (float) $c->amount,
                        'rate' => (float) $c->rate,
                        'status' => $c->status,
                        'order_id' => $c->order_id,
                        'order_total' => (float) $c->order->total,
                        'created_at' => $c->created_at->toDateTimeString(),
                    ])
                : [],
            'referrals' => $affiliate
                ? $affiliate->referrals()->with('referredUser')->latest()->limit(20)->get()
                    ->map(fn ($r) => [
                        'id' => $r->id,
                        'user_name' => $r->referredUser->name,
                        'user_email' => $r->referredUser->email,
                        'status' => $r->status,
                        'created_at' => $r->created_at->toDateTimeString(),
                    ])
                : [],
            'payouts' => $affiliate
                ? $affiliate->payouts()->latest()->limit(20)->get()
                    ->map(fn ($p) => [
                        'id' => $p->id,
                        'amount' => (float) $p->amount,
                        'status' => $p->status,
                        'created_at' => $p->created_at->toDateTimeString(),
                    ])
                : [],
        ];

        return Inertia::render('settings/affiliate', $props);
    }

    public function store(Request $request): RedirectResponse
    {
        $existing = Affiliate::where('user_id', $request->user()->id)->first();
        if ($existing) {
            return back()->with('error', 'You are already an affiliate.');
        }

        $affiliate = Affiliate::create([
            'user_id' => $request->user()->id,
            'referral_code' => Affiliate::generateReferralCode(),
            'commission_rate' => 10.00,
            'status' => 'active',
        ]);

        return back()->with('success', 'You are now an affiliate! Share your referral link to start earning.');
    }

    public function requestPayout(Request $request): RedirectResponse
    {
        $affiliate = Affiliate::where('user_id', $request->user()->id)->firstOrFail();

        $pending = $affiliate->pendingEarnings();

        if ($pending < 10) {
            return back()->with('error', 'You need at least $10.00 in earnings to request a payout.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:10|max:'.$pending,
            'payment_method' => 'required|string|in:paypal,bank_transfer',
            'payment_details' => 'required|string|max:255',
        ]);

        $affiliate->payouts()->create([
            'amount' => $validated['amount'],
            'status' => 'pending',
            'payment_method' => $validated['payment_method'],
            'payment_details' => $validated['payment_details'],
        ]);

        return back()->with('success', 'Payout request submitted successfully.');
    }
}
