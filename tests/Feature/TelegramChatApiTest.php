<?php

namespace Tests\Feature;

use App\Models\AiChatChannel;
use App\Models\AiChatConversation;
use App\Models\AiChatTelegramMessage;
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
        config()->set('aichat.enabled', true);
        config()->set('services.telegram.bot_token', 'test-telegram-token');
        config()->set('services.telegram.bot_username', 'inkpavo_support_bot');
        config()->set('services.telegram.webhook_secret', 'test-webhook-secret');
        config()->set('services.telegram.support_chat_id', '-1001234567890');
        config()->set('services.telegram.support_user_ids', ['777']);

        Http::fake([
            'https://api.telegram.org/bottest-telegram-token/*' => function (HttpRequest $request) {
                static $supportMessageId = 98;
                $messageId = $request->data()['chat_id'] === '-1001234567890'
                    ? ++$supportMessageId
                    : 99;

                return Http::response([
                    'ok' => true,
                    'result' => ['message_id' => $messageId],
                ]);
            },
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

    public function test_human_website_message_is_sent_to_the_telegram_support_chat(): void
    {
        $conversation = $this->conversation();
        $conversationId = $conversation->id;

        $this->postJson('/ai/chat/message', [
            'session_id' => $conversation->session_id,
            'message' => 'I need help with my order.',
        ])->assertCreated();

        Http::assertSent(function (HttpRequest $request) use ($conversationId): bool {
            return $request->url() === 'https://api.telegram.org/bottest-telegram-token/sendMessage'
                && $request->data()['chat_id'] === '-1001234567890'
                && str_contains($request->data()['text'], "Website support #{$conversationId}")
                && str_contains($request->data()['text'], 'I need help with my order.');
        });

        $this->assertDatabaseHas('ai_chat_telegram_messages', [
            'conversation_id' => $conversation->id,
            'ai_chat_message_id' => $conversation->messages()->latest('id')->value('id'),
            'telegram_chat_id' => '-1001234567890',
            'telegram_message_id' => 99,
            'direction' => 'notification',
        ]);
    }

    public function test_operator_reply_is_written_to_the_matching_website_conversation(): void
    {
        $first = $this->conversation();
        $second = $this->conversation();

        $this->postJson('/ai/chat/message', [
            'session_id' => $first->session_id,
            'message' => 'Message for the first customer.',
        ])->assertCreated();
        $this->postJson('/ai/chat/message', [
            'session_id' => $second->session_id,
            'message' => 'Message for the second customer.',
        ])->assertCreated();

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-webhook-secret')
            ->postJson('/api/telegram/webhook', [
                'update_id' => 400,
                'message' => [
                    'message_id' => 900,
                    'from' => ['id' => 777, 'username' => 'agent'],
                    'chat' => ['id' => -1001234567890, 'type' => 'supergroup'],
                    'text' => 'We are checking this for you.',
                    'reply_to_message' => [
                        'message_id' => 100,
                        'from' => ['id' => 999, 'is_bot' => true],
                        'chat' => ['id' => -1001234567890, 'type' => 'supergroup'],
                        'text' => 'Message for the second customer.',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $second->id,
            'role' => 'admin',
            'content' => 'We are checking this for you.',
        ]);
        $this->assertDatabaseMissing('ai_chat_messages', [
            'conversation_id' => $first->id,
            'role' => 'admin',
            'content' => 'We are checking this for you.',
        ]);
        $this->assertDatabaseHas('ai_chat_telegram_messages', [
            'conversation_id' => $second->id,
            'telegram_update_id' => 400,
            'telegram_message_id' => 900,
            'direction' => 'operator_reply',
        ]);
    }

    public function test_duplicate_operator_update_is_not_added_twice(): void
    {
        $conversation = $this->conversation();

        $this->postJson('/ai/chat/message', [
            'session_id' => $conversation->session_id,
            'message' => 'Please check this.',
        ])->assertCreated();

        $update = [
            'update_id' => 401,
            'message' => [
                'message_id' => 901,
                'from' => ['id' => 777],
                'chat' => ['id' => -1001234567890, 'type' => 'supergroup'],
                'text' => 'We are checking this.',
                'reply_to_message' => ['message_id' => 99],
            ],
        ];

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-webhook-secret')
            ->postJson('/api/telegram/webhook', $update)
            ->assertOk();
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-webhook-secret')
            ->postJson('/api/telegram/webhook', $update)
            ->assertOk();

        $this->assertSame(1, $conversation->messages()->where('role', 'admin')->count());
        $this->assertSame(1, AiChatTelegramMessage::query()->where('telegram_update_id', 401)->count());
    }

    public function test_unauthorized_telegram_user_cannot_reply_to_a_support_notification(): void
    {
        $conversation = $this->conversation();

        $this->postJson('/ai/chat/message', [
            'session_id' => $conversation->session_id,
            'message' => 'Please check this.',
        ])->assertCreated();

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-webhook-secret')
            ->postJson('/api/telegram/webhook', [
                'update_id' => 402,
                'message' => [
                    'message_id' => 902,
                    'from' => ['id' => 888],
                    'chat' => ['id' => -1001234567890, 'type' => 'supergroup'],
                    'text' => 'Unauthorized reply.',
                    'reply_to_message' => ['message_id' => 99],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseMissing('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'admin',
            'content' => 'Unauthorized reply.',
        ]);
    }

    public function test_ai_mode_message_is_not_sent_to_the_operator_chat(): void
    {
        $conversation = AiChatConversation::create([
            'session_id' => (string) Str::uuid(),
            'mode' => 'ai',
        ]);

        $this->postJson('/ai/chat/message', [
            'session_id' => $conversation->session_id,
            'message' => 'This should stay in AI mode.',
        ])->assertCreated();

        $this->assertDatabaseMissing('ai_chat_telegram_messages', [
            'conversation_id' => $conversation->id,
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
