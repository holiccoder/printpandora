<?php

namespace Tests\Feature;

use App\Models\AiChatConversation;
use App\Models\AiChatFeishuMessage;
use App\Services\FeishuSupportBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class FeishuSupportBridgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('aichat.translation.enabled', false);
        config()->set('services.feishu.app_id', 'cli_test-app');
        config()->set('services.feishu.app_secret', 'test-app-secret');
        config()->set('services.feishu.support_open_ids', ['ou_zhangsan', 'ou_lisi']);
        config()->set('services.feishu.timeout', 10);
        config()->set('services.feishu.base_url', 'https://open.feishu.cn/open-apis');
        config()->set('app.url', 'https://example.test');
        Cache::flush();
    }

    public function test_unconfigured_bridge_does_not_send_or_write_a_notification(): void
    {
        config()->set('services.feishu.app_secret', null);
        Http::fake();

        $conversation = $this->conversation();
        $message = $conversation->messages()->create([
            'role' => 'user',
            'content' => 'I need help.',
        ]);

        $this->bridge()->notifyCustomerMessage($conversation, $message);

        Http::assertNothingSent();
        $this->assertDatabaseCount('ai_chat_feishu_messages', 0);
    }

    public function test_notification_sends_text_and_card_to_each_support_user(): void
    {
        $this->fakeSuccessfulResponses();
        $conversation = $this->conversation();
        $message = $conversation->messages()->create([
            'role' => 'user',
            'content' => 'I need help.',
        ]);

        $this->bridge()->notifyCustomerMessage($conversation, $message);

        $this->assertDatabaseHas('ai_chat_feishu_messages', [
            'conversation_id' => $conversation->id,
            'ai_chat_message_id' => $message->id,
            'feishu_open_id' => null,
            'message_id' => null,
            'direction' => AiChatFeishuMessage::NOTIFICATION,
        ]);
        $this->assertSame(
            $conversation->id,
            Cache::get('feishu:active_conversation:ou_zhangsan'),
        );
        $this->assertSame(
            $conversation->id,
            Cache::get('feishu:active_conversation:ou_lisi'),
        );

        Http::assertSentCount(5);
        Http::assertSent(function (HttpRequest $request): bool {
            return ($request->data()['msg_type'] ?? null) === 'text'
                && str_contains(
                    (string) json_decode((string) ($request->data()['content'] ?? ''), true)['text'],
                    '客服通知 #',
                );
        });
        Http::assertSent(function (HttpRequest $request): bool {
            return ($request->data()['msg_type'] ?? null) === 'interactive'
                && str_contains(
                    (string) ($request->data()['receive_id'] ?? ''),
                    'ou_',
                );
        });
    }

    public function test_duplicate_notifications_are_skipped(): void
    {
        $this->fakeSuccessfulResponses();
        $conversation = $this->conversation();
        $message = $conversation->messages()->create([
            'role' => 'user',
            'content' => 'I need help.',
        ]);
        $bridge = $this->bridge();

        $bridge->notifyCustomerMessage($conversation, $message);
        $bridge->notifyCustomerMessage($conversation, $message);

        $this->assertDatabaseCount('ai_chat_feishu_messages', 1);
        Http::assertSentCount(5);
    }

    public function test_operator_can_route_a_reply_with_a_conversation_prefix(): void
    {
        $this->fakeSuccessfulResponses();
        $conversation = $this->conversation('ai');

        $this->bridge()->handleOperatorMessage(
            'ou_zhangsan',
            'om-reply-1',
            '#'.$conversation->id.' Please check my order.',
        );

        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'admin',
            'content' => 'Please check my order.',
        ]);
        $this->assertDatabaseHas('ai_chat_feishu_messages', [
            'conversation_id' => $conversation->id,
            'feishu_open_id' => 'ou_zhangsan',
            'message_id' => 'om-reply-1',
            'direction' => AiChatFeishuMessage::OPERATOR_REPLY,
        ]);
        $this->assertSame('human', $conversation->refresh()->mode);
        $this->assertNotNull($conversation->human_requested_at);
        $this->assertSame(
            $conversation->id,
            Cache::get('feishu:active_conversation:ou_zhangsan'),
        );
        $this->assertConfirmationSent($conversation->id);
    }

    public function test_operator_can_route_a_reply_to_the_active_conversation_without_a_prefix(): void
    {
        $this->fakeSuccessfulResponses();
        $conversation = $this->conversation('human');
        Cache::put('feishu:active_conversation:ou_zhangsan', $conversation->id, now()->addDays(7));

        $this->bridge()->handleOperatorMessage('ou_zhangsan', 'om-reply-2', 'Following up.');

        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'admin',
            'content' => 'Following up.',
        ]);
        $this->assertConfirmationSent($conversation->id);
    }

    public function test_operator_without_an_active_conversation_receives_routing_instructions(): void
    {
        $this->fakeSuccessfulResponses();

        $this->bridge()->handleOperatorMessage('ou_zhangsan', 'om-reply-3', 'Hello.');

        $this->assertDatabaseCount('ai_chat_messages', 0);
        Http::assertSent(function (HttpRequest $request): bool {
            return ($request->data()['msg_type'] ?? null) === 'text'
                && json_decode((string) ($request->data()['content'] ?? ''), true)['text']
                    === '请用「#会话ID 内容」指定要回复的会话。';
        });
    }

    public function test_operator_reply_to_an_unknown_conversation_receives_routing_instructions(): void
    {
        $this->fakeSuccessfulResponses();

        $this->bridge()->handleOperatorMessage('ou_zhangsan', 'om-reply-4', '#9999 Hello.');

        $this->assertDatabaseCount('ai_chat_messages', 0);
        $this->assertDatabaseCount('ai_chat_feishu_messages', 0);
        Http::assertSent(function (HttpRequest $request): bool {
            return ($request->data()['msg_type'] ?? null) === 'text'
                && json_decode((string) ($request->data()['content'] ?? ''), true)['text']
                    === '请用「#会话ID 内容」指定要回复的会话。';
        });
    }

    public function test_non_support_users_are_ignored(): void
    {
        Http::fake();

        $conversation = $this->conversation();
        $this->bridge()->handleOperatorMessage(
            'ou_outsider',
            'om-reply-5',
            '#'.$conversation->id.' Hi.',
        );

        $this->assertDatabaseCount('ai_chat_messages', 0);
        $this->assertDatabaseCount('ai_chat_feishu_messages', 0);
        Http::assertNothingSent();
    }

    public function test_duplicate_operator_message_ids_are_idempotent(): void
    {
        $this->fakeSuccessfulResponses();
        $conversation = $this->conversation();
        $bridge = $this->bridge();

        $bridge->handleOperatorMessage(
            'ou_zhangsan',
            'om-reply-6',
            '#'.$conversation->id.' First reply.',
        );
        $bridge->handleOperatorMessage(
            'ou_zhangsan',
            'om-reply-6',
            '#'.$conversation->id.' Duplicate reply.',
        );

        $this->assertSame(1, $conversation->messages()->where('role', 'admin')->count());
        $this->assertDatabaseCount('ai_chat_feishu_messages', 1);
        Http::assertSentCount(2);
    }

    private function bridge(): FeishuSupportBridge
    {
        return $this->app->make(FeishuSupportBridge::class);
    }

    private function conversation(string $mode = 'human'): AiChatConversation
    {
        return AiChatConversation::create([
            'session_id' => (string) Str::uuid(),
            'mode' => $mode,
        ]);
    }

    private function fakeSuccessfulResponses(): void
    {
        Http::fake([
            'https://open.feishu.cn/open-apis/auth/v3/tenant_access_token/internal' => Http::response([
                'code' => 0,
                'tenant_access_token' => 'tenant-access-token',
                'expire' => 7200,
            ]),
            'https://open.feishu.cn/open-apis/im/v1/messages*' => Http::response([
                'code' => 0,
                'msg' => 'ok',
            ]),
        ]);
    }

    private function assertConfirmationSent(int $conversationId): void
    {
        Http::assertSent(function (HttpRequest $request) use ($conversationId): bool {
            return ($request->data()['msg_type'] ?? null) === 'text'
                && ($request->data()['receive_id'] ?? null) === 'ou_zhangsan'
                && (json_decode((string) ($request->data()['content'] ?? ''), true)['text'] ?? null)
                    === "已发送到会话 #{$conversationId}";
        });
    }
}
