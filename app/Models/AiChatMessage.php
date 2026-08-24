<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $conversation_id
 * @property string $role
 * @property string $content
 * @property string|null $attachment_path
 * @property string|null $attachment_name
 * @property Carbon|null $created_at
 */
class AiChatMessage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'attachment_path',
        'attachment_name',
    ];

    /**
     * Public URL of the uploaded attachment, if any.
     */
    public function attachmentUrl(): ?string
    {
        return $this->attachment_path
            ? Storage::disk('public')->url($this->attachment_path)
            : null;
    }

    /** @return BelongsTo<AiChatConversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiChatConversation::class, 'conversation_id');
    }

    /** @return HasMany<AiChatTelegramMessage, $this> */
    public function telegramMessages(): HasMany
    {
        return $this->hasMany(AiChatTelegramMessage::class, 'ai_chat_message_id');
    }
}
