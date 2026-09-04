<?php

namespace App\Http\Controllers;

use App\Jobs\HandleFeishuOperatorMessage;
use App\Support\Feishu\FeishuCrypt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class FeishuCallbackController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $this->verifiedPayload($request);

        if ($payload === null) {
            return response()->json(['message' => 'Invalid Feishu callback.'], 403);
        }

        if (($payload['type'] ?? null) === 'url_verification') {
            $challenge = $payload['challenge'] ?? null;

            if (! is_string($challenge) || $challenge === '') {
                return response()->json(['message' => 'Invalid Feishu challenge.'], 403);
            }

            return response()->json(['challenge' => $challenge]);
        }

        if (($payload['header']['event_type'] ?? null) !== 'im.message.receive_v1') {
            return response()->json(['code' => 0]);
        }

        $message = $payload['event']['message'] ?? null;
        $sender = $payload['event']['sender'] ?? null;

        if (! is_array($message) || ! is_array($sender)) {
            return response()->json(['code' => 0]);
        }

        if (($message['message_type'] ?? null) !== 'text'
            || ($sender['sender_type'] ?? null) === 'app') {
            return response()->json(['code' => 0]);
        }

        $messageId = trim((string) ($message['message_id'] ?? ''));
        $senderId = $sender['sender_id'] ?? null;
        $openId = is_array($senderId) ? trim((string) ($senderId['open_id'] ?? '')) : '';
        $content = $message['content'] ?? null;
        $contentPayload = is_string($content) ? json_decode($content, true) : null;
        $text = is_array($contentPayload) && is_string($contentPayload['text'] ?? null)
            ? trim($contentPayload['text'])
            : null;

        if ($messageId !== '' && $openId !== '' && $text !== null) {
            try {
                dispatch(new HandleFeishuOperatorMessage(
                    openId: $openId,
                    messageId: $messageId,
                    text: $text,
                ));
            } catch (Throwable $exception) {
                // A callback must be acknowledged quickly. Queue failures are
                // logged instead of causing Feishu to retry indefinitely.
                report($exception);
            }
        }

        return response()->json(['code' => 0]);
    }

    /** @return array<string, mixed>|null */
    private function verifiedPayload(Request $request): ?array
    {
        $rawBody = $request->getContent();
        $encryptKey = trim((string) config('services.feishu.encrypt_key'));

        if ($encryptKey !== '') {
            $signature = (string) $request->header('X-Lark-Signature', '');
            $timestamp = (string) $request->header('X-Lark-Request-Timestamp', '');
            $nonce = (string) $request->header('X-Lark-Request-Nonce', '');

            if (! (new FeishuCrypt)->verifySignature(
                $encryptKey,
                $timestamp,
                $nonce,
                $rawBody,
                $signature,
            )) {
                return null;
            }

            $envelope = json_decode($rawBody, true);
            $encrypted = is_array($envelope) ? ($envelope['encrypt'] ?? null) : null;

            if (! is_string($encrypted) || trim($encrypted) === '') {
                return null;
            }

            try {
                $plainText = (new FeishuCrypt)->decrypt($encryptKey, $encrypted);
            } catch (Throwable $exception) {
                report($exception);

                return null;
            }

            $payload = json_decode($plainText, true);

            return is_array($payload) ? $payload : null;
        }

        $verificationToken = trim((string) config('services.feishu.verification_token'));
        $payload = json_decode($rawBody, true);

        if ($verificationToken === '' || ! is_array($payload)) {
            return null;
        }

        return ($payload['token'] ?? null) === $verificationToken ? $payload : null;
    }
}
