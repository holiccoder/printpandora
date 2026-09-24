<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShowcaseCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<Showcase, $this> */
    public function showcases(): HasMany
    {
        return $this->hasMany(Showcase::class, 'category_id');
    }
}
