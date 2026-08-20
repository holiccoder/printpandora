<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiChatConversation extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'mode',
        'human_requested_at',
    ];

    protected function casts(): array
    {
        return [
            'human_requested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(AiChatMessage::class, 'conversation_id')
            ->latestOfMany('created_at');
    }
}
