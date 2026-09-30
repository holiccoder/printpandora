<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFileAudit extends Model
{
    protected $fillable = [
        'order_id',
        'order_file_id',
        'actor_type',
        'actor_id',
        'action',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<OrderFile, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(OrderFile::class, 'order_file_id');
    }
}
