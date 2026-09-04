<?php

namespace Tests\Feature;

use App\Services\FeishuBotService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class FeishuBotServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.feishu.app_id', 'cli_test-app');
        config()->set('services.feishu.app_secret', 'test-app-secret');
        config()->set('services.feishu.support_open_ids', ['ou_zhangsan', 'ou_lisi']);
        config()->set('services.feishu.timeout', 10);
        config()->set('services.feishu.base_url', 'https://open.feishu.cn/open-apis');
        Cache::flush();
    }

    public function test_tenant_access_tokens_are_cached(): void
    {
        Http::fake([
            'https://open.feishu.cn/open-apis/auth/v3/tenant_access_token/internal' => Http::response([
                'code' => 0,
                'msg' => 'ok',
                'tenant_access_token' => 'tenant-access-token',
                'expire' => 7200,
            ]),
        ]);

        $service = new FeishuBotService;

        $this->assertSame('tenant-access-token', $service->tenantAccessToken());
        $this->assertSame('tenant-access-token', $service->tenantAccessToken());

        Http::assertSentCount(1);
        Http::assertSent(function (HttpRequest $request): bool {
            return $request->url() === 'https://open.feishu.cn/open-apis/auth/v3/tenant_access_token/internal'
                && $request->data() === [
                    'app_id' => 'cli_test-app',
                    'app_secret' => 'test-app-secret',
                ];
        });
    }

    public function test_it_sends_a_text_message_to_one_open_id(): void
    {
        $this->fakeSuccessfulResponses();

        (new FeishuBotService)->sendText('ou_zhangsan', 'Website support #42');

        Http::assertSent(function (HttpRequest $request): bool {
            return str_starts_with(
                $request->url(),
                'https://open.feishu.cn/open-apis/im/v1/messages?receive_id_type=open_id',
            )
                && $request->hasHeader('Authorization', 'Bearer tenant-access-token')
                && ($request->data()['receive_id'] ?? null) === 'ou_zhangsan'
                && ($request->data()['msg_type'] ?? null) === 'text'
                && json_decode((string) ($request->data()['content'] ?? ''), true) === [
                    'text' => 'Website support #42',
                ];
        });
    }

    public function test_it_sends_an_interactive_card_with_the_reply_url(): void
    {
        $this->fakeSuccessfulResponses();

        (new FeishuBotService)->sendCard(
            'ou_zhangsan',
            '网站客服 #42',
            '客户: customer@example.com\n\nCustomer needs help.',
            'https://example.test/admin/ai-chat-conversations/42',
        );

        Http::assertSent(function (HttpRequest $request): bool {
            $card = json_decode((string) ($request->data()['content'] ?? ''), true);

            return ($request->data()['msg_type'] ?? null) === 'interactive'
                && is_array($card)
                && ($card['header']['title']['content'] ?? null) === '网站客服 #42'
                && ($card['elements'][0]['text']['content'] ?? null)
                    === '客户: customer@example.com\n\nCustomer needs help.'
                && ($card['elements'][1]['actions'][0]['text']['content'] ?? null) === '去回复'
                && ($card['elements'][1]['actions'][0]['url'] ?? null)
                    === 'https://example.test/admin/ai-chat-conversations/42';
        });
    }

    public function test_api_errors_are_exposed_as_runtime_errors(): void
    {
        Http::fake([
            'https://open.feishu.cn/open-apis/auth/v3/tenant_access_token/internal' => Http::response([
                'code' => 99991663,
                'msg' => 'invalid app credential',
            ]),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Feishu tenant_access_token failed (99991663): invalid app credential',
        );

        (new FeishuBotService)->tenantAccessToken();
    }

    private function fakeSuccessfulResponses(): void
    {
        Http::fake([
            'https://open.feishu.cn/open-apis/auth/v3/tenant_access_token/internal' => Http::response([
                'code' => 0,
                'msg' => 'ok',
                'tenant_access_token' => 'tenant-access-token',
                'expire' => 7200,
            ]),
            'https://open.feishu.cn/open-apis/im/v1/messages*' => Http::response([
                'code' => 0,
                'msg' => 'ok',
                'data' => ['message_id' => 'om-test-message'],
            ]),
        ]);
    }
}
