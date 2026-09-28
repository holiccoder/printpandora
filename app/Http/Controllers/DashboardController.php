<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\Affiliate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\CustomerNotification;
use App\Services\DiscountService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
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
        // surfaces the most recent order's shipping address.
        $latestShippingOrder = Order::where('user_id', $user->id)
            ->whereNotNull('shipping_address')
            ->latest()
            ->first();

        $shippingAddress = $latestShippingOrder ? [
            'name' => $latestShippingOrder->customer_name,
            'phone' => $latestShippingOrder->customer_phone,
            'line' => $latestShippingOrder->shipping_address,
            'city' => $latestShippingOrder->shipping_city,
            'state' => $latestShippingOrder->shipping_state,
            'zip' => $latestShippingOrder->shipping_zip,
            'country' => $latestShippingOrder->shipping_country,
        ] : null;

        $affiliate = Affiliate::where('user_id', $user->id)->first();

        return Inertia::render('dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ],
            'recentOrders' => $recentOrders,
            'shippingAddress' => $shippingAddress,
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
     * Full orders list — filterable by status, descending by creation date, paginated.
     */
    public function orders(Request $request): Response
    {
        $status = $request->query('status');
        $status = is_string($status) && array_key_exists($status, Order::statusOptions())
            ? $status
            : null;

        $orders = Order::with('items.product')
            ->where('user_id', $request->user()->id)
            ->when($status !== null, function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'status' => $order->status,
                'tracking_number' => $order->tracking_number,
                'tracking_url' => $order->tracking_url,
                'invoice_url' => $order->payment_status === 'paid'
                    ? route('dashboard.orders.invoice', ['id' => $order->id])
                    : null,
                'total' => (float) $order->total,
                'item_count' => $order->items->sum('quantity'),
                'product_names' => $order->items
                    ->map(fn (OrderItem $item): ?string => $item->product?->name)
                    ->filter()
                    ->values()
                    ->all(),
                'notes' => $order->notes,
                'created_at' => $order->created_at?->toIso8601String(),
            ]);

        return Inertia::render('dashboard/orders', [
            'orders' => $orders,
            'statusOptions' => Order::statusOptions(),
            'selectedStatus' => $status,
        ]);
    }

    /**
     * Show the currently available discount coupons for the signed-in customer.
     */
    public function discountCoupons(Request $request, DiscountService $discounts): Response
    {
        $coupons = $discounts->availableForCustomer($request->user())
            ->map(fn ($discountCode) => [
                'code' => $discountCode->code,
                'type' => $discountCode->type,
                'value' => (float) $discountCode->value,
                'minimum_subtotal' => (float) $discountCode->minimum_subtotal,
                'starts_at' => $discountCode->starts_at?->toIso8601String(),
                'ends_at' => $discountCode->ends_at?->toIso8601String(),
                'first_order_only' => (bool) $discountCode->first_order_only,
            ])
            ->values();

        return Inertia::render('dashboard/discount-coupons', [
            'coupons' => $coupons,
        ]);
    }

    /**
     * Show the signed-in customer's system and administrator notifications.
     */
    public function notifications(Request $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->where('type', CustomerNotification::class)
            ->latest()
            ->paginate(20)
            ->through(fn (DatabaseNotification $notification): array => $this->notificationPayload($notification));

        return Inertia::render('dashboard/notifications', [
            'notifications' => $notifications,
            'unreadCount' => $request->user()
                ->unreadNotifications()
                ->where('type', CustomerNotification::class)
                ->count(),
        ]);
    }

    /**
     * Mark one of the current customer's notifications as read.
     */
    public function markNotificationRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('type', CustomerNotification::class)
            ->whereKey($id)
            ->firstOrFail();

        $notification->markAsRead();

        return back();
    }

    /**
     * Mark every system and administrator notification as read.
     */
    public function markAllNotificationsRead(Request $request): RedirectResponse
    {
        $request->user()
            ->unreadNotifications()
            ->where('type', CustomerNotification::class)
            ->update(['read_at' => now()]);

        return back();
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
                'shipping_address' => $user->shipping_address,
                'shipping_city' => $user->shipping_city,
                'shipping_state' => $user->shipping_state,
                'shipping_zip' => $user->shipping_zip,
                'shipping_country' => $user->shipping_country,
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

    /**
     * @return array<string, mixed>
     */
    private function notificationPayload(DatabaseNotification $notification): array
    {
        $data = $notification->data;
        $actionUrl = data_get($data, 'action_url');

        return [
            'id' => (string) $notification->getKey(),
            'category' => (string) data_get($data, 'category', CustomerNotification::CATEGORY_SYSTEM),
            'type' => (string) data_get($data, 'type', data_get($data, 'category', CustomerNotification::CATEGORY_SYSTEM)),
            'title' => (string) data_get($data, 'title', 'Notification'),
            'body' => (string) data_get($data, 'body', ''),
            'action_url' => is_string($actionUrl) && $actionUrl !== '' ? $actionUrl : null,
            'order_id' => data_get($data, 'order_id'),
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
