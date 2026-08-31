<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AiChatConversation;
use App\Services\WeComKfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class WeComKfOutboundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('aichat.api_token', 'test-chat-api-token');
        config()->set('aichat.translation.enabled', false);
        config()->set('services.wecom.corp_id', 'ww-test-corp');
        config()->set('services.wecom.kf_secret', 'test-kf-secret');
        config()->set('services.wecom.open_kfid', 'wk-test');
        Cache::flush();
        Http::fake([
            'https://qyapi.weixin.qq.com/cgi-bin/gettoken*' => Http::response([
                'errcode' => 0,
                'errmsg' => 'ok',
                'access_token' => 'test-access-token',
                'expires_in' => 7200,
            ]),
            'https://qyapi.weixin.qq.com/cgi-bin/kf/send_msg*' => Http::response([
                'errcode' => 0,
                'errmsg' => 'ok',
                'msgid' => 'wecom-outbound-1',
            ]),
        ]);
    }

    public function test_external_chat_api_replies_through_a_wecom_channel(): void
    {
        $conversation = $this->conversation();

        $this->withToken('test-chat-api-token')
            ->postJson("/api/v1/support/chat/conversations/{$conversation->id}/reply", [
                'message' => 'Your order is being prepared.',
            ])
            ->assertCreated()
            ->assertJson([
                'channel_delivered' => true,
                'channel_message_id' => 'wecom-outbound-1',
                'telegram_delivered' => false,
                'telegram_message_id' => null,
            ]);

        $this->assertSendRequest('Your order is being prepared.');
    }

    public function test_filament_admin_replies_through_a_wecom_channel(): void
    {
        $conversation = $this->conversation();
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->postJson("/ai/chat/admin/conversations/{$conversation->id}/reply", [
                'message' => 'We are checking this for you.',
            ])
            ->assertCreated()
            ->assertJson([
                'channel_delivered' => true,
                'channel_message_id' => 'wecom-outbound-1',
                'telegram_delivered' => false,
                'telegram_message_id' => null,
            ]);

        $this->assertSendRequest('We are checking this for you.');
    }

    private function conversation(): AiChatConversation
    {
        $conversation = AiChatConversation::create([
            'session_id' => (string) Str::uuid(),
            'mode' => 'human',
        ]);
        $conversation->channels()->create([
            'provider' => WeComKfService::PROVIDER,
            'external_chat_id' => 'wm-customer',
        ]);

        return $conversation;
    }

    private function assertSendRequest(string $text): void
    {
        Http::assertSent(function (HttpRequest $request) use ($text): bool {
            return str_starts_with($request->url(), 'https://qyapi.weixin.qq.com/cgi-bin/kf/send_msg')
                && $request->data()['touser'] === 'wm-customer'
                && $request->data()['text']['content'] === $text;
        });
    }
}
