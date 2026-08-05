<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignServiceRequest extends Model
{
    /**
     * Valid design service codes mapped to their fixed fee in USD.
     * The server is the source of truth for the fee; clients only send codes.
     */
    public const DESIGN_SERVICE_FEES = [
        'card_layout' => 29.00,
        'card_design' => 79.00,
    ];

    protected $fillable = [
        'email',
        'business_name',
        'card_info',
        'business_card_type',
        'design_service_code',
        'design_service_fee',
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
            'design_service_fee' => 'decimal:2',
        ];
    }
}
