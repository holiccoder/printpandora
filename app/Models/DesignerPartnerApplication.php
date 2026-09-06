<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignerPartnerApplication extends Model
{
    protected $fillable = [
        'name_or_company',
        'country_or_region',
        'email',
        'website',
        'portfolio_links',
        'design_field',
        'expected_products_finishes',
        'status',
        'notes',
        'ip_address',
        'user_agent',
        'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }
}
