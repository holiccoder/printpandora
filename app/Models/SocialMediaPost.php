<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialMediaPost extends Model
{
    protected $fillable = [
        'title',
        'content',
        'platform_contents',
        'platforms',
        'scheduled_at',
        'status',
        'media_urls',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'platforms' => 'array',
            'platform_contents' => 'array',
            'media_urls' => 'array',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }
}
