<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChatChannel extends Model
{
    protected $fillable = [
        'provider',
        'external_chat_id',
        'external_user_id',
        'external_username',
        'link_token_hash',
        'link_token_expires_at',
        'last_update_id',
    ];

    protected function casts(): array
    {
        return [
            'link_token_expires_at' => 'datetime',
            'last_update_id' => 'integer',
        ];
    }

    /** @return BelongsTo<AiChatConversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiChatConversation::class, 'conversation_id');
    }
}
