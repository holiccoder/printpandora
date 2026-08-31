<?php

namespace App\Http\Controllers;

use App\Jobs\SyncWeComKfMessages;
use App\Support\WeCom\WeComCrypt;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class WeComKfCallbackController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $crypt = new WeComCrypt((string) config('services.wecom.corp_id'));
        $encryptedMessage = $this->encryptedMessage($request);
        $signature = (string) ($request->query('msg_signature') ?? $request->query('signature', ''));
        $timestamp = (string) $request->query('timestamp', '');
        $nonce = (string) $request->query('nonce', '');

        if ($encryptedMessage === null
            || ! $crypt->verifySignature(
                (string) config('services.wecom.callback_token'),
                $timestamp,
                $nonce,
                $encryptedMessage,
                $signature,
            )) {
            return response('Invalid WeCom callback signature.', 403);
        }

        try {
            $plainText = $crypt->decrypt(
                $encryptedMessage,
                (string) config('services.wecom.encoding_aes_key'),
            );
        } catch (Throwable $exception) {
            report($exception);

            return response('Invalid WeCom callback message.', 403);
        }

        if ($request->isMethod('GET')) {
            return response($plainText, 200, ['Content-Type' => 'text/plain']);
        }

        $event = $this->parsePlainMessage($plainText);

        if ($event['event'] === 'kf_msg_or_event') {
            dispatch(new SyncWeComKfMessages(
                token: $event['token'] ?? null,
                openKfId: $event['open_kfid'] ?? null,
            ));
        }

        return response('success', 200, ['Content-Type' => 'text/plain']);
    }

    private function encryptedMessage(Request $request): ?string
    {
        if ($request->isMethod('GET')) {
            $echoString = trim((string) $request->query('echostr', ''));

            return $echoString === '' ? null : $echoString;
        }

        $body = trim($request->getContent());

        if ($body !== '') {
            $json = json_decode($body, true);

            if (is_array($json)) {
                $encrypted = $json['Encrypt'] ?? $json['encrypt'] ?? null;

                if (is_string($encrypted) && trim($encrypted) !== '') {
                    return trim($encrypted);
                }
            }

            $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);

            if ($xml !== false) {
                $encrypted = (string) ($xml->Encrypt ?? $xml->encrypt ?? '');

                if (trim($encrypted) !== '') {
                    return trim($encrypted);
                }
            }
        }

        $encrypted = $request->input('Encrypt', $request->input('encrypt'));

        return is_string($encrypted) && trim($encrypted) !== '' ? trim($encrypted) : null;
    }

    /** @return array{event: string, token: ?string, open_kfid: ?string} */
    private function parsePlainMessage(string $plainText): array
    {
        $plainText = trim($plainText);

        if (str_starts_with($plainText, '{')) {
            $json = json_decode($plainText, true);

            if (is_array($json)) {
                return [
                    'event' => (string) ($json['event'] ?? $json['Event'] ?? ''),
                    'token' => $this->nullableString($json['token'] ?? $json['Token'] ?? null),
                    'open_kfid' => $this->nullableString(
                        $json['open_kfid'] ?? $json['OpenKfId'] ?? $json['OpenKfID'] ?? null,
                    ),
                ];
            }
        }

        $xml = simplexml_load_string($plainText, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);

        if ($xml === false) {
            return ['event' => '', 'token' => null, 'open_kfid' => null];
        }

        return [
            'event' => (string) ($xml->Event ?? $xml->event ?? ''),
            'token' => $this->nullableString((string) ($xml->Token ?? $xml->token ?? '')),
            'open_kfid' => $this->nullableString((string) ($xml->OpenKfId ?? $xml->open_kfid ?? '')),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
