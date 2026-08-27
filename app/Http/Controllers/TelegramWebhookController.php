<?php

namespace App\Http\Controllers;

use App\Models\AiChatChannel;
use App\Models\AiChatConversation;
use App\Services\AiChatTranslationService;
use App\Services\TelegramBotService;
use App\Services\TelegramSupportBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TelegramWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        TelegramBotService $telegram,
        TelegramSupportBridge $telegramSupport,
        AiChatTranslationService $translation,
    ): JsonResponse {
        $secret = (string) config('services.telegram.webhook_secret');
        $providedSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token');

        if ($secret === '' || ! hash_equals($secret, $providedSecret)) {
            return response()->json(['message' => 'Invalid webhook secret.'], 403);
        }

        $updateId = (int) $request->input('update_id', 0);
        $message = $request->input('message');

        if ($updateId <= 0 || ! is_array($message)) {
            return response()->json(['ok' => true]);
        }

        $chat = $message['chat'] ?? null;

        if (! is_array($chat) || ! array_key_exists('id', $chat)) {
            return response()->json(['ok' => true]);
        }

        $chatId = (string) $chat['id'];

        if ($telegramSupport->isSupportChat($chatId)) {
            $telegramSupport->handleOperatorReply($updateId, $message);

            return response()->json(['ok' => true]);
        }

        $channel = $telegram->channelForChat($chatId);

        // Telegram retries webhook deliveries after failures. Ignore an
        // update already accepted for this customer chat.
        if ($channel && $channel->last_update_id !== null && $updateId <= $channel->last_update_id) {
            return response()->json(['ok' => true]);
        }

        $text = trim((string) ($message['text'] ?? $message['caption'] ?? ''));
        $from = is_array($message['from'] ?? null) ? $message['from'] : [];
        $userId = isset($from['id']) ? (string) $from['id'] : null;
        $username = isset($from['username']) ? (string) $from['username'] : null;

        if (preg_match('/^\/start(?:@\w+)?(?:\s+([A-Za-z0-9_-]{1,64}))?$/', $text, $matches) === 1) {
            $linkToken = $matches[1] ?? null;

            if ($linkToken !== null) {
                $channel = $telegram->linkConversation($linkToken, $chatId, $userId, $username);

                if (! $channel) {
                    $telegram->sendMessage(
                        $chatId,
                        'This support link is invalid or expired. Please request a new link.',
                    );

                    return response()->json(['ok' => true]);
                }

                $this->markUpdate($channel, $updateId, $userId, $username);
                $channel->conversation->touch();
                $telegram->sendMessage(
                    $chatId,
                    'This Telegram chat is now connected to your support conversation.',
                );

                return response()->json(['ok' => true]);
            }

            if (! $channel) {
                $channel = $this->createConversation($chatId, $userId, $username);
            }

            $this->markUpdate($channel, $updateId, $userId, $username);
            $channel->conversation->touch();
            $telegram->sendMessage(
                $chatId,
                'Thanks for contacting support. A team member will reply here shortly.',
            );

            return response()->json(['ok' => true]);
        }

        if (! $channel) {
            $channel = $this->createConversation($chatId, $userId, $username);
        }

        $conversation = $channel->conversation;
        $content = $text !== '' ? $text : '[Telegram attachment received]';
        $conversation->messages()->create([
            'role' => 'user',
            'content' => $content,
            ...($text !== '' ? $translation->attributesFor('user', $content) : []),
        ]);
        $conversation->touch();
        $this->markUpdate($channel, $updateId, $userId, $username);

        return response()->json(['ok' => true]);
    }

    protected function createConversation(
        string $chatId,
        ?string $userId,
        ?string $username,
    ): AiChatChannel {
        $conversation = AiChatConversation::create([
            'session_id' => (string) Str::uuid(),
            'mode' => 'human',
        ]);

        return $conversation->channels()->create([
            'provider' => TelegramBotService::PROVIDER,
            'external_chat_id' => $chatId,
            'external_user_id' => $userId,
            'external_username' => $username,
        ]);
    }

    protected function markUpdate(
        AiChatChannel $channel,
        int $updateId,
        ?string $userId,
        ?string $username,
    ): void {
        $channel->update([
            'last_update_id' => $updateId,
            'external_user_id' => $userId,
            'external_username' => $username,
        ]);
    }
}
