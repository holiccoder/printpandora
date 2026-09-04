<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Sends messages to support staff through a Feishu enterprise self-built app.
 */
class FeishuBotService
{
    private const TENANT_ACCESS_TOKEN_CACHE_KEY = 'feishu:tenant_access_token';

    public function isConfigured(): bool
    {
        return trim((string) config('services.feishu.app_id')) !== ''
            && trim((string) config('services.feishu.app_secret')) !== ''
            && $this->supportOpenIds() !== [];
    }

    public function isSupportUser(string $openId): bool
    {
        return $openId !== '' && in_array($openId, $this->supportOpenIds(), true);
    }

    /** @return array<int, string> */
    public function supportOpenIds(): array
    {
        $openIds = config('services.feishu.support_open_ids', []);

        if (! is_array($openIds)) {
            return [];
        }

        return array_values(array_filter(array_map(
            'trim',
            array_map('strval', $openIds),
        )));
    }

    public function tenantAccessToken(): string
    {
        $cached = Cache::get(self::TENANT_ACCESS_TOKEN_CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $appId = trim((string) config('services.feishu.app_id'));
        $appSecret = trim((string) config('services.feishu.app_secret'));

        if ($appId === '' || $appSecret === '') {
            throw new RuntimeException('Feishu app credentials are not configured.');
        }

        $response = Http::asJson()
            ->timeout((int) config('services.feishu.timeout', 10))
            ->post($this->endpoint('auth/v3/tenant_access_token/internal'), [
                'app_id' => $appId,
                'app_secret' => $appSecret,
            ]);
        $this->ensureHttpSuccess($response, 'tenant_access_token');
        $data = $this->responseData($response->json(), 'tenant_access_token');
        $accessToken = trim((string) ($data['tenant_access_token'] ?? ''));

        if ($accessToken === '') {
            throw new RuntimeException('Feishu token endpoint did not return a tenant access token.');
        }

        $expiresIn = max(1, (int) ($data['expire'] ?? $data['expires_in'] ?? 7200) - 300);
        Cache::put(self::TENANT_ACCESS_TOKEN_CACHE_KEY, $accessToken, $expiresIn);

        return $accessToken;
    }

    /**
     * @return array<string, mixed>
     */
    public function sendText(string $openId, string $content): array
    {
        return $this->sendMessage(
            $openId,
            'text',
            $this->encodeContent(['text' => $content]),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function sendCard(string $openId, string $title, string $description, string $url): array
    {
        $card = [
            'config' => [
                'wide_screen_mode' => true,
            ],
            'header' => [
                'template' => 'blue',
                'title' => [
                    'tag' => 'plain_text',
                    'content' => $title,
                ],
            ],
            'elements' => [
                [
                    'tag' => 'div',
                    'text' => [
                        'tag' => 'plain_text',
                        'content' => $description,
                    ],
                ],
                [
                    'tag' => 'action',
                    'actions' => [
                        [
                            'tag' => 'button',
                            'text' => [
                                'tag' => 'plain_text',
                                'content' => '去回复',
                            ],
                            'type' => 'primary',
                            'url' => $url,
                        ],
                    ],
                ],
            ],
        ];

        return $this->sendMessage(
            $openId,
            'interactive',
            $this->encodeContent($card),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function sendMessage(string $openId, string $messageType, string $content): array
    {
        $response = Http::withToken($this->tenantAccessToken())
            ->asJson()
            ->timeout((int) config('services.feishu.timeout', 10))
            ->post($this->endpoint('im/v1/messages?receive_id_type=open_id'), [
                'receive_id' => $openId,
                'msg_type' => $messageType,
                'content' => $content,
            ]);

        $this->ensureHttpSuccess($response, 'im/v1/messages');

        return $this->responseData($response->json(), 'im/v1/messages');
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.feishu.base_url', 'https://open.feishu.cn/open-apis'), '/').'/'.$path;
    }

    /** @param array<string, mixed> $content */
    private function encodeContent(array $content): string
    {
        return json_encode(
            $content,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }

    private function ensureHttpSuccess(Response $response, string $operation): void
    {
        if (! $response->successful()) {
            throw new RuntimeException(
                "Feishu {$operation} failed with HTTP status {$response->status()}.",
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function responseData(mixed $payload, string $operation): array
    {
        $data = is_array($payload) ? $payload : [];
        $errorCode = (int) ($data['code'] ?? 0);

        if ($errorCode !== 0) {
            $message = (string) ($data['msg'] ?? 'Unknown Feishu API error.');

            throw new RuntimeException("Feishu {$operation} failed ({$errorCode}): {$message}");
        }

        return $data;
    }
}
