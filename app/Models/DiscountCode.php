<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DiscountCode extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'minimum_subtotal',
        'is_active',
        'starts_at',
        'ends_at',
        'max_uses',
        'max_uses_per_customer',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'minimum_subtotal' => 'decimal:2',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'max_uses' => 'integer',
            'max_uses_per_customer' => 'integer',
            'usage_count' => 'integer',
        ];
    }

    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = Str::upper(trim($value));
    }

    /** @return HasMany<DiscountRedemption, $this> */
    public function redemptions(): HasMany
    {
        return $this->hasMany(DiscountRedemption::class);
    }
}
