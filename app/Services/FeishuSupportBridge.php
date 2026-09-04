<?php

namespace App\Services;

use App\Models\AiChatConversation;
use App\Models\AiChatFeishuMessage;
use App\Models\AiChatMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Bridges human-mode website conversations to Feishu support staff.
 *
 * Since an application callback has no reply-to context, each operator keeps
 * one active conversation in cache and can override it with a #conversation_id
 * prefix.
 */
class FeishuSupportBridge
{
    private const ACTIVE_CONVERSATION_CACHE_PREFIX = 'feishu:active_conversation:';

    private const ACTIVE_CONVERSATION_TTL_DAYS = 7;

    public function __construct(
        private readonly FeishuBotService $feishu,
        private readonly AiChatTranslationService $translation,
    ) {}

    public function notifyCustomerMessage(
        AiChatConversation $conversation,
        AiChatMessage $message,
    ): void {
        if (! $this->feishu->isConfigured()) {
            return;
        }

        try {
            $notified = DB::transaction(function () use ($conversation, $message): bool {
                $lockedMessage = AiChatMessage::query()
                    ->whereKey($message->getKey())
                    ->lockForUpdate()
                    ->first();

                if (! $lockedMessage) {
                    return false;
                }

                $existing = AiChatFeishuMessage::query()
                    ->where('ai_chat_message_id', $lockedMessage->getKey())
                    ->where('direction', AiChatFeishuMessage::NOTIFICATION)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return false;
                }

                $lockedConversation = AiChatConversation::query()
                    ->with('user')
                    ->whereKey($conversation->getKey())
                    ->lockForUpdate()
                    ->first();

                if (! $lockedConversation) {
                    return false;
                }

                $notificationText = $this->notificationText($lockedConversation, $lockedMessage);
                $cardTitle = "网站客服 #{$lockedConversation->getKey()}";
                $cardDescription = $this->cardDescription($lockedConversation, $lockedMessage);
                $url = route('filament.admin.resources.ai-chat-conversations.view', [
                    'record' => $lockedConversation->getKey(),
                ]);

                foreach ($this->feishu->supportOpenIds() as $openId) {
                    $this->feishu->sendText($openId, $notificationText);
                    $this->feishu->sendCard($openId, $cardTitle, $cardDescription, $url);
                }

                AiChatFeishuMessage::create([
                    'conversation_id' => $lockedConversation->getKey(),
                    'ai_chat_message_id' => $lockedMessage->getKey(),
                    'feishu_open_id' => null,
                    'message_id' => null,
                    'direction' => AiChatFeishuMessage::NOTIFICATION,
                ]);

                return true;
            });

            if ($notified) {
                $this->refreshActiveConversations($conversation->getKey());
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function handleOperatorMessage(string $openId, string $messageId, string $text): void
    {
        $openId = trim($openId);
        $messageId = trim($messageId);

        if (! $this->feishu->isSupportUser($openId) || $messageId === '') {
            return;
        }

        try {
            if (AiChatFeishuMessage::query()->where('message_id', $messageId)->exists()) {
                return;
            }

            [$conversationId, $replyText] = $this->targetFromText($openId, $text);

            if ($conversationId === null || $replyText === '') {
                $this->sendOperatorText(
                    $openId,
                    '请用「#会话ID 内容」指定要回复的会话。',
                );

                return;
            }

            $translationAttributes = $this->translation->attributesFor('admin', $replyText);
            $result = DB::transaction(function () use (
                $conversationId,
                $messageId,
                $openId,
                $replyText,
                $translationAttributes,
            ): array {
                $existing = AiChatFeishuMessage::query()
                    ->where('message_id', $messageId)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return ['status' => 'duplicate'];
                }

                $conversation = AiChatConversation::query()
                    ->whereKey($conversationId)
                    ->lockForUpdate()
                    ->first();

                if (! $conversation) {
                    return ['status' => 'missing'];
                }

                if ($conversation->mode === 'ai') {
                    $conversation->update([
                        'mode' => 'human',
                        'human_requested_at' => now(),
                    ]);
                }

                $adminMessage = $conversation->messages()->create([
                    'role' => 'admin',
                    'content' => $replyText,
                    ...$translationAttributes,
                ]);
                $conversation->touch();

                AiChatFeishuMessage::create([
                    'conversation_id' => $conversation->getKey(),
                    'ai_chat_message_id' => $adminMessage->getKey(),
                    'feishu_open_id' => $openId,
                    'message_id' => $messageId,
                    'direction' => AiChatFeishuMessage::OPERATOR_REPLY,
                ]);

                return [
                    'status' => 'sent',
                    'conversation_id' => $conversation->getKey(),
                ];
            });

            if ($result['status'] === 'duplicate') {
                return;
            }

            if ($result['status'] === 'missing') {
                $this->sendOperatorText(
                    $openId,
                    '请用「#会话ID 内容」指定要回复的会话。',
                );

                return;
            }

            $sentConversationId = (int) ($result['conversation_id'] ?? 0);
            $this->putActiveConversation($openId, $sentConversationId);
            $this->sendOperatorText($openId, "已发送到会话 #{$sentConversationId}");
        } catch (Throwable $exception) {
            report($exception);
            $this->sendOperatorText($openId, '回复处理失败，请稍后重试。');
        }
    }

    /** @return array{0: int|null, 1: string} */
    private function targetFromText(string $openId, string $text): array
    {
        $text = trim($text);

        if (preg_match('/^#(\d+)\s*/u', $text, $matches) === 1) {
            $conversationId = (int) $matches[1];
            $replyText = trim(substr($text, strlen($matches[0])));

            return [$conversationId > 0 ? $conversationId : null, $replyText];
        }

        $activeConversationId = Cache::get($this->activeConversationCacheKey($openId));
        $activeConversationId = filter_var(
            $activeConversationId,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        return [is_int($activeConversationId) ? $activeConversationId : null, $text];
    }

    private function refreshActiveConversations(int $conversationId): void
    {
        foreach ($this->feishu->supportOpenIds() as $openId) {
            $this->putActiveConversation($openId, $conversationId);
        }
    }

    private function putActiveConversation(string $openId, int $conversationId): void
    {
        if ($openId === '' || $conversationId <= 0) {
            return;
        }

        Cache::put(
            $this->activeConversationCacheKey($openId),
            $conversationId,
            now()->addDays(self::ACTIVE_CONVERSATION_TTL_DAYS),
        );
    }

    private function activeConversationCacheKey(string $openId): string
    {
        return self::ACTIVE_CONVERSATION_CACHE_PREFIX.$openId;
    }

    private function sendOperatorText(string $openId, string $text): void
    {
        try {
            $this->feishu->sendText($openId, $text);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function notificationText(
        AiChatConversation $conversation,
        AiChatMessage $message,
    ): string {
        $customer = $conversation->user
            ? $conversation->user->email
            : 'Guest '.substr((string) $conversation->session_id, 0, 8);
        $content = trim($message->contentForAdmin());

        if ($content === '') {
            $content = $message->attachment_name
                ? '[Attachment: '.$message->attachment_name.']'
                : '[Empty customer message]';
        }

        $translationLabel = $message->isTranslatedForAdmin()
            ? "\n(AI translated)"
            : '';

        return Str::limit(
            "客服通知 #{$conversation->getKey()}\n客户: {$customer}\n\n{$content}{$translationLabel}\n\n回复「#{$conversation->getKey()} 内容」可直接回复客户。",
            2000,
            '…',
        );
    }

    private function cardDescription(
        AiChatConversation $conversation,
        AiChatMessage $message,
    ): string {
        $content = trim($message->contentForAdmin());

        if ($content === '') {
            $content = $message->attachment_name
                ? '[Attachment: '.$message->attachment_name.']'
                : '[Empty customer message]';
        }

        return Str::limit(
            "客户: {$this->customerLabel($conversation)}\n\n{$content}",
            512,
            '…',
        );
    }

    private function customerLabel(AiChatConversation $conversation): string
    {
        return $conversation->user
            ? (string) $conversation->user->email
            : 'Guest '.substr((string) $conversation->session_id, 0, 8);
    }
}
