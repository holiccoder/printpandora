<?php

namespace Tests\Feature;

use App\Services\WeComAppService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class WeComAppServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.wecom.corp_id', 'ww-test-corp');
        config()->set('services.wecom.app_agent_id', '1000002');
        config()->set('services.wecom.app_secret', 'test-app-secret');
        config()->set('services.wecom.app_support_user_ids', ['zhangsan', 'lisi']);
        config()->set('services.wecom.timeout', 10);
        Cache::flush();
    }

    public function test_access_tokens_are_cached_for_the_app_secret(): void
    {
        Http::fake([
            'https://qyapi.weixin.qq.com/cgi-bin/gettoken*' => Http::response([
                'errcode' => 0,
                'access_token' => 'app-access-token',
                'expires_in' => 7200,
            ]),
        ]);

        $service = new WeComAppService;

        $this->assertSame('app-access-token', $service->accessToken());
        $this->assertSame('app-access-token', $service->accessToken());

        Http::assertSentCount(1);
        Http::assertSent(function (HttpRequest $request): bool {
            return $request->url() === 'https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=ww-test-corp&corpsecret=test-app-secret';
        });
    }

    public function test_it_sends_text_to_all_configured_support_users(): void
    {
        $this->fakeSuccessfulResponses();

        $result = (new WeComAppService)->sendText('zhangsan|lisi', 'Website support #42');

        $this->assertSame('app-message-1', $result['msgid']);
        Http::assertSent(function (HttpRequest $request): bool {
            return str_starts_with(
                $request->url(),
                'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=app-access-token',
            ) && $request->data() === [
                'touser' => 'zhangsan|lisi',
                'msgtype' => 'text',
                'agentid' => 1000002,
                'text' => ['content' => 'Website support #42'],
            ];
        });
    }

    public function test_it_sends_a_text_card_with_the_reply_url(): void
    {
        $this->fakeSuccessfulResponses();

        (new WeComAppService)->sendTextCard(
            'zhangsan|lisi',
            'Website support #42',
            'Customer needs help.',
            'https://example.test/admin/ai-chat-conversations/42',
        );

        Http::assertSent(function (HttpRequest $request): bool {
            return $request->data() === [
                'touser' => 'zhangsan|lisi',
                'msgtype' => 'textcard',
                'agentid' => 1000002,
                'textcard' => [
                    'title' => 'Website support #42',
                    'description' => 'Customer needs help.',
                    'url' => 'https://example.test/admin/ai-chat-conversations/42',
                    'btntxt' => '去回复',
                ],
            ];
        });
    }

    public function test_api_errors_are_exposed_as_runtime_errors(): void
    {
        Http::fake([
            'https://qyapi.weixin.qq.com/cgi-bin/gettoken*' => Http::response([
                'errcode' => 0,
                'access_token' => 'app-access-token',
                'expires_in' => 7200,
            ]),
            'https://qyapi.weixin.qq.com/cgi-bin/message/send*' => Http::response([
                'errcode' => 40014,
                'errmsg' => 'invalid access_token',
            ]),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('WeCom message/send failed (40014): invalid access_token');

        (new WeComAppService)->sendText('zhangsan', 'Hello.');
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
}
