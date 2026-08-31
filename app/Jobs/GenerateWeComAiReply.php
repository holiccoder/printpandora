<?php

namespace App\Jobs;

use App\Ai\Agents\CustomerSupportAgent;
use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Services\AiKnowledgeRetriever;
use App\Services\WeComKfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class GenerateWeComAiReply implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public AiChatConversation $conversation,
        public AiChatMessage $message,
    ) {}

    public function handle(
        AiKnowledgeRetriever $retriever,
        WeComKfService $wecom,
    ): void {
        $conversation = AiChatConversation::query()
            ->whereKey($this->conversation->getKey())
            ->first();
        $message = AiChatMessage::query()
            ->whereKey($this->message->getKey())
            ->first();

        if (! $conversation || ! $message || $conversation->mode !== 'ai') {
            return;
        }

        $text = trim((string) $message->content);

        if ($text === '') {
            return;
        }

        try {
            $knowledge = $retriever->retrieve($text);
            $agent = new CustomerSupportAgent(
                $knowledge,
                $this->history($conversation, $message),
                null,
            );
            $reply = trim((string) $agent
                ->prompt($text, [], config('aichat.provider'), config('aichat.model'))
                ->text);

            if ($reply === '') {
                throw new RuntimeException('WeCom AI support agent returned an empty reply.');
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->handoff($conversation, $wecom);

            return;
        }

        $conversation->refresh();

        if ($conversation->mode !== 'ai') {
            return;
        }

        $channel = $conversation->channels()
            ->where('provider', WeComKfService::PROVIDER)
            ->whereNotNull('external_chat_id')
            ->first();

        if (! $channel || $channel->external_chat_id === null) {
            return;
        }

        $wecom->sendMessage($channel->external_chat_id, $reply);
        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $reply,
        ]);
        $conversation->touch();
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception) {
            report($exception);
        }
    }

    /** @return array<int, array{role: string, content: string}> */
    private function history(
        AiChatConversation $conversation,
        AiChatMessage $currentMessage,
    ): array {
        $limit = max(0, (int) config('aichat.wecom_history_limit', 10));

        if ($limit === 0) {
            return [];
        }

        return $conversation->messages()
            ->whereKeyNot($currentMessage->getKey())
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(function (AiChatMessage $message): ?array {
                if ($message->role === 'user') {
                    return [
                        'role' => 'user',
                        'content' => $message->contentForCustomer(),
                    ];
                }

                if (in_array($message->role, ['assistant', 'admin'], true)) {
                    return [
                        'role' => 'assistant',
                        'content' => $message->contentForCustomer(),
                    ];
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function handoff(AiChatConversation $conversation, WeComKfService $wecom): void
    {
        $conversation->update([
            'mode' => 'human',
            'human_requested_at' => $conversation->human_requested_at ?? now(),
        ]);

        $channel = $conversation->channels()
            ->where('provider', WeComKfService::PROVIDER)
            ->whereNotNull('external_chat_id')
            ->first();

        if (! $channel || $channel->external_chat_id === null) {
            return;
        }

        $fallback = (string) config('aichat.wecom_fallback_message');

        try {
            $wecom->sendMessage($channel->external_chat_id, $fallback);
            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $fallback,
            ]);
            $conversation->touch();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
