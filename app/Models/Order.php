<?php

namespace App\Models;

use App\Events\OrderPaid;
use App\Services\CustomerNotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * @property-read Collection<int, OrderItem> $items
 * @property-read Collection<int, ProductDesignRequest> $productDesignRequests
 * @property-read Collection<int, DesignServiceRequest> $designServiceRequests
 * @property-read Carbon|null $shipped_at
 */
class Order extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PENDING_PAYMENT = self::STATUS_PENDING;

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_NEEDS_REUPLOAD = 'needs_reupload';

    public const STATUS_PENDING_CONFIRMATION = 'pending_confirmation';

    /** @deprecated Existing order records are migrated directly to production. */
    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_PRODUCTION = 'production';

    public const STATUS_SHIPPED = 'shipped';

    /**
     * Backwards-compatible aliases for integrations that still reference the
     * previous workflow constants. New code should use the six statuses above.
     */
    public const STATUS_PENDING_MODIFICATION = self::STATUS_PENDING_REVIEW;

    public const STATUS_PENDING_PRODUCTION = self::STATUS_PENDING_CONFIRMATION;

    public const STATUS_PENDING_SHIPMENT = self::STATUS_PRODUCTION;

    public const STATUS_CANCELLED = self::STATUS_PENDING_REVIEW;

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => '待付款',
            self::STATUS_PENDING_REVIEW => '待审核',
            self::STATUS_NEEDS_REUPLOAD => '需重新上传文件',
            self::STATUS_PENDING_CONFIRMATION => '待确认',
            self::STATUS_PRODUCTION => '生产中',
            self::STATUS_SHIPPED => '已发货',
        ];
    }

    public static function statusLabel(string $status): string
    {
        return self::statusOptions()[$status] ?? $status;
    }

    protected static function booted(): void
    {
        static::saving(function (Order $order): void {
            if ($order->status === self::STATUS_SHIPPED && $order->shipped_at === null) {
                $order->setAttribute('shipped_at', now());
            }
        });

        static::updated(function (Order $order): void {
            if ($order->wasChanged('payment_status') && $order->payment_status === 'paid') {
                OrderPaid::dispatch($order);
            }

            $wasSubmitted = $order->wasChanged('checkout_token')
                && filled($order->getRawOriginal('checkout_token'))
                && blank($order->checkout_token);

            if ($wasSubmitted) {
                app(CustomerNotificationService::class)->orderPlaced($order);

                return;
            }

            if ($order->wasChanged('status')) {
                app(CustomerNotificationService::class)->orderStatusChanged(
                    $order,
                    $order->getRawOriginal('status'),
                );

                if (Schema::hasTable('order_file_audits')) {
                    OrderFileAudit::create([
                        'order_id' => $order->getKey(),
                        'actor_type' => 'system',
                        'action' => 'status_changed',
                        'metadata' => [
                            'from' => $order->getRawOriginal('status'),
                            'to' => $order->status,
                        ],
                    ]);
                }
            }
        });
    }

    protected $fillable = [
        'user_id',
        'checkout_token',
        'status',
        'confirmation_requested_at',
        'confirmation_reminded_at',
        'payment_method',
        'payment_status',
        'payment_id',
        'invoice_number',
        'invoice_path',
        'invoice_issued_at',
        'invoice_emailed_at',
        'paypal_order_id',
        'total',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'shipping_country',
        'shipping_method',
        'shipping_carrier',
        'shipping_fee',
        'shipped_at',
        'shipping_weight_grams',
        'shipping_length_cm',
        'shipping_width_cm',
        'shipping_height_cm',
        'tracking_number',
        'tracking_url',
        'fourpx_ref_no',
        'fourpx_consignment_no',
        'fourpx_tracking_number',
        'fourpx_logistics_channel_no',
        'fourpx_status',
        'fourpx_label_url',
        'fourpx_last_error',
        'fourpx_response',
        'fourpx_tracking_response',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'shipped_at' => 'datetime',
            'confirmation_requested_at' => 'datetime',
            'confirmation_reminded_at' => 'datetime',
            'shipping_weight_grams' => 'integer',
            'shipping_length_cm' => 'decimal:2',
            'shipping_width_cm' => 'decimal:2',
            'shipping_height_cm' => 'decimal:2',
            'fourpx_response' => 'array',
            'fourpx_tracking_response' => 'array',
            'invoice_issued_at' => 'datetime',
            'invoice_emailed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<ProductDesignRequest, $this>
     */
    public function productDesignRequests(): HasMany
    {
        return $this->hasMany(ProductDesignRequest::class);
    }

    /**
     * @return HasMany<OrderFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(OrderFile::class);
    }

    /**
     * @return HasMany<OrderFileAudit, $this>
     */
    public function fileAudits(): HasMany
    {
        return $this->hasMany(OrderFileAudit::class);
    }

    /**
     * @return HasMany<DesignServiceRequest, $this>
     */
    public function designServiceRequests(): HasMany
    {
        return $this->hasMany(DesignServiceRequest::class);
    }

    /**
     * @return HasOne<DiscountRedemption, $this>
     */
    public function discountRedemption(): HasOne
    {
        return $this->hasOne(DiscountRedemption::class);
    }
}
