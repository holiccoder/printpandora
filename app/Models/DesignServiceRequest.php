<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignServiceRequest extends Model
{
    protected $fillable = [
        'email',
        'business_name',
        'card_info',
        'business_card_type',
        'terms_accepted',
        'logo_path',
        'example_paths',
        'ip_address',
        'user_agent',
        'notes',
        'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'terms_accepted' => 'boolean',
            'example_paths' => 'array',
            'handled_at' => 'datetime',
        ];
    }
}
