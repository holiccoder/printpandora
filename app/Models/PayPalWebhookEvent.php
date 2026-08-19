<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayPalWebhookEvent extends Model
{
    protected $table = 'paypal_webhook_events';

    protected $fillable = [
        'event_id',
        'event_type',
        'paypal_order_id',
        'paypal_capture_id',
        'order_id',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
