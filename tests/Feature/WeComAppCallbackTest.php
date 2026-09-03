<?php

namespace Tests\Feature;

use App\Jobs\HandleWeComAppOperatorMessage;
use App\Support\WeCom\WeComCrypt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WeComAppCallbackTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'app-callback-token';

    private const AES_KEY = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.wecom.corp_id', 'ww-test-corp');
        config()->set('services.wecom.app_agent_id', '1000002');
        config()->set('services.wecom.app_callback_token', self::TOKEN);
        config()->set('services.wecom.app_encoding_aes_key', self::AES_KEY);
    }

    public function test_get_validation_decrypts_and_returns_the_echo_string(): void
    {
        $encrypted = (new WeComCrypt('ww-test-corp'))->encrypt('echo-value', self::AES_KEY);

        $this->get('/api/wecom/app/callback?'.http_build_query($this->signedQuery($encrypted)))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
            ->assertSeeText('echo-value');
    }

    public function test_get_validation_with_an_invalid_signature_is_rejected(): void
    {
        $encrypted = (new WeComCrypt('ww-test-corp'))->encrypt('echo-value', self::AES_KEY);

        $this->get('/api/wecom/app/callback?'.http_build_query(
            $this->signedQuery($encrypted, 'invalid-signature'),
        ))->assertForbidden();
    }

    public function test_post_text_message_for_the_configured_agent_is_queued(): void
    {
        Queue::fake();
        $encrypted = $this->encryptedMessage(<<<'XML'
<xml>
    <ToUserName><![CDATA[ww-test-corp]]></ToUserName>
    <FromUserName><![CDATA[zhangsan]]></FromUserName>
    <MsgType><![CDATA[text]]></MsgType>
    <Content><![CDATA[#42 Please check this.]]></Content>
    <MsgId>msg-42</MsgId>
    <AgentID>1000002</AgentID>
</xml>
XML);

        $this->postXml(
            $this->signedQuery($encrypted),
            $this->callbackEnvelope($encrypted),
        )
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
            ->assertSeeText('success');

        Queue::assertPushed(HandleWeComAppOperatorMessage::class, function (
            HandleWeComAppOperatorMessage $job,
        ): bool {
            return $job->userId === 'zhangsan'
                && $job->msgId === 'msg-42'
                && $job->text === '#42 Please check this.';
        });
    }

    public function test_post_with_an_invalid_signature_is_rejected(): void
    {
        Queue::fake();
        $encrypted = $this->encryptedMessage($this->textMessageXml());

        $this->postXml(
            $this->signedQuery($encrypted, 'invalid-signature'),
            $this->callbackEnvelope($encrypted),
        )->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_non_text_messages_are_acknowledged_without_being_queued(): void
    {
        Queue::fake();
        $encrypted = $this->encryptedMessage(<<<'XML'
<xml>
    <FromUserName><![CDATA[zhangsan]]></FromUserName>
    <MsgType><![CDATA[image]]></MsgType>
    <MsgId>image-1</MsgId>
    <AgentID>1000002</AgentID>
</xml>
XML);

        $this->postXml(
            $this->signedQuery($encrypted),
            $this->callbackEnvelope($encrypted),
        )->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_messages_for_a_different_agent_are_acknowledged_without_being_queued(): void
    {
        Queue::fake();
        $encrypted = $this->encryptedMessage(str_replace(
            '1000002',
            '1000003',
            $this->textMessageXml(),
        ));

        $this->postXml(
            $this->signedQuery($encrypted),
            $this->callbackEnvelope($encrypted),
        )->assertOk();

        Queue::assertNothingPushed();
    }

    private function textMessageXml(): string
    {
        return <<< 'XML'
<xml>
    <FromUserName><![CDATA[zhangsan]]></FromUserName>
    <MsgType><![CDATA[text]]></MsgType>
    <Content><![CDATA[#42 Please check this.]]></Content>
    <MsgId>msg-42</MsgId>
    <AgentID>1000002</AgentID>
</xml>
XML;
    }

    private function encryptedMessage(string $plainText): string
    {
        return (new WeComCrypt('ww-test-corp'))->encrypt($plainText, self::AES_KEY);
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
            '/api/wecom/app/callback?'.http_build_query($query),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/xml'],
            $body,
        );
    }
}
