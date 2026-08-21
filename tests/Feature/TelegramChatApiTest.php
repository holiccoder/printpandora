<?php

namespace Tests\Feature;

use App\Models\AiChatChannel;
use App\Models\AiChatConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class TelegramChatApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('aichat.api_token', 'test-chat-api-token');
        config()->set('services.telegram.bot_token', 'test-telegram-token');
        config()->set('services.telegram.bot_username', 'inkpavo_support_bot');
        config()->set('services.telegram.webhook_secret', 'test-webhook-secret');

        Http::fake([
            'https://api.telegram.org/bottest-telegram-token/*' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 99],
            ]),
        ]);
    }

    public function test_external_chat_api_requires_a_bearer_token(): void
    {
        $this->getJson('/api/v1/support/chat/conversations')
            ->assertUnauthorized();

        $this->withToken('wrong-token')
            ->getJson('/api/v1/support/chat/conversations')
            ->assertUnauthorized();
    }

    public function test_external_client_can_create_a_telegram_link(): void
    {
        $conversation = $this->conversation();

        $response = $this->withToken('test-chat-api-token')
            ->postJson("/api/v1/support/chat/conversations/{$conversation->id}/telegram-link")
            ->assertCreated()
            ->assertJsonStructure(['url', 'expires_at']);

        $this->assertStringStartsWith(
            'https://t.me/inkpavo_support_bot?start=',
            $response->json('url'),
        );
        $this->assertDatabaseHas('ai_chat_channels', [
            'conversation_id' => $conversation->id,
            'provider' => 'telegram',
        ]);
    }

    public function test_telegram_webhook_links_a_conversation_and_records_messages(): void
    {
        $conversation = $this->conversation();
        $link = $this->withToken('test-chat-api-token')
            ->postJson("/api/v1/support/chat/conversations/{$conversation->id}/telegram-link")
            ->json('url');
        parse_str((string) parse_url($link, PHP_URL_QUERY), $query);

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-webhook-secret')
            ->postJson('/api/telegram/webhook', [
                'update_id' => 100,
                'message' => [
                    'message_id' => 1,
                    'from' => ['id' => 12345, 'username' => 'customer'],
                    'chat' => ['id' => 67890, 'type' => 'private'],
                    'text' => '/start '.$query['start'],
                ],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('ai_chat_channels', [
            'conversation_id' => $conversation->id,
            'external_chat_id' => '67890',
            'external_user_id' => '12345',
            'external_username' => 'customer',
            'last_update_id' => 100,
        ]);

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-webhook-secret')
            ->postJson('/api/telegram/webhook', [
                'update_id' => 101,
                'message' => [
                    'message_id' => 2,
                    'from' => ['id' => 12345, 'username' => 'customer'],
                    'chat' => ['id' => 67890, 'type' => 'private'],
                    'text' => 'I need help with my order.',
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'I need help with my order.',
        ]);
    }

    public function test_each_telegram_chat_id_gets_a_separate_conversation(): void
    {
        foreach ([111, 222] as $index => $chatId) {
            $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-webhook-secret')
                ->postJson('/api/telegram/webhook', [
                    'update_id' => $index + 1,
                    'message' => [
                        'message_id' => 1,
                        'from' => ['id' => $chatId],
                        'chat' => ['id' => $chatId, 'type' => 'private'],
                        'text' => 'Hello support',
                    ],
                ])
                ->assertOk();
        }

        $this->assertSame(2, AiChatConversation::query()->count());
        $this->assertSame(2, AiChatChannel::query()->where('provider', 'telegram')->count());
    }

    public function test_external_client_reply_is_sent_to_the_linked_telegram_chat(): void
    {
        $conversation = $this->conversation();
        $conversation->channels()->create([
            'provider' => 'telegram',
            'external_chat_id' => '67890',
        ]);

        $this->withToken('test-chat-api-token')
            ->postJson("/api/v1/support/chat/conversations/{$conversation->id}/reply", [
                'message' => 'Your order is being prepared.',
            ])
            ->assertCreated()
            ->assertJson([
                'telegram_delivered' => true,
                'telegram_message_id' => 99,
            ]);

        Http::assertSent(function (HttpRequest $request): bool {
            return $request->url() === 'https://api.telegram.org/bottest-telegram-token/sendMessage'
                && $request->data()['chat_id'] === '67890'
                && $request->data()['text'] === 'Your order is being prepared.';
        });

        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'admin',
            'content' => 'Your order is being prepared.',
        ]);
    }

    public function test_webhook_requires_telegram_secret(): void
    {
        $this->postJson('/api/telegram/webhook', [
            'update_id' => 1,
            'message' => [
                'chat' => ['id' => 1],
                'text' => 'Hello',
            ],
        ])->assertForbidden();
    }

    private function conversation(): AiChatConversation
    {
        return AiChatConversation::create([
            'session_id' => (string) Str::uuid(),
            'mode' => 'human',
        ]);
    }
}
