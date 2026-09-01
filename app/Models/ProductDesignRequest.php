<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDesignRequest extends Model
{
    protected $fillable = [
        'order_id',
        'desgin',
    ];

    protected function casts(): array
    {
        return [
            'desgin' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
