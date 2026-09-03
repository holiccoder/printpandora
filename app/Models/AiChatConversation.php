<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $session_id
 * @property int|null $user_id
 * @property string $mode
 * @property Carbon|null $human_requested_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<AiChatMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class, 'conversation_id');
    }

    /** @return HasMany<AiChatChannel, $this> */
    public function channels(): HasMany
    {
        return $this->hasMany(AiChatChannel::class, 'conversation_id');
    }

    /** @return HasMany<AiChatTelegramMessage, $this> */
    public function telegramMessages(): HasMany
    {
        return $this->hasMany(AiChatTelegramMessage::class, 'conversation_id');
    }

    /** @return HasManyThrough<AiChatWecomMessage, AiChatChannel, $this> */
    public function wecomMessages(): HasManyThrough
    {
        return $this->hasManyThrough(
            AiChatWecomMessage::class,
            AiChatChannel::class,
            'conversation_id',
            'channel_id',
            'id',
            'id',
        );
    }

    /** @return HasMany<AiChatWecomAppMessage, $this> */
    public function wecomAppMessages(): HasMany
    {
        return $this->hasMany(AiChatWecomAppMessage::class, 'conversation_id');
    }

    /** @return HasOne<AiChatMessage, $this> */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(AiChatMessage::class, 'conversation_id')
            ->latestOfMany('created_at');
    }
}
