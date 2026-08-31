<?php

namespace App\Services;

use App\Contracts\SendsChatReplies;
use App\Models\AiChatChannel;
use App\Models\AiChatConversation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class TelegramBotService implements SendsChatReplies
{
    public const PROVIDER = 'telegram';

    /**
     * Create a short-lived deep link that can connect a Telegram chat to a
     * conversation already known by the website.
     *
     * @return array{url: string, expires_at: string}
     */
    public function createConversationLink(AiChatConversation $conversation): array
    {
        $botUsername = ltrim((string) config('services.telegram.bot_username'), '@');

        if ($botUsername === '') {
            throw new RuntimeException('Telegram bot username is not configured.');
        }

        $token = Str::random(48);
        $expiresAt = now()->addMinutes(max(1, (int) config('aichat.telegram_link_ttl', 30)));
        $channel = $conversation->channels()->firstOrNew([
            'provider' => self::PROVIDER,
        ]);

        $channel->fill([
            'link_token_hash' => hash('sha256', $token),
            'link_token_expires_at' => $expiresAt,
        ]);
        $channel->save();

        return [
            'url' => "https://t.me/{$botUsername}?start={$token}",
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    public function channelForChat(string $chatId): ?AiChatChannel
    {
        return AiChatChannel::query()
            ->where('provider', self::PROVIDER)
            ->where('external_chat_id', $chatId)
            ->first();
    }

    public function linkConversation(
        string $token,
        string $chatId,
        ?string $userId = null,
        ?string $username = null,
    ): ?AiChatChannel {
        $channel = AiChatChannel::query()
            ->where('provider', self::PROVIDER)
            ->where('link_token_hash', hash('sha256', $token))
            ->where('link_token_expires_at', '>', now())
            ->first();

        if (! $channel) {
            return null;
        }

        $alreadyLinked = AiChatChannel::query()
            ->where('provider', self::PROVIDER)
            ->where('external_chat_id', $chatId)
            ->whereKeyNot($channel->id)
            ->exists();

        if ($alreadyLinked) {
            return null;
        }

        $channel->fill([
            'external_chat_id' => $chatId,
            'external_user_id' => $userId,
            'external_username' => $username,
            'link_token_hash' => null,
            'link_token_expires_at' => null,
        ]);
        $channel->save();

        return $channel->refresh();
    }

    /**
     * Send a message when the conversation has a Telegram channel attached.
     * A null result means this is a website-only conversation.
     *
     * @return array<string, mixed>|null
     */
    public function sendToConversation(AiChatConversation $conversation, string $text): ?array
    {
        $channel = $conversation->channels()
            ->where('provider', self::PROVIDER)
            ->whereNotNull('external_chat_id')
            ->first();

        if (! $channel || $channel->external_chat_id === null) {
            return null;
        }

        return $this->sendMessage($channel->external_chat_id, $text);
    }

    /**
     * @return array<string, mixed>
     */
    public function sendMessage(string $chatId, string $text): array
    {
        $token = (string) config('services.telegram.bot_token');

        if ($token === '') {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        $response = Http::asJson()
            ->timeout((int) config('services.telegram.timeout', 10))
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
            ]);

        if (! $response->successful() || $response->json('ok') !== true) {
            $description = (string) ($response->json('description') ?? 'Unknown Telegram API error.');

            throw new RuntimeException("Telegram sendMessage failed: {$description}");
        }

        $result = $response->json('result');

        return is_array($result) ? $result : [];
    }
}
