<?php

namespace Tests\Feature;

use App\Models\AiChatConversation;
use App\Models\AiChatWecomAppMessage;
use App\Services\WeComSupportBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class WeComSupportBridgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('aichat.translation.enabled', false);
        config()->set('services.wecom.corp_id', 'ww-test-corp');
        config()->set('services.wecom.app_agent_id', '1000002');
        config()->set('services.wecom.app_secret', 'test-app-secret');
        config()->set('services.wecom.app_support_user_ids', ['zhangsan', 'lisi']);
        config()->set('services.wecom.timeout', 10);
        config()->set('app.url', 'https://example.test');
        Cache::flush();
    }

    public function test_unconfigured_bridge_does_not_send_or_write_a_notification(): void
    {
        config()->set('services.wecom.app_secret', null);
        Http::fake();

        $conversation = $this->conversation();
        $message = $conversation->messages()->create([
            'role' => 'user',
            'content' => 'I need help.',
        ]);

        $this->bridge()->notifyCustomerMessage($conversation, $message);

        Http::assertNothingSent();
        $this->assertDatabaseCount('ai_chat_wecom_app_messages', 0);
    }

    public function test_notification_sends_text_and_card_writes_a_mapping_and_refreshes_active_sessions(): void
    {
        $this->fakeSuccessfulResponses();
        $conversation = $this->conversation();
        $message = $conversation->messages()->create([
            'role' => 'user',
            'content' => 'I need help.',
        ]);

        $this->bridge()->notifyCustomerMessage($conversation, $message);

        $this->assertDatabaseHas('ai_chat_wecom_app_messages', [
            'conversation_id' => $conversation->id,
            'ai_chat_message_id' => $message->id,
            'wecom_userid' => null,
            'msgid' => null,
            'direction' => AiChatWecomAppMessage::NOTIFICATION,
        ]);
        $this->assertSame(
            $conversation->id,
            Cache::get('wecom:app:active_conversation:zhangsan'),
        );
        $this->assertSame(
            $conversation->id,
            Cache::get('wecom:app:active_conversation:lisi'),
        );

        Http::assertSentCount(3);
        Http::assertSent(function (HttpRequest $request): bool {
            return $request->data()['msgtype'] ?? null === 'text'
                && str_contains((string) ($request->data()['text']['content'] ?? ''), '客服通知 #');
        });
        Http::assertSent(function (HttpRequest $request): bool {
            return ($request->data()['msgtype'] ?? null) === 'textcard'
                && str_contains((string) ($request->data()['textcard']['url'] ?? ''), '/admin/');
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

        $this->assertDatabaseCount('ai_chat_wecom_app_messages', 1);
        Http::assertSentCount(3);
    }

    public function test_operator_can_route_a_reply_with_a_conversation_prefix(): void
    {
        $this->fakeSuccessfulResponses();
        $conversation = $this->conversation('ai');

        $this->bridge()->handleOperatorMessage(
            'zhangsan',
            'wecom-msg-1',
            '#'.$conversation->id.' Please check my order.',
        );

        $this->assertDatabaseHas('ai_chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'admin',
            'content' => 'Please check my order.',
        ]);
        $this->assertDatabaseHas('ai_chat_wecom_app_messages', [
            'conversation_id' => $conversation->id,
            'wecom_userid' => 'zhangsan',
            'msgid' => 'wecom-msg-1',
            'direction' => AiChatWecomAppMessage::OPERATOR_REPLY,
        ]);
        $this->assertSame('human', $conversation->refresh()->mode);
        $this->assertNotNull($conversation->human_requested_at);
        $this->assertSame(
            $conversation->id,
            Cache::get('wecom:app:active_conversation:zhangsan'),
        );
        $this->assertConfirmationSent($conversation->id);
    }

    public function test_operator_can_route_a_reply_to_the_active_conversation_without_a_prefix(): void
    {
        $this->fakeSuccessfulResponses();
        $conversation = $this->conversation('human');
        Cache::put('wecom:app:active_conversation:zhangsan', $conversation->id, now()->addDays(7));

        $this->bridge()->handleOperatorMessage('zhangsan', 'wecom-msg-2', 'Following up.');

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

        $this->bridge()->handleOperatorMessage('zhangsan', 'wecom-msg-3', 'Hello.');

        $this->assertDatabaseCount('ai_chat_messages', 0);
        Http::assertSent(function (HttpRequest $request): bool {
            return ($request->data()['msgtype'] ?? null) === 'text'
                && ($request->data()['text']['content'] ?? null)
                    === '请用「#会话ID 内容」指定要回复的会话。';
        });
    }

    public function test_non_support_users_are_ignored(): void
    {
        Http::fake();

        $conversation = $this->conversation();
        $this->bridge()->handleOperatorMessage('outsider', 'wecom-msg-4', '#'.$conversation->id.' Hi.');

        $this->assertDatabaseCount('ai_chat_messages', 0);
        $this->assertDatabaseCount('ai_chat_wecom_app_messages', 0);
        Http::assertNothingSent();
    }

    public function test_duplicate_operator_msgids_are_idempotent(): void
    {
        $this->fakeSuccessfulResponses();
        $conversation = $this->conversation();
        $bridge = $this->bridge();

        $bridge->handleOperatorMessage('zhangsan', 'wecom-msg-5', '#'.$conversation->id.' First reply.');
        $bridge->handleOperatorMessage('zhangsan', 'wecom-msg-5', '#'.$conversation->id.' Duplicate reply.');

        $this->assertSame(1, $conversation->messages()->where('role', 'admin')->count());
        $this->assertDatabaseCount('ai_chat_wecom_app_messages', 1);
        Http::assertSentCount(2);
    }

    private function bridge(): WeComSupportBridge
    {
        return $this->app->make(WeComSupportBridge::class);
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
            'https://qyapi.weixin.qq.com/cgi-bin/gettoken*' => Http::response([
                'errcode' => 0,
                'access_token' => 'app-access-token',
                'expires_in' => 7200,
            ]),
            'https://qyapi.weixin.qq.com/cgi-bin/message/send*' => Http::response([
                'errcode' => 0,
                'errmsg' => 'ok',
                'msgid' => 'app-message-1',
            ]),
        ]);
    }

    private function assertConfirmationSent(int $conversationId): void
    {
        Http::assertSent(function (HttpRequest $request) use ($conversationId): bool {
            return ($request->data()['msgtype'] ?? null) === 'text'
                && ($request->data()['touser'] ?? null) === 'zhangsan'
                && ($request->data()['text']['content'] ?? null)
                    === "已发送到会话 #{$conversationId}";
        });
    }
}
