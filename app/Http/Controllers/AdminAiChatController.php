<?php

namespace App\Http\Controllers;

use App\Models\AiChatConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
            ->with(['user:id,name,email', 'messages' => fn ($query) => $query->latest('id')->limit(1)])
            ->withCount('messages')
            ->orderByRaw("case when mode = 'human' then 0 else 1 end")
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get()
            ->map(function (AiChatConversation $conversation) {
                $last = $conversation->messages->first();

                return [
                    'id' => $conversation->id,
                    'mode' => $conversation->mode,
                    'customer' => $conversation->user?->email
                        ?? 'Guest '.substr($conversation->session_id, 0, 8),
                    'messages_count' => $conversation->messages_count,
                    'waiting' => $conversation->mode === 'human' && $last?->role === 'user',
                    'last_message' => $last?->content ?: ($last?->attachment_name ?? ''),
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
            ->map(fn ($message) => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'attachment_url' => $message->attachmentUrl(),
                'attachment_name' => $message->attachment_name,
                'created_at' => $message->created_at?->toIso8601String(),
            ])
            ->values();

        return response()->json(['messages' => $messages]);
    }

    /**
     * Reply to the customer as human support.
     */
    public function reply(AiChatConversation $conversation, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $message = $conversation->messages()->create([
            'role' => 'admin',
            'content' => trim($request->input('message')),
        ]);

        $conversation->touch();

        return response()->json([
            'message' => [
                'id' => $message->id,
                'role' => 'admin',
                'content' => $message->content,
                'created_at' => $message->created_at?->toIso8601String(),
            ],
        ], 201);
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
