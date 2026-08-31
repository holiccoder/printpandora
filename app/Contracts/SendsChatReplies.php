<?php

namespace App\Contracts;

use App\Models\AiChatConversation;

interface SendsChatReplies
{
    /**
     * Send a reply when this provider has a channel attached to the
     * conversation. A null result means the conversation is not connected to
     * this provider.
     *
     * @return array<string, mixed>|null
     */
    public function sendToConversation(AiChatConversation $conversation, string $text): ?array;
}
