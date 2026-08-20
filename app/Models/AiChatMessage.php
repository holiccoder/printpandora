<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->attachment_path)
            : null;
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiChatConversation::class, 'conversation_id');
    }
}
