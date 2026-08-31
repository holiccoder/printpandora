<?php

namespace App\Jobs;

use App\Models\AiChatChannel;
use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Models\AiChatWecomMessage;
use App\Services\AiChatTranslationService;
use App\Services\WeComKfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SyncWeComKfMessages implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const CURSOR_CACHE_KEY = 'wecom:kf:cursor';

    public int $tries = 3;

    public function __construct(
        public ?string $token = null,
        public ?string $openKfId = null,
    ) {}

    /** @return array<int, WithoutOverlapping> */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('wecom:kf:sync'))
                ->releaseAfter(5)
                ->expireAfter(300),
        ];
    }

    public function handle(
        WeComKfService $wecom,
        AiChatTranslationService $translation,
    ): void {
        $cachedCursor = Cache::get(self::CURSOR_CACHE_KEY);
        $cursor = is_string($cachedCursor) && trim($cachedCursor) !== ''
            ? $cachedCursor
            : null;

        while (true) {
            $page = $wecom->syncMessages($cursor, $this->token, $this->openKfId);
            $messages = is_array($page['msg_list'] ?? null) ? $page['msg_list'] : [];

            foreach ($messages as $message) {
                if (is_array($message)) {
                    $this->processMessage($message, $translation, $wecom);
                }
            }

            $nextCursor = trim((string) ($page['next_cursor'] ?? ''));

            if ($nextCursor !== '') {
                Cache::forever(self::CURSOR_CACHE_KEY, $nextCursor);
            }

            if ((int) ($page['has_more'] ?? 0) !== 1) {
                return;
            }

            if ($nextCursor === '' || $nextCursor === $cursor) {
                throw new RuntimeException('WeCom sync_msg returned has_more without a new cursor.');
            }

            $cursor = $nextCursor;
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception) {
            report($exception);
        }
    }

    /** @param array<string, mixed> $payload */
    private function processMessage(
        array $payload,
        AiChatTranslationService $translation,
        WeComKfService $wecom,
    ): void {
        if ((int) ($payload['origin'] ?? 0) !== 3) {
            return;
        }

        $msgid = trim((string) ($payload['msgid'] ?? ''));
        $externalUserId = trim((string) ($payload['external_userid'] ?? ''));

        if ($msgid === '' || $externalUserId === '') {
            return;
        }

        $result = DB::transaction(function () use (
            $msgid,
            $externalUserId,
            $payload,
            $translation,
        ): ?array {
            $existing = AiChatWecomMessage::query()
                ->where('msgid', $msgid)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return null;
            }

            $channel = AiChatChannel::query()
                ->where('provider', WeComKfService::PROVIDER)
                ->where('external_chat_id', $externalUserId)
                ->lockForUpdate()
                ->first();

            if (! $channel) {
                $conversation = AiChatConversation::create([
                    'session_id' => (string) Str::uuid(),
                    'mode' => 'ai',
                ]);
                $channel = $conversation->channels()->create([
                    'provider' => WeComKfService::PROVIDER,
                    'external_chat_id' => $externalUserId,
                    'external_user_id' => $externalUserId,
                ]);
            } else {
                $conversation = $channel->conversation()->first();

                if (! $conversation) {
                    return null;
                }

                if ($channel->external_user_id !== $externalUserId) {
                    $channel->update(['external_user_id' => $externalUserId]);
                }
            }

            $msgType = trim((string) ($payload['msgtype'] ?? '')) ?: 'unknown';
            $content = $this->contentFor($payload, $msgType);
            $attributes = [
                'role' => 'user',
                'content' => $content,
            ];

            if ($msgType === 'text') {
                $attributes += $translation->attributesFor('user', $content);
            }

            $chatMessage = $conversation->messages()->create($attributes);
            $conversation->touch();

            AiChatWecomMessage::create([
                'channel_id' => $channel->getKey(),
                'msgid' => $msgid,
                'ai_chat_message_id' => $chatMessage->getKey(),
            ]);

            return [
                'channel' => $channel,
                'conversation' => $conversation,
                'message' => $chatMessage,
            ];
        });

        if (! is_array($result)) {
            return;
        }

        /** @var AiChatChannel $channel */
        $channel = $result['channel'];
        /** @var AiChatConversation $conversation */
        $conversation = $result['conversation'];
        /** @var AiChatMessage $message */
        $message = $result['message'];

        if ($conversation->mode !== 'ai') {
            return;
        }

        $text = (string) $message->content;

        if ($this->isHandoffRequest($text)) {
            $conversation->update([
                'mode' => 'human',
                'human_requested_at' => $conversation->human_requested_at ?? now(),
            ]);
            $this->sendAndStore(
                $conversation,
                $channel->external_chat_id,
                (string) config('aichat.wecom_handoff_message'),
                $wecom,
            );

            return;
        }

        GenerateWeComAiReply::dispatch($conversation, $message);
    }

    /** @param array<string, mixed> $payload */
    private function contentFor(array $payload, string $msgType): string
    {
        if ($msgType === 'text') {
            $content = trim((string) data_get($payload, 'text.content', ''));

            return $content !== '' ? $content : '[WeCom text message received]';
        }

        return "[WeCom {$msgType} message received]";
    }

    private function isHandoffRequest(string $text): bool
    {
        $keywords = config('aichat.wecom_handoff_keywords', ['人工', 'human', '转人工']);

        if (! is_array($keywords)) {
            return false;
        }

        $text = mb_strtolower($text, 'UTF-8');

        foreach ($keywords as $keyword) {
            $keyword = trim((string) $keyword);

            if ($keyword !== '' && mb_strpos($text, mb_strtolower($keyword, 'UTF-8')) !== false) {
                return true;
            }
        }

        return false;
    }

    private function sendAndStore(
        AiChatConversation $conversation,
        ?string $externalUserId,
        string $text,
        WeComKfService $wecom,
    ): void {
        if ($externalUserId === null || trim($externalUserId) === '' || trim($text) === '') {
            return;
        }

        $wecom->sendMessage($externalUserId, $text);
        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $text,
        ]);
        $conversation->touch();
    }
}
