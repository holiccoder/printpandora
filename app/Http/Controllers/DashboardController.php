<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\Affiliate;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Dashboard overview — profile, recent orders, latest shipping address,
     * and affiliate snapshot. Each section is a self-contained card.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $recentOrders = Order::with('items.product')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total' => (float) $order->total,
                'item_count' => $order->items->sum('quantity'),
                'created_at' => $order->created_at?->toIso8601String(),
            ]);

        // We don't have a dedicated addresses table, so the dashboard
        // surfaces the most recent order's shipping address as the
        // user's "current" delivery address.
        $latestAddressOrder = Order::where('user_id', $user->id)
            ->whereNotNull('shipping_address')
            ->latest()
            ->first();

        $address = $latestAddressOrder ? [
            'name' => $latestAddressOrder->customer_name,
            'phone' => $latestAddressOrder->customer_phone,
            'line' => $latestAddressOrder->shipping_address,
            'city' => $latestAddressOrder->shipping_city,
            'state' => $latestAddressOrder->shipping_state,
            'zip' => $latestAddressOrder->shipping_zip,
            'country' => $latestAddressOrder->shipping_country,
        ] : null;

        $affiliate = Affiliate::where('user_id', $user->id)->first();

        return Inertia::render('dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'recentOrders' => $recentOrders,
            'address' => $address,
            'affiliate' => $affiliate ? [
                'referral_code' => $affiliate->referral_code,
                'commission_rate' => (float) $affiliate->commission_rate,
                'status' => $affiliate->status,
                'total_earnings' => (float) $affiliate->total_earnings,
                'paid_earnings' => (float) $affiliate->paid_earnings,
                'pending_earnings' => $affiliate->pendingEarnings(),
                'referral_url' => route('referral.show', $affiliate->referral_code),
            ] : null,
        ]);
    }

    /**
     * Full orders list — descending by creation date, paginated.
     */
    public function orders(Request $request): Response
    {
        $orders = Order::with('items.product')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15)
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'total' => (float) $order->total,
                'item_count' => $order->items->sum('quantity'),
                'created_at' => $order->created_at?->toIso8601String(),
            ]);

        return Inertia::render('dashboard/orders', [
            'orders' => $orders,
        ]);
    }

    /**
     * Profile edit form — same fields as the settings/profile page,
     * but rendered inside the dashboard layout.
     */
    public function profile(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('dashboard/profile', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ],
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Persist profile edits. Reuses the existing ProfileUpdateRequest so
     * name/email validation rules stay in lockstep with the settings page.
     * Password is optional — when blank, the existing hash is left alone.
     */
    public function updateProfile(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Name + email come from the shared ProfileUpdateRequest rules.
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Password is opt-in; validate only if the user actually typed one.
        // Empty submissions leave the stored hash untouched.
        if ($request->filled('password')) {
            $request->validate([
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user->password = $request->string('password')->toString();
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('dashboard.profile');
    }
}
