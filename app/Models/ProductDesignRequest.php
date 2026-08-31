<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDesignRequest extends Model
{
    protected $fillable = [
        'desgin',
    ];

    protected function casts(): array
    {
        return [
            'desgin' => 'array',
        ];
    }
}
