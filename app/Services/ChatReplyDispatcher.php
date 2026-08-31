<?php

namespace App\Services;

use App\Models\AiChatConversation;

class ChatReplyDispatcher
{
    public function __construct(
        private readonly TelegramBotService $telegram,
        private readonly WeComKfService $wecom,
    ) {}

    /**
     * Send a reply through the first connected channel on the conversation.
     *
     * @return array<string, mixed>|null
     */
    public function send(AiChatConversation $conversation, string $text): ?array
    {
        $channel = $conversation->channels()
            ->whereNotNull('external_chat_id')
            ->oldest('id')
            ->first();

        if (! $channel) {
            return null;
        }

        return match ($channel->provider) {
            TelegramBotService::PROVIDER => $this->telegram->sendToConversation($conversation, $text),
            WeComKfService::PROVIDER => $this->wecom->sendToConversation($conversation, $text),
            default => null,
        };
    }
}
