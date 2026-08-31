<?php

namespace Tests\Feature;

use App\Services\WeComKfService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class WeComKfServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.wecom.corp_id', 'ww-test-corp');
        config()->set('services.wecom.kf_secret', 'test-kf-secret');
        config()->set('services.wecom.open_kfid', 'wk-test');
        config()->set('services.wecom.timeout', 10);
        Cache::forget('wecom:kf:access_token');

    }

    private function fakeSuccessfulApiResponses(): void
    {
        Http::fake([
            'https://qyapi.weixin.qq.com/cgi-bin/gettoken*' => Http::response([
                'errcode' => 0,
                'errmsg' => 'ok',
                'access_token' => 'test-access-token',
                'expires_in' => 7200,
            ]),
            'https://qyapi.weixin.qq.com/cgi-bin/kf/sync_msg*' => Http::response([
                'errcode' => 0,
                'errmsg' => 'ok',
                'next_cursor' => 'next-cursor',
                'has_more' => 0,
                'msg_list' => [],
            ]),
            'https://qyapi.weixin.qq.com/cgi-bin/kf/send_msg*' => Http::response([
                'errcode' => 0,
                'errmsg' => 'ok',
                'msgid' => 'wecom-message-1',
            ]),
        ]);
    }

    public function test_access_tokens_are_cached_until_their_expiry_window(): void
    {
        $this->fakeSuccessfulApiResponses();
        $service = new WeComKfService;

        $this->assertSame('test-access-token', $service->accessToken());
        $this->assertSame('test-access-token', $service->accessToken());

        Http::assertSentCount(1);
        Http::assertSent(function (HttpRequest $request): bool {
            return str_starts_with(
                $request->url(),
                'https://qyapi.weixin.qq.com/cgi-bin/gettoken?access_token=',
            ) === false
                && $request->url() === 'https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=ww-test-corp&corpsecret=test-kf-secret';
        });
    }

    public function test_it_reads_messages_with_the_callback_token_and_cursor(): void
    {
        $this->fakeSuccessfulApiResponses();
        $result = (new WeComKfService)->syncMessages('old-cursor', 'callback-token');

        $this->assertSame('next-cursor', $result['next_cursor']);
        $this->assertSame(0, $result['has_more']);

        Http::assertSent(function (HttpRequest $request): bool {
            return str_starts_with($request->url(), 'https://qyapi.weixin.qq.com/cgi-bin/kf/sync_msg')
                && $request->data() === [
                    'limit' => 1000,
                    'cursor' => 'old-cursor',
                    'token' => 'callback-token',
                    'open_kfid' => 'wk-test',
                ];
        });
    }

    public function test_it_sends_a_text_message_to_a_wecom_customer(): void
    {
        $this->fakeSuccessfulApiResponses();
        $result = (new WeComKfService)->sendMessage('wm-customer', 'Hello from support.');

        $this->assertSame('wecom-message-1', $result['msgid']);

        Http::assertSent(function (HttpRequest $request): bool {
            return str_starts_with($request->url(), 'https://qyapi.weixin.qq.com/cgi-bin/kf/send_msg')
                && $request->data() === [
                    'touser' => 'wm-customer',
                    'open_kfid' => 'wk-test',
                    'msgtype' => 'text',
                    'text' => ['content' => 'Hello from support.'],
                ];
        });
    }

    public function test_api_errors_are_exposed_as_runtime_errors(): void
    {
        Cache::flush();
        Http::fake(fn (HttpRequest $request) => Http::response([
            'errcode' => 40013,
            'errmsg' => 'invalid corpid',
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('WeCom gettoken failed (40013): invalid corpid');

        (new WeComKfService)->accessToken();
    }
}
