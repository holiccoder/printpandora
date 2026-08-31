<?php

namespace Tests\Feature;

use App\Jobs\SyncWeComKfMessages;
use App\Support\WeCom\WeComCrypt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WeComKfCallbackTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'callback-token';

    private const AES_KEY = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.wecom.corp_id', 'ww-test-corp');
        config()->set('services.wecom.callback_token', self::TOKEN);
        config()->set('services.wecom.encoding_aes_key', self::AES_KEY);
        config()->set('services.wecom.open_kfid', 'wk-default');
    }

    public function test_get_validation_decrypts_and_returns_the_echo_string(): void
    {
        $encrypted = (new WeComCrypt('ww-test-corp'))->encrypt('echo-value', self::AES_KEY);
        $query = $this->signedQuery($encrypted);

        $this->get('/api/wecom/kf/callback?'.http_build_query($query))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
            ->assertSeeText('echo-value');
    }

    public function test_post_with_an_invalid_signature_is_rejected(): void
    {
        Queue::fake();
        $encrypted = $this->encryptedCallback();

        $this->postXml(
            $this->signedQuery($encrypted, 'invalid-signature'),
            $this->callbackEnvelope($encrypted),
        )->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_valid_message_event_is_acked_and_queued_for_synchronization(): void
    {
        Queue::fake();
        $encrypted = $this->encryptedCallback();

        $this->postXml(
            $this->signedQuery($encrypted),
            $this->callbackEnvelope($encrypted),
        )
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
            ->assertSeeText('success');

        Queue::assertPushed(SyncWeComKfMessages::class, function (SyncWeComKfMessages $job): bool {
            return $job->token === 'pull-token'
                && $job->openKfId === 'wk-callback';
        });
    }

    private function encryptedCallback(): string
    {
        return (new WeComCrypt('ww-test-corp'))->encrypt(<<<'XML'
<xml>
    <MsgType><![CDATA[event]]></MsgType>
    <Event><![CDATA[kf_msg_or_event]]></Event>
    <Token><![CDATA[pull-token]]></Token>
    <OpenKfId><![CDATA[wk-callback]]></OpenKfId>
</xml>
XML, self::AES_KEY);
    }

    /** @return array<string, string> */
    private function signedQuery(string $encrypted, ?string $signature = null): array
    {
        $values = [self::TOKEN, '1710000000', 'nonce-value', $encrypted];
        sort($values, SORT_STRING);

        return [
            'msg_signature' => $signature ?? sha1(implode('', $values)),
            'timestamp' => '1710000000',
            'nonce' => 'nonce-value',
            'echostr' => $encrypted,
        ];
    }

    private function callbackEnvelope(string $encrypted): string
    {
        return '<xml><Encrypt><![CDATA['.$encrypted.']]></Encrypt></xml>';
    }

    /** @param array<string, string> $query */
    private function postXml(array $query, string $body)
    {
        return $this->call(
            'POST',
            '/api/wecom/kf/callback?'.http_build_query($query),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/xml'],
            $body,
        );
    }
}
