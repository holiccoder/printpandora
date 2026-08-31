<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Maps a sync_msg message ID to the local channel and chat message so cursor
 * retries cannot create the same customer message twice.
 *
 * @property int $id
 * @property int $channel_id
 * @property string $msgid
 * @property int|null $ai_chat_message_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AiChatWecomMessage extends Model
{
    protected $fillable = [
        'channel_id',
        'msgid',
        'ai_chat_message_id',
    ];

    protected function casts(): array
    {
        return [
            'channel_id' => 'integer',
            'ai_chat_message_id' => 'integer',
        ];
    }

    /** @return BelongsTo<AiChatChannel, $this> */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(AiChatChannel::class, 'channel_id');
    }

    /** @return BelongsTo<AiChatMessage, $this> */
    public function chatMessage(): BelongsTo
    {
        return $this->belongsTo(AiChatMessage::class, 'ai_chat_message_id');
    }
}
