<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Maps a website conversation message to the WeCom self-built application
 * used by support staff.
 *
 * @property int $id
 * @property int $conversation_id
 * @property int|null $ai_chat_message_id
 * @property string|null $wecom_userid
 * @property string|null $msgid
 * @property string $direction
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AiChatWecomAppMessage extends Model
{
    public const NOTIFICATION = 'notification';

    public const OPERATOR_REPLY = 'operator_reply';

    protected $fillable = [
        'conversation_id',
        'ai_chat_message_id',
        'wecom_userid',
        'msgid',
        'direction',
    ];

    protected function casts(): array
    {
        return [
            'ai_chat_message_id' => 'integer',
        ];
    }

    /** @return BelongsTo<AiChatConversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiChatConversation::class, 'conversation_id');
    }

    /** @return BelongsTo<AiChatMessage, $this> */
    public function chatMessage(): BelongsTo
    {
        return $this->belongsTo(AiChatMessage::class, 'ai_chat_message_id');
    }
}
