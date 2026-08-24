<?php

namespace App\Services;

use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Models\AiChatTelegramMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class TelegramSupportBridge
{
    public function __construct(
        protected TelegramBotService $telegram,
    ) {}

    public function isSupportChat(string $chatId): bool
    {
        $configuredChatId = trim((string) config('services.telegram.support_chat_id'));

        return $configuredChatId !== '' && hash_equals($configuredChatId, $chatId);
    }

    public function notifyCustomerMessage(
        AiChatConversation $conversation,
        AiChatMessage $message,
    ): ?AiChatTelegramMessage {
        $chatId = $this->supportChatId();

        if ($chatId === null) {
            return null;
        }

        try {
            return DB::transaction(function () use ($conversation, $message, $chatId): ?AiChatTelegramMessage {
                $lockedMessage = AiChatMessage::query()
                    ->whereKey($message->getKey())
                    ->lockForUpdate()
                    ->first();

                if (! $lockedMessage) {
                    return null;
                }

                $existing = $lockedMessage->telegramMessages()
                    ->where('direction', AiChatTelegramMessage::NOTIFICATION)
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $telegramMessage = $this->telegram->sendMessage(
                    $chatId,
                    $this->notificationText($conversation, $lockedMessage),
                );
                $telegramMessageId = (int) ($telegramMessage['message_id'] ?? 0);

                if ($telegramMessageId <= 0) {
                    return null;
                }

                return AiChatTelegramMessage::create([
                    'conversation_id' => $conversation->getKey(),
                    'ai_chat_message_id' => $lockedMessage->getKey(),
                    'telegram_chat_id' => $chatId,
                    'telegram_message_id' => $telegramMessageId,
                    'direction' => AiChatTelegramMessage::NOTIFICATION,
                ]);
            });
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * Route a Telegram operator reply back to the website conversation named
     * by the notification message being replied to.
     */
    /** @param array<string, mixed> $message */
    public function handleOperatorReply(int $updateId, array $message): void
    {
        if ($updateId <= 0 || AiChatTelegramMessage::query()
            ->where('telegram_update_id', $updateId)
            ->exists()) {
            return;
        }

        $chatId = (string) data_get($message, 'chat.id', '');
        $userId = (string) data_get($message, 'from.id', '');

        if (! $this->isSupportChat($chatId) || ! $this->isAuthorizedUser($userId)) {
            return;
        }

        $text = trim((string) ($message['text'] ?? $message['caption'] ?? ''));
        $replyToMessageId = (int) data_get($message, 'reply_to_message.message_id', 0);
        $operatorMessageId = (int) data_get($message, 'message_id', 0);

        if ($text === '' || $replyToMessageId <= 0 || $operatorMessageId <= 0) {
            return;
        }

        $notification = AiChatTelegramMessage::query()
            ->where('telegram_chat_id', $chatId)
            ->where('telegram_message_id', $replyToMessageId)
            ->where('direction', AiChatTelegramMessage::NOTIFICATION)
            ->first();

        if (! $notification) {
            return;
        }

        DB::transaction(function () use ($notification, $text, $chatId, $updateId, $operatorMessageId): void {
            if (AiChatTelegramMessage::query()
                ->where('telegram_update_id', $updateId)
                ->exists()) {
                return;
            }

            $conversation = $notification->conversation()->lockForUpdate()->first();

            if (! $conversation) {
                return;
            }

            $adminMessage = $conversation->messages()->create([
                'role' => 'admin',
                'content' => $text,
            ]);
            $conversation->touch();

            AiChatTelegramMessage::create([
                'conversation_id' => $conversation->getKey(),
                'ai_chat_message_id' => $adminMessage->getKey(),
                'telegram_chat_id' => $chatId,
                'telegram_message_id' => $operatorMessageId,
                'telegram_update_id' => $updateId,
                'direction' => AiChatTelegramMessage::OPERATOR_REPLY,
            ]);
        });
    }

    private function supportChatId(): ?string
    {
        $chatId = trim((string) config('services.telegram.support_chat_id'));

        return $chatId === '' ? null : $chatId;
    }

    private function isAuthorizedUser(string $userId): bool
    {
        if ($userId === '') {
            return false;
        }

        $configuredUserIds = config('services.telegram.support_user_ids', []);
        $configuredUserIds = is_array($configuredUserIds) ? $configuredUserIds : [];
        $configuredUserIds = array_map('strval', array_filter($configuredUserIds));

        return in_array($userId, $configuredUserIds, true);
    }

    private function notificationText(
        AiChatConversation $conversation,
        AiChatMessage $message,
    ): string {
        $customer = $conversation->user
            ? $conversation->user->email
            : 'Guest '.substr((string) $conversation->session_id, 0, 8);
        $content = trim((string) $message->content);

        if ($content === '') {
            $content = $message->attachment_name
                ? '[Attachment: '.$message->attachment_name.']'
                : '[Empty customer message]';
        }

        return Str::limit(
            "Website support #{$conversation->getKey()}\nCustomer: {$customer}\n\n{$content}\n\nReply to this message to respond.",
            3900,
            '…',
        );
    }
}
