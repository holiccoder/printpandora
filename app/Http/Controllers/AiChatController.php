<?php

namespace App\Http\Controllers;

use App\Ai\Agents\CustomerSupportAgent;
use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Services\AiChatTranslationService;
use App\Services\AiKnowledgeRetriever;
use App\Services\TelegramSupportBridge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Laravel\Ai\Responses\StreamedAgentResponse;
use Laravel\Ai\Streaming\Events\TextDelta;

class AiChatController extends Controller
{
    public function store(
        Request $request,
        AiKnowledgeRetriever $retriever,
        AiChatTranslationService $translation,
    ): StreamableAgentResponse|JsonResponse {
        abort_unless(config('aichat.enabled', true), 404);

        // The app renders exceptions as JSON only for api/* routes, so validate
        // manually to guarantee a JSON 422 for this fetch-only endpoint.
        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:1000'],
            'session_id' => ['required', 'uuid'],
            'history' => ['nullable', 'array', 'max:'.(int) config('aichat.max_history', 10)],
            'history.*.role' => ['required', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $message = $validated['message'];
        $history = $validated['history'] ?? [];

        $conversation = AiChatConversation::firstOrCreate(
            ['session_id' => $validated['session_id']],
            ['user_id' => $request->user()?->id],
        );

        if ($conversation->user_id === null && $request->user()) {
            $conversation->update(['user_id' => $request->user()->id]);
        }

        // In human mode the AI stays silent — the customer message should go
        // through the plain message endpoint instead.
        if ($conversation->mode === 'human') {
            return response()->json([
                'message' => 'This conversation is being handled by human support.',
                'mode' => 'human',
            ], 409);
        }

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $message,
            ...$translation->attributesFor('user', $message),
        ]);

        $knowledge = $retriever->retrieve($message);

        $agent = new CustomerSupportAgent($knowledge, $history, $request->user());

        return $agent
            ->stream($message, [], config('aichat.provider'), config('aichat.model'))
            ->then(function (StreamedAgentResponse $response) use ($conversation) {
                $text = TextDelta::combine($response->events);

                if (trim($text) !== '') {
                    $conversation->messages()->create([
                        'role' => 'assistant',
                        'content' => $text,
                    ]);
                }
            });
    }

    /**
     * Switch the conversation from AI to human support.
     */
    public function handoff(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => ['required', 'uuid'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $conversation = AiChatConversation::firstOrCreate(
            ['session_id' => $request->input('session_id')],
            ['user_id' => $request->user()?->id],
        );

        if ($conversation->user_id === null && $request->user()) {
            $conversation->update(['user_id' => $request->user()->id]);
        }

        if ($conversation->mode !== 'human') {
            $conversation->update([
                'mode' => 'human',
                'human_requested_at' => now(),
            ]);
        }

        return response()->json(['mode' => 'human']);
    }

    /**
     * Post a plain customer message (human mode), optionally with an
     * attachment such as a design draft or a screenshot.
     */
    public function message(
        Request $request,
        TelegramSupportBridge $telegramSupport,
        AiChatTranslationService $translation,
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'session_id' => ['required', 'uuid'],
            'message' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,gif,webp,pdf'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $text = trim((string) $request->input('message', ''));

        if ($text === '' && ! $request->hasFile('attachment')) {
            return response()->json(['message' => 'Type a message or attach a file.'], 422);
        }

        $conversation = AiChatConversation::firstOrCreate(
            ['session_id' => $request->input('session_id')],
            ['user_id' => $request->user()?->id],
        );

        if ($conversation->user_id === null && $request->user()) {
            $conversation->update(['user_id' => $request->user()->id]);
        }

        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('ai-chat-attachments', 'public');
            $attachmentName = $file->getClientOriginalName();
        }

        $message = $conversation->messages()->create([
            'role' => 'user',
            'content' => $text,
            ...$translation->attributesFor('user', $text),
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        $conversation->touch();
        if ($conversation->mode === 'human') {
            $telegramSupport->notifyCustomerMessage($conversation, $message);
        }

        return response()->json(['message' => $this->serializeMessage($message)], 201);
    }

    /**
     * Poll for new messages (and the current mode) after a given message id.
     */
    public function poll(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query->all(), [
            'session_id' => ['required', 'uuid'],
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $conversation = AiChatConversation::query()
            ->where('session_id', $request->query('session_id'))
            ->first();

        if (! $conversation) {
            return response()->json(['mode' => 'ai', 'messages' => []])
                ->header('Cache-Control', 'no-store, private');
        }

        $messages = $conversation->messages()
            ->where('id', '>', (int) $request->query('after_id', 0))
            ->oldest('id')
            ->get()
            ->map(fn ($message) => $this->serializeMessage($message))
            ->values();

        return response()->json([
            'mode' => $conversation->mode,
            'messages' => $messages,
        ])->header('Cache-Control', 'no-store, private');
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeMessage(AiChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->contentForCustomer(),
            'is_translated' => $message->isTranslatedForCustomer(),
            'translation_label' => $message->translationLabelForCustomer(),
            'attachment_url' => $message->attachmentUrl(),
            'attachment_name' => $message->attachment_name,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
