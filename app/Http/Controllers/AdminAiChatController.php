<?php

namespace App\Http\Controllers;

use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Services\AiChatTranslationService;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Admin-side endpoints for the human-support chat bubble inside Filament.
 * All routes require the "admin" guard (see routes/web.php).
 */
class AdminAiChatController extends Controller
{
    /**
     * Recent conversations, human-mode ones first, with a waiting flag when
     * the last message came from the customer.
     */
    public function index(): JsonResponse
    {
        $conversations = AiChatConversation::query()
            ->with(['user:id,name,email', 'latestMessage'])
            ->withCount('messages')
            ->orderByRaw("case when mode = 'human' then 0 else 1 end")
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get()
            ->map(function (AiChatConversation $conversation): array {
                $last = $conversation->latestMessage;

                return [
                    'id' => $conversation->id,
                    'mode' => $conversation->mode,
                    'customer' => $conversation->user
                        ? $conversation->user->email
                        : 'Guest '.substr($conversation->session_id, 0, 8),
                    'messages_count' => $conversation->messages_count,
                    'waiting' => $conversation->mode === 'human' && $last?->role === 'user',
                    'last_message' => $last
                        ? ($last->contentForAdmin() ?: ($last->attachment_name ?? ''))
                        : '',
                    'last_message_is_translated' => $last?->isTranslatedForAdmin() ?? false,
                    'last_message_translation_label' => $last?->translationLabelForAdmin(),
                    'last_message_at' => $last?->created_at?->toIso8601String(),
                    'human_requested_at' => $conversation->human_requested_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'conversations' => $conversations,
            'waiting_count' => $conversations->where('waiting', true)->count(),
        ]);
    }

    /**
     * Messages of one conversation (optionally only after a given id).
     */
    public function messages(AiChatConversation $conversation, Request $request): JsonResponse
    {
        $messages = $conversation->messages()
            ->where('id', '>', (int) $request->query('after_id', 0))
            ->oldest('id')
            ->get()
            ->map(fn (AiChatMessage $message) => $this->serializeMessage($message))
            ->values();

        return response()->json(['messages' => $messages]);
    }

    /**
     * Reply to the customer as human support.
     */
    public function reply(
        AiChatConversation $conversation,
        Request $request,
        TelegramBotService $telegram,
        AiChatTranslationService $translation,
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $text = trim($request->input('message'));

        if ($text === '') {
            return response()->json(['message' => 'The reply cannot be empty.'], 422);
        }

        $translationAttributes = $translation->attributesFor('admin', $text);
        $customerText = $translationAttributes['translated_content'] ?? $text;

        try {
            $telegramMessage = $telegram->sendToConversation($conversation, $customerText);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'The reply could not be delivered through Telegram.',
            ], 502);
        }

        $message = $conversation->messages()->create([
            'role' => 'admin',
            'content' => $text,
            ...$translationAttributes,
        ]);

        $conversation->touch();

        return response()->json([
            'message' => [
                ...$this->serializeMessage($message),
                'customer_content' => $message->contentForCustomer(),
                'customer_is_translated' => $message->isTranslatedForCustomer(),
            ],
            'telegram_delivered' => $telegramMessage !== null,
            'telegram_message_id' => $telegramMessage['message_id'] ?? null,
        ], 201);
    }

    /** @return array<string, mixed> */
    protected function serializeMessage(AiChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->contentForAdmin(),
            'is_translated' => $message->isTranslatedForAdmin(),
            'translation_label' => $message->translationLabelForAdmin(),
            'attachment_url' => $message->attachmentUrl(),
            'attachment_name' => $message->attachment_name,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    /**
     * Hand the conversation back to the AI.
     */
    public function resolve(AiChatConversation $conversation): JsonResponse
    {
        $conversation->update(['mode' => 'ai']);

        return response()->json(['mode' => 'ai']);
    }

    /**
     * Take over an AI conversation as human support.
     */
    public function takeover(AiChatConversation $conversation): JsonResponse
    {
        $conversation->update([
            'mode' => 'human',
            'human_requested_at' => $conversation->human_requested_at ?? now(),
        ]);

        return response()->json(['mode' => 'human']);
    }
}
