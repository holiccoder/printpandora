<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ChatApiController extends Controller
{
    public function conversations(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->input('limit', 50), 1), 100);

        $conversations = AiChatConversation::query()
            ->with(['user:id,name,email', 'channels', 'latestMessage'])
            ->withCount('messages')
            ->orderByRaw("case when mode = 'human' then 0 else 1 end")
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (AiChatConversation $conversation): array => $this->serializeConversation($conversation))
            ->values();

        return response()->json([
            'conversations' => $conversations,
        ]);
    }

    public function messages(AiChatConversation $conversation, Request $request): JsonResponse
    {
        $afterId = max(0, (int) $request->input('after_id', 0));
        $limit = min(max((int) $request->input('limit', 100), 1), 200);

        $messages = $conversation->messages()
            ->where('id', '>', $afterId)
            ->oldest('id')
            ->limit($limit)
            ->get()
            ->map(fn (AiChatMessage $message): array => $this->serializeMessage($message))
            ->values();

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => $messages,
        ]);
    }

    public function reply(
        AiChatConversation $conversation,
        Request $request,
        TelegramBotService $telegram,
    ): JsonResponse {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);
        $text = trim((string) $validated['message']);

        if ($text === '') {
            return response()->json(['message' => 'The reply cannot be empty.'], 422);
        }

        try {
            $telegramMessage = $telegram->sendToConversation($conversation, $text);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'The reply could not be delivered through Telegram.',
            ], 502);
        }

        $message = $conversation->messages()->create([
            'role' => 'admin',
            'content' => $text,
        ]);
        $conversation->touch();

        return response()->json([
            'message' => $this->serializeMessage($message),
            'telegram_delivered' => $telegramMessage !== null,
            'telegram_message_id' => $telegramMessage['message_id'] ?? null,
        ], 201);
    }

    public function telegramLink(
        AiChatConversation $conversation,
        TelegramBotService $telegram,
    ): JsonResponse {
        try {
            return response()->json($telegram->createConversationLink($conversation), 201);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Telegram is not configured for conversation links.',
            ], 503);
        }
    }

    public function takeover(AiChatConversation $conversation): JsonResponse
    {
        $conversation->update([
            'mode' => 'human',
            'human_requested_at' => $conversation->human_requested_at ?? now(),
        ]);

        return response()->json(['mode' => 'human']);
    }

    public function resolve(AiChatConversation $conversation): JsonResponse
    {
        $conversation->update(['mode' => 'ai']);

        return response()->json(['mode' => 'ai']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeConversation(AiChatConversation $conversation): array
    {
        $last = $conversation->latestMessage;
        $telegram = $conversation->channels
            ->firstWhere('provider', TelegramBotService::PROVIDER);

        return [
            'id' => $conversation->id,
            'session_id' => $conversation->session_id,
            'mode' => $conversation->mode,
            'customer' => $conversation->user
                ? $conversation->user->email
                : 'Guest '.substr($conversation->session_id, 0, 8),
            'messages_count' => $conversation->messages_count,
            'waiting' => $conversation->mode === 'human' && $last?->role === 'user',
            'last_message' => $last
                ? ($last->content ?: ($last->attachment_name ?? ''))
                : '',
            'last_message_at' => $last?->created_at?->toIso8601String(),
            'telegram' => [
                'connected' => $telegram?->external_chat_id !== null,
                'chat_id' => $telegram?->external_chat_id,
                'username' => $telegram?->external_username,
                'link_pending' => $telegram?->link_token_hash !== null,
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function serializeMessage(AiChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'attachment_url' => $message->attachmentUrl(),
            'attachment_name' => $message->attachment_name,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
