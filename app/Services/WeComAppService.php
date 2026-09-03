<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Sends application messages to staff through the self-built WeCom
 * application ("website support bridge"). This is separate from the WeChat
 * Customer Service (kf) API: it uses the application's own secret and can
 * message members at any time, without the kf 48-hour window.
 */
class WeComAppService
{
    private const ACCESS_TOKEN_CACHE_KEY = 'wecom:app:access_token';

    /**
     * @return array<string, mixed>
     */
    public function sendText(string $userIds, string $content): array
    {
        return $this->sendMessage([
            'touser' => $userIds,
            'msgtype' => 'text',
            'agentid' => $this->agentId(),
            'text' => [
                'content' => $content,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function sendTextCard(string $userIds, string $title, string $description, string $url): array
    {
        return $this->sendMessage([
            'touser' => $userIds,
            'msgtype' => 'textcard',
            'agentid' => $this->agentId(),
            'textcard' => [
                'title' => $title,
                'description' => $description,
                'url' => $url,
                'btntxt' => '去回复',
            ],
        ]);
    }

    public function isConfigured(): bool
    {
        return trim((string) config('services.wecom.corp_id')) !== ''
            && trim((string) config('services.wecom.app_secret')) !== ''
            && trim((string) config('services.wecom.app_agent_id')) !== ''
            && $this->supportUserIds() !== [];
    }

    public function isSupportUser(string $userId): bool
    {
        return $userId !== '' && in_array($userId, $this->supportUserIds(), true);
    }

    /** @return array<int, string> */
    public function supportUserIds(): array
    {
        $userIds = config('services.wecom.app_support_user_ids', []);

        return is_array($userIds) ? array_values(array_map('strval', $userIds)) : [];
    }

    /**
     * WeCom "touser" joins multiple user IDs with a pipe.
     */
    public function supportUserIdsString(): string
    {
        return implode('|', $this->supportUserIds());
    }

    public function accessToken(): string
    {
        $cached = Cache::get(self::ACCESS_TOKEN_CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = Http::asJson()
            ->timeout((int) config('services.wecom.timeout', 10))
            ->get('https://qyapi.weixin.qq.com/cgi-bin/gettoken', [
                'corpid' => (string) config('services.wecom.corp_id'),
                'corpsecret' => (string) config('services.wecom.app_secret'),
            ]);
        $this->ensureHttpSuccess($response, 'gettoken');
        $data = $this->responseData($response->json(), 'gettoken');
        $accessToken = trim((string) ($data['access_token'] ?? ''));

        if ($accessToken === '') {
            throw new RuntimeException('WeCom gettoken did not return an access token.');
        }

        $expiresIn = max(1, (int) ($data['expires_in'] ?? 7200) - 300);
        Cache::put(self::ACCESS_TOKEN_CACHE_KEY, $accessToken, $expiresIn);

        return $accessToken;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sendMessage(array $payload): array
    {
        $response = Http::asJson()
            ->timeout((int) config('services.wecom.timeout', 10))
            ->post(
                'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token='
                    .urlencode($this->accessToken()),
                $payload,
            );

        $this->ensureHttpSuccess($response, 'message/send');

        return $this->responseData($response->json(), 'message/send');
    }

    private function agentId(): int
    {
        return (int) config('services.wecom.app_agent_id');
    }

    private function ensureHttpSuccess(Response $response, string $operation): void
    {
        if (! $response->successful()) {
            throw new RuntimeException(
                "WeCom {$operation} failed with HTTP status {$response->status()}.",
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function responseData(mixed $payload, string $operation): array
    {
        $data = is_array($payload) ? $payload : [];
        $errorCode = (int) ($data['errcode'] ?? 0);

        if ($errorCode !== 0) {
            $message = (string) ($data['errmsg'] ?? 'Unknown WeCom API error.');

            throw new RuntimeException("WeCom {$operation} failed ({$errorCode}): {$message}");
        }

        return $data;
    }
}
