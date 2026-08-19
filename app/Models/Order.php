<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read Collection<int, OrderItem> $items
 */
class Order extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'payment_method',
        'payment_status',
        'payment_id',
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
            'shipping_weight_grams' => 'integer',
            'shipping_length_cm' => 'decimal:2',
            'shipping_width_cm' => 'decimal:2',
            'shipping_height_cm' => 'decimal:2',
            'fourpx_response' => 'array',
            'fourpx_tracking_response' => 'array',
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
     * @return HasOne<DiscountRedemption, $this>
     */
    public function discountRedemption(): HasOne
    {
        return $this->hasOne(DiscountRedemption::class);
    }
}
