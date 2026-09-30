<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\Affiliate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\CustomerNotification;
use App\Services\DiscountService;
use App\Services\OrderFileService;
use App\Services\OrderWeightService;
use App\Services\ProductImageService;
use Carbon\CarbonImmutable;
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
    public function orders(
        Request $request,
        OrderFileService $orderFiles,
        OrderWeightService $weights,
    ): Response {
        $customer = $request->user();
        $status = $request->query('status');
        $status = is_string($status) && array_key_exists($status, Order::statusOptions())
            ? $status
            : null;

        $filters = [
            'order_time_start' => $this->dateFilter($request, 'order_time_start', 'order_start_date'),
            'order_time_end' => $this->dateFilter($request, 'order_time_end', 'order_end_date'),
            'keyword' => $this->stringFilter($request->query('keyword')),
            'shipping_number' => $this->stringFilter($request->query('shipping_number')),
        ];

        $orders = Order::with([
            'items.product',
            'designServiceRequests',
            'productDesignRequests',
        ])
            ->where('user_id', $customer->id)
            ->when($status !== null, function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($filters['order_time_start'] !== '', function (Builder $query) use ($filters): void {
                $query->whereDate('created_at', '>=', $filters['order_time_start']);
            })
            ->when($filters['order_time_end'] !== '', function (Builder $query) use ($filters): void {
                $query->whereDate('created_at', '<=', $filters['order_time_end']);
            })
            ->when($filters['keyword'] !== '', function (Builder $query) use ($filters): void {
                $keyword = $filters['keyword'];
                $orderId = ltrim($keyword, '#');
                $productLike = '%'.$keyword.'%';

                $query->where(function (Builder $query) use ($orderId, $productLike): void {
                    if (ctype_digit($orderId)) {
                        $query->whereKey((int) $orderId)
                            ->orWhereHas('items.product', function (Builder $productQuery) use ($productLike): void {
                                $productQuery->where('name', 'like', $productLike);
                            });

                        return;
                    }

                    $query->whereHas('items.product', function (Builder $productQuery) use ($productLike): void {
                        $productQuery->where('name', 'like', $productLike);
                    });
                });
            })
            ->when($filters['shipping_number'] !== '', function (Builder $query) use ($filters): void {
                $shippingLike = '%'.$filters['shipping_number'].'%';

                $query->where(function (Builder $query) use ($shippingLike): void {
                    $query->where('tracking_number', 'like', $shippingLike)
                        ->orWhere('fourpx_ref_no', 'like', $shippingLike)
                        ->orWhere('fourpx_consignment_no', 'like', $shippingLike)
                        ->orWhere('fourpx_tracking_number', 'like', $shippingLike);
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(function (Order $order) use ($customer, $orderFiles, $weights): array {
                $files = $orderFiles->forOrder($order);

                return [
                    'id' => $order->id,
                    'status' => $order->status,
                    'tracking_number' => $order->tracking_number,
                    'tracking_url' => $order->tracking_url,
                    'invoice_url' => $order->payment_status === 'paid'
                        ? route('dashboard.orders.invoice', ['id' => $order->id])
                        : null,
                    'total' => (float) $order->total,
                    'item_count' => $order->items->sum('quantity'),
                    'weight' => $order->shipping_weight_grams
                        ?: $weights->wholeGrams($weights->forOrder($order)),
                    'product_names' => $order->items
                        ->map(fn (OrderItem $item): ?string => $item->product?->name)
                        ->filter()
                        ->values()
                        ->all(),
                    'products' => $order->items
                        ->map(function (OrderItem $item): ?array {
                            if (! $item->product) {
                                return null;
                            }

                            $options = is_array($item->options) ? $item->options : [];

                            return [
                                'name' => $item->product->name,
                                'options' => collect($options)
                                    ->reject(static fn (mixed $value, string|int $key): bool => $key === 'design_service_request_id'
                                        || $value === null
                                        || $value === ''
                                        || $value === [])
                                    ->all(),
                                'weight' => $item->product->weight,
                            ];
                        })
                        ->filter()
                        ->values()
                        ->all(),
                    'notes' => $order->notes,
                    'created_at' => $order->created_at?->toIso8601String(),
                    ...$files,
                    'can_manage_files' => $orderFiles->canCustomerManage($order),
                    'can_confirm_files' => $orderFiles->canCustomerConfirm($order, $customer),
                    'file_upload_url' => route('dashboard.orders.files.upload', ['id' => $order->id]),
                    'file_confirm_url' => route('dashboard.orders.files.confirm', ['id' => $order->id]),
                ];
            });

        return Inertia::render('dashboard/orders', [
            'orders' => $orders,
            'statusOptions' => Order::statusOptions(),
            'selectedStatus' => $status,
            'filters' => $filters,
        ]);
    }

    /**
     * Show one of the signed-in customer's orders inside the dashboard.
     */
    public function showOrder(
        Request $request,
        int $id,
        OrderFileService $orderFiles,
        ProductImageService $productImages,
    ): Response {
        $order = Order::with([
            'items.product',
            'discountRedemption',
            'designServiceRequests',
            'productDesignRequests',
        ])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $files = $orderFiles->forOrder($order);

        return Inertia::render('dashboard/order-show', [
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'total' => (float) $order->total,
                'created_at' => $order->created_at?->toIso8601String(),
                'notes' => $order->notes,
                'coupon_code' => $order->discountRedemption?->code,
                'items' => $order->items->map(function (OrderItem $item) use ($productImages): array {
                    $product = $item->product;

                    return [
                        'id' => $item->id,
                        'quantity' => (int) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'subtotal' => (float) $item->subtotal,
                        'options' => collect(is_array($item->options) ? $item->options : [])
                            ->reject(static fn (mixed $value, string|int $key): bool => $key === 'design_service_request_id'
                                || $value === null
                                || $value === ''
                                || $value === [])
                            ->all(),
                        'product' => $product ? [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => $product->slug,
                            'featured_image' => $productImages->featuredImageUrl($product),
                        ] : null,
                    ];
                })->values()->all(),
                'files' => $files,
                'can_manage_files' => $orderFiles->canCustomerManage($order),
                'can_confirm_files' => $orderFiles->canCustomerConfirm($order, $request->user()),
                'file_upload_url' => route('dashboard.orders.files.upload', ['id' => $order->id]),
                'file_confirm_url' => route('dashboard.orders.files.confirm', ['id' => $order->id]),
                'contact' => [
                    'name' => $order->customer_name,
                    'email' => $order->customer_email,
                    'phone' => $order->customer_phone,
                ],
                'address' => [
                    'line' => $order->shipping_address,
                    'city' => $order->shipping_city,
                    'state' => $order->shipping_state,
                    'zip' => $order->shipping_zip,
                    'country' => $order->shipping_country,
                ],
                'shipping' => [
                    'carrier' => $order->shipping_carrier,
                    'method' => $order->shipping_method,
                    'expenses' => (float) $order->shipping_fee,
                    'number' => $order->tracking_number
                        ?: $order->fourpx_tracking_number
                        ?: $order->fourpx_consignment_no
                        ?: $order->fourpx_ref_no,
                    'tracking_link' => $order->tracking_url,
                ],
                'invoice_url' => $order->payment_status === 'paid'
                    ? route('dashboard.orders.invoice', ['id' => $order->id])
                    : null,
            ],
        ]);
    }

    private function stringFilter(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function dateFilter(Request $request, string $key, string $alias): string
    {
        $value = $request->query($key, $request->query($alias));

        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return '';
        }

        return $date instanceof CarbonImmutable ? $date->format('Y-m-d') : '';
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
        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $emailChanged
                ? __('A verification link has been sent to your new email address.')
                : __('Profile updated.'),
        ]);

        $response = to_route('dashboard.profile');

        return $emailChanged
            ? $response->with('status', 'verification-link-sent')
            : $response;
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
