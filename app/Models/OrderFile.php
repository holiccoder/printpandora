<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property string|null $identifier
 * @property string $path
 * @property string $original_name
 * @property string|null $label
 * @property string $source
 * @property string $status
 * @property int $version
 * @property bool $is_current
 * @property Carbon|null $confirmed_at
 */
class OrderFile extends Model
{
    protected $fillable = [
        'order_id',
        'identifier',
        'path',
        'original_name',
        'label',
        'source',
        'status',
        'version',
        'is_current',
        'uploaded_by_user_id',
        'uploaded_by_admin_id',
        'confirmed_by_user_id',
        'confirmed_at',
        'origin_type',
        'origin_id',
        'origin_field',
        'origin_index',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_current' => 'boolean',
            'confirmed_at' => 'datetime',
            'origin_id' => 'integer',
            'origin_index' => 'integer',
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
     * @return BelongsTo<User, $this>
     */
    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function uploadedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'uploaded_by_admin_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function confirmedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }
}
