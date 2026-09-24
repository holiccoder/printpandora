<?php

namespace App\Models;

use App\Events\OrderPaid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property-read Collection<int, OrderItem> $items
 * @property-read Collection<int, ProductDesignRequest> $productDesignRequests
 * @property-read Collection<int, DesignServiceRequest> $designServiceRequests
 * @property-read Carbon|null $shipped_at
 */
class Order extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_PENDING_MODIFICATION = 'pending_modification';

    public const STATUS_PENDING_PRODUCTION = 'pending_production';

    public const STATUS_PRODUCTION = 'production';

    public const STATUS_PENDING_SHIPMENT = 'pending_shipment';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => '待付款',
            self::STATUS_CONFIRMED => '已确认',
            self::STATUS_PENDING_MODIFICATION => '待修改',
            self::STATUS_PENDING_PRODUCTION => '待生产',
            self::STATUS_PRODUCTION => '生产中',
            self::STATUS_PENDING_SHIPMENT => '待发货',
            self::STATUS_SHIPPED => '已发货',
            self::STATUS_CANCELLED => '已取消',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Order $order): void {
            if ($order->status === self::STATUS_SHIPPED && $order->shipped_at === null) {
                $order->shipped_at = now();
            }
        });

        static::updated(function (Order $order): void {
            if ($order->wasChanged('payment_status') && $order->payment_status === 'paid') {
                OrderPaid::dispatch($order);
            }
        });
    }

    protected $fillable = [
        'user_id',
        'checkout_token',
        'status',
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
