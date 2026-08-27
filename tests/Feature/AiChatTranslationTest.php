<?php

namespace Tests\Feature;

use App\Ai\Agents\CustomerSupportAgent;
use App\Ai\Agents\MessageTranslationAgent;
use App\Models\Admin;
use App\Models\AiChatConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Ai\Embeddings;
use Tests\TestCase;

class AiChatTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('aichat.translation.enabled', true);
        config()->set('aichat.api_token', 'test-chat-api-token');
        config()->set('services.telegram.bot_token', 'test-telegram-token');
        config()->set('services.telegram.webhook_secret', 'test-webhook-secret');
        config()->set('services.telegram.support_chat_id', null);
        config()->set('services.telegram.support_user_ids', ['777']);

        Http::fake([
            'https://api.telegram.org/bottest-telegram-token/*' => function (HttpRequest $request) {
                return Http::response([
                    'ok' => true,
                    'result' => ['message_id' => 99],
                ]);
            },
        ]);
    }

    public function test_english_customer_messages_are_translated_for_admins(): void
    {
        MessageTranslationAgent::fake(['我需要帮助。']);

        $sessionId = (string) Str::uuid();
        $this->postJson('/ai/chat/handoff', ['session_id' => $sessionId])
            ->assertOk();

        $this->postJson('/ai/chat/message', [
            'session_id' => $sessionId,
            'message' => 'I need help.',
        ])->assertCreated();

        $conversation = AiChatConversation::query()
            ->where('session_id', $sessionId)
            ->firstOrFail();

        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'I need help.',
            'translated_content' => '我需要帮助。',
            'translation_target' => 'zh-CN',
        ]);

        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->getJson("/ai/chat/admin/conversations/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonPath('messages.0.content', '我需要帮助。')
            ->assertJsonPath('messages.0.is_translated', true)
            ->assertJsonPath('messages.0.translation_label', 'AI translated');

        $this->actingAs($admin, 'admin')
            ->getJson('/ai/chat/admin/conversations')
            ->assertOk()
            ->assertJsonPath('conversations.0.last_message', '我需要帮助。')
            ->assertJsonPath('conversations.0.last_message_is_translated', true);
    }

    public function test_ai_mode_customer_messages_are_also_saved_with_an_admin_translation(): void
    {
        Embeddings::fake([[[1.0, 0.0, 0.0]]]);
        MessageTranslationAgent::fake(['我想知道运费。']);
        CustomerSupportAgent::fake(['Shipping takes 5-10 business days.']);

        $sessionId = (string) Str::uuid();
        $response = $this->post('/ai/chat', [
            'message' => 'How much is shipping?',
            'session_id' => $sessionId,
            'history' => [],
        ]);
        $response->streamedContent();

        $conversation = AiChatConversation::query()
            ->where('session_id', $sessionId)
            ->firstOrFail();

        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'How much is shipping?',
            'translated_content' => '我想知道运费。',
            'translation_target' => 'zh-CN',
        ]);
    }

    public function test_chinese_admin_replies_are_translated_for_the_customer(): void
    {
        MessageTranslationAgent::fake(['We can help you.']);

        $conversation = $this->conversation();
        $customerMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => 'I need help.',
        ]);
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->postJson("/ai/chat/admin/conversations/{$conversation->id}/reply", [
                'message' => '我们可以帮助你。',
            ])
            ->assertCreated()
            ->assertJsonPath('message.content', '我们可以帮助你。')
            ->assertJsonPath('message.customer_content', 'We can help you.')
            ->assertJsonPath('message.customer_is_translated', true);

        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'admin',
            'content' => '我们可以帮助你。',
            'translated_content' => 'We can help you.',
            'translation_target' => 'en',
        ]);

        $this->getJson("/ai/chat/poll?session_id={$conversation->session_id}&after_id={$customerMessage->id}")
            ->assertOk()
            ->assertJsonPath('messages.0.content', 'We can help you.')
            ->assertJsonPath('messages.0.is_translated', true)
            ->assertJsonPath('messages.0.translation_label', 'AI translated');
    }

    public function test_translation_can_be_disabled_without_changing_message_content(): void
    {
        config()->set('aichat.translation.enabled', false);
        MessageTranslationAgent::fake();

        $conversation = $this->conversation();
        $this->postJson('/ai/chat/message', [
            'session_id' => $conversation->session_id,
            'message' => 'I need help.',
        ])->assertCreated();

        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->postJson("/ai/chat/admin/conversations/{$conversation->id}/reply", [
                'message' => '我们可以帮助你。',
            ])
            ->assertCreated()
            ->assertJsonPath('message.content', '我们可以帮助你。')
            ->assertJsonPath('message.customer_is_translated', false);

        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'I need help.',
            'translated_content' => null,
        ]);
        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'admin',
            'content' => '我们可以帮助你。',
            'translated_content' => null,
        ]);

        MessageTranslationAgent::assertNeverPrompted();
    }

    public function test_website_translation_is_used_in_the_telegram_support_notification_and_poll(): void
    {
        config()->set('services.telegram.support_chat_id', '-1001234567890');
        MessageTranslationAgent::fake(['我需要帮助。', 'We are checking this for you.']);

        $conversation = $this->conversation();

        $this->postJson('/ai/chat/message', [
            'session_id' => $conversation->session_id,
            'message' => 'I need help.',
        ])->assertCreated();

        Http::assertSent(function (HttpRequest $request): bool {
            return $request->data()['chat_id'] === '-1001234567890'
                && str_contains($request->data()['text'], '我需要帮助。')
                && str_contains($request->data()['text'], '(AI translated)');
        });

        $customerMessage = $conversation->messages()->latest('id')->firstOrFail();
        $notification = $conversation->telegramMessages()
            ->where('direction', 'notification')
            ->firstOrFail();

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-webhook-secret')
            ->postJson('/api/telegram/webhook', [
                'update_id' => 700,
                'message' => [
                    'message_id' => 701,
                    'from' => ['id' => 777],
                    'chat' => ['id' => -1001234567890, 'type' => 'supergroup'],
                    'text' => '我们正在为您检查。',
                    'reply_to_message' => [
                        'message_id' => $notification->telegram_message_id,
                    ],
                ],
            ])
            ->assertOk();

        $this->getJson("/ai/chat/poll?session_id={$conversation->session_id}&after_id={$customerMessage->id}")
            ->assertOk()
            ->assertJsonPath('messages.0.content', 'We are checking this for you.')
            ->assertJsonPath('messages.0.is_translated', true)
            ->assertJsonPath('messages.0.translation_label', 'AI translated');
    }

    public function test_telegram_customer_and_api_admin_messages_use_their_translated_direction(): void
    {
        MessageTranslationAgent::fake(['我需要帮助。', 'We can help you.']);

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-webhook-secret')
            ->postJson('/api/telegram/webhook', [
                'update_id' => 800,
                'message' => [
                    'message_id' => 801,
                    'from' => ['id' => 12345, 'username' => 'customer'],
                    'chat' => ['id' => 67890, 'type' => 'private'],
                    'text' => 'I need help.',
                ],
            ])
            ->assertOk();

        $conversation = AiChatConversation::query()
            ->whereHas('channels', fn ($query) => $query->where('external_chat_id', '67890'))
            ->firstOrFail();

        $this->withToken('test-chat-api-token')
            ->getJson("/api/v1/support/chat/conversations/{$conversation->id}/messages")
            ->assertOk()
            ->assertJsonPath('messages.0.content', '我需要帮助。')
            ->assertJsonPath('messages.0.is_translated', true)
            ->assertJsonPath('messages.0.translation_label', 'AI translated');

        $this->withToken('test-chat-api-token')
            ->postJson("/api/v1/support/chat/conversations/{$conversation->id}/reply", [
                'message' => '我们可以帮助你。',
            ])
            ->assertCreated()
            ->assertJsonPath('message.content', '我们可以帮助你。')
            ->assertJsonPath('customer_content', 'We can help you.')
            ->assertJsonPath('customer_is_translated', true);

        Http::assertSent(function (HttpRequest $request): bool {
            return $request->data()['chat_id'] === '67890'
                && $request->data()['text'] === 'We can help you.';
        });
    }

    private function conversation(): AiChatConversation
    {
        return AiChatConversation::create([
            'session_id' => (string) Str::uuid(),
            'mode' => 'human',
        ]);
    }
}
