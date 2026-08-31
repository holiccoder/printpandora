<?php

namespace App\Services;

use App\Contracts\SendsChatReplies;
use App\Models\AiChatChannel;
use App\Models\AiChatConversation;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WeComKfService implements SendsChatReplies
{
    public const PROVIDER = 'wecom';

    private const ACCESS_TOKEN_CACHE_KEY = 'wecom:kf:access_token';

    /**
     * @return array<string, mixed>
     */
    public function syncMessages(
        ?string $cursor = null,
        ?string $token = null,
        ?string $openKfId = null,
    ): array {
        $payload = [
            'limit' => 1000,
        ];
        $cursor = trim((string) $cursor);
        $token = trim((string) $token);
        $openKfId = trim((string) ($openKfId ?? config('services.wecom.open_kfid')));

        if ($cursor !== '') {
            $payload['cursor'] = $cursor;
        }

        if ($token !== '') {
            $payload['token'] = $token;
        }

        if ($openKfId !== '') {
            $payload['open_kfid'] = $openKfId;
        }

        $response = Http::asJson()
            ->timeout((int) config('services.wecom.timeout', 10))
            ->post($this->endpoint('kf/sync_msg'), $payload);
        $this->ensureHttpSuccess($response, 'sync_msg');
        $data = $this->responseData($response->json(), 'sync_msg');

        return [
            'next_cursor' => (string) ($data['next_cursor'] ?? ''),
            'has_more' => (int) ($data['has_more'] ?? 0),
            'msg_list' => is_array($data['msg_list'] ?? null) ? $data['msg_list'] : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sendMessage(string $externalUserId, string $text): array
    {
        $response = Http::asJson()
            ->timeout((int) config('services.wecom.timeout', 10))
            ->post($this->endpoint('kf/send_msg'), [
                'touser' => $externalUserId,
                'open_kfid' => (string) config('services.wecom.open_kfid'),
                'msgtype' => 'text',
                'text' => [
                    'content' => $text,
                ],
            ]);

        $this->ensureHttpSuccess($response, 'send_msg');

        return $this->responseData($response->json(), 'send_msg');
    }

    public function channelForExternalUser(string $externalUserId): ?AiChatChannel
    {
        return AiChatChannel::query()
            ->where('provider', self::PROVIDER)
            ->where('external_chat_id', $externalUserId)
            ->first();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function sendToConversation(AiChatConversation $conversation, string $text): ?array
    {
        $channel = $conversation->channels()
            ->where('provider', self::PROVIDER)
            ->whereNotNull('external_chat_id')
            ->first();

        if (! $channel || $channel->external_chat_id === null) {
            return null;
        }

        return $this->sendMessage($channel->external_chat_id, $text);
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
                'corpsecret' => (string) config('services.wecom.kf_secret'),
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

    private function endpoint(string $path): string
    {
        return 'https://qyapi.weixin.qq.com/cgi-bin/'.$path.'?access_token='.urlencode($this->accessToken());
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
