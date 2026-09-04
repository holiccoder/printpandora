<?php

namespace Tests\Feature;

use App\Jobs\HandleFeishuOperatorMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FeishuCallbackTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'feishu-verification-token';

    private const ENCRYPT_KEY = 'feishu-encrypt-key';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.feishu.verification_token', self::TOKEN);
        config()->set('services.feishu.encrypt_key', null);
    }

    public function test_unencrypted_url_verification_returns_the_challenge(): void
    {
        $this->postJson('/api/feishu/callback', [
            'type' => 'url_verification',
            'challenge' => 'challenge-value',
            'token' => self::TOKEN,
        ])
            ->assertOk()
            ->assertExactJson(['challenge' => 'challenge-value']);
    }

    public function test_encrypted_url_verification_is_decrypted_and_returns_the_challenge(): void
    {
        config()->set('services.feishu.encrypt_key', self::ENCRYPT_KEY);
        $callback = $this->encryptedCallback([
            'type' => 'url_verification',
            'challenge' => 'encrypted-challenge',
            'token' => self::TOKEN,
        ]);

        $this->call(
            'POST',
            '/api/feishu/callback',
            [],
            [],
            [],
            $callback['headers'],
            $callback['body'],
        )
            ->assertOk()
            ->assertExactJson(['challenge' => 'encrypted-challenge']);
    }

    public function test_invalid_verification_token_is_rejected(): void
    {
        $this->postJson('/api/feishu/callback', [
            'type' => 'url_verification',
            'challenge' => 'challenge-value',
            'token' => 'wrong-token',
        ])->assertForbidden();
    }

    public function test_invalid_encrypted_signature_is_rejected(): void
    {
        config()->set('services.feishu.encrypt_key', self::ENCRYPT_KEY);
        $callback = $this->encryptedCallback([
            'type' => 'url_verification',
            'challenge' => 'challenge-value',
            'token' => self::TOKEN,
        ]);
        $callback['headers']['HTTP_X_LARK_SIGNATURE'] = 'invalid-signature';

        $this->call(
            'POST',
            '/api/feishu/callback',
            [],
            [],
            [],
            $callback['headers'],
            $callback['body'],
        )->assertForbidden();
    }

    public function test_normal_text_event_is_queued(): void
    {
        Queue::fake();

        $this->postJson('/api/feishu/callback', $this->textEvent())
            ->assertOk()
            ->assertExactJson(['code' => 0]);

        Queue::assertPushed(HandleFeishuOperatorMessage::class, function (
            HandleFeishuOperatorMessage $job,
        ): bool {
            return $job->openId === 'ou_zhangsan'
                && $job->messageId === 'om-message-42'
                && $job->text === '#42 Please check this.';
        });
    }

    public function test_non_text_messages_are_acknowledged_without_being_queued(): void
    {
        Queue::fake();
        $event = $this->textEvent();
        $event['event']['message']['message_type'] = 'image';

        $this->postJson('/api/feishu/callback', $event)
            ->assertOk()
            ->assertExactJson(['code' => 0]);

        Queue::assertNothingPushed();
    }

    public function test_messages_sent_by_the_bot_are_ignored(): void
    {
        Queue::fake();
        $event = $this->textEvent();
        $event['event']['sender']['sender_type'] = 'app';

        $this->postJson('/api/feishu/callback', $event)
            ->assertOk()
            ->assertExactJson(['code' => 0]);

        Queue::assertNothingPushed();
    }

    public function test_get_requests_are_not_accepted(): void
    {
        $this->get('/api/feishu/callback')->assertNotFound();
    }

    /** @return array<string, mixed> */
    private function textEvent(): array
    {
        return [
            'schema' => '2.0',
            'header' => [
                'event_type' => 'im.message.receive_v1',
            ],
            'event' => [
                'sender' => [
                    'sender_id' => [
                        'open_id' => 'ou_zhangsan',
                    ],
                    'sender_type' => 'user',
                ],
                'message' => [
                    'message_id' => 'om-message-42',
                    'message_type' => 'text',
                    'content' => json_encode(['text' => '#42 Please check this.']),
                ],
            ],
            'token' => self::TOKEN,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{body: string, headers: array<string, string>}
     */
    private function encryptedCallback(array $payload): array
    {
        $plainText = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
        $key = hash('sha256', self::ENCRYPT_KEY, true);
        $cipherText = openssl_encrypt(
            $plainText,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            substr($key, 0, 16),
        );

        self::assertIsString($cipherText);

        $body = json_encode([
            'encrypt' => base64_encode($cipherText),
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestamp = '1710000000';
        $nonce = 'nonce-value';

        return [
            'body' => $body,
            'headers' => [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_LARK_REQUEST_TIMESTAMP' => $timestamp,
                'HTTP_X_LARK_REQUEST_NONCE' => $nonce,
                'HTTP_X_LARK_SIGNATURE' => hash(
                    'sha256',
                    $timestamp.$nonce.self::ENCRYPT_KEY.$body,
                ),
            ],
        ];
    }
}
