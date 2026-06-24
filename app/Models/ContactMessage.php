<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'email',
        'subject',
        'topic',
        'message',
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
