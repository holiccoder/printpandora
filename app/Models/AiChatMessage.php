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
 * @property string|null $translated_content
 * @property string|null $translation_target
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
        'translated_content',
        'translation_target',
        'attachment_path',
        'attachment_name',
    ];

    public const TRANSLATION_LABEL = 'AI translated';

    public function contentForAdmin(): string
    {
        return $this->isTranslatedForAdmin()
            ? (string) $this->translated_content
            : (string) $this->content;
    }

    public function contentForCustomer(): string
    {
        return $this->isTranslatedForCustomer()
            ? (string) $this->translated_content
            : (string) $this->content;
    }

    public function isTranslatedForAdmin(): bool
    {
        return $this->role === 'user' && $this->hasTranslation();
    }

    public function isTranslatedForCustomer(): bool
    {
        return $this->role === 'admin' && $this->hasTranslation();
    }

    public function hasTranslation(): bool
    {
        return trim((string) $this->translated_content) !== '';
    }

    public function translationLabelForAdmin(): ?string
    {
        return $this->isTranslatedForAdmin() ? self::TRANSLATION_LABEL : null;
    }

    public function translationLabelForCustomer(): ?string
    {
        return $this->isTranslatedForCustomer() ? self::TRANSLATION_LABEL : null;
    }

    public function getAdminContentAttribute(): string
    {
        return $this->contentForAdmin();
    }

    public function getCustomerContentAttribute(): string
    {
        return $this->contentForCustomer();
    }

    public function getAdminTranslationLabelAttribute(): ?string
    {
        return $this->translationLabelForAdmin();
    }

    public function getCustomerTranslationLabelAttribute(): ?string
    {
        return $this->translationLabelForCustomer();
    }

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
