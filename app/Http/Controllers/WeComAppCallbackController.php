<?php

namespace App\Http\Controllers;

use App\Jobs\HandleWeComAppOperatorMessage;
use App\Support\WeCom\WeComCrypt;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class WeComAppCallbackController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $crypt = new WeComCrypt((string) config('services.wecom.corp_id'));
        $encryptedMessage = $this->encryptedMessage($request);
        $signature = (string) ($request->query('msg_signature') ?? $request->query('signature', ''));
        $timestamp = (string) $request->query('timestamp', '');
        $nonce = (string) $request->query('nonce', '');
        $token = trim((string) config('services.wecom.app_callback_token'));

        if ($token === ''
            || $encryptedMessage === null
            || ! $crypt->verifySignature(
                $token,
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
                (string) config('services.wecom.app_encoding_aes_key'),
            );
        } catch (Throwable $exception) {
            report($exception);

            return response('Invalid WeCom callback message.', 403);
        }

        if ($request->isMethod('GET')) {
            return response($plainText, 200, ['Content-Type' => 'text/plain']);
        }

        $event = $this->parsePlainMessage($plainText);
        $configuredAgentId = trim((string) config('services.wecom.app_agent_id'));

        if ($event['msg_type'] === 'text'
            && $configuredAgentId !== ''
            && $event['agent_id'] === $configuredAgentId
            && $event['user_id'] !== ''
            && $event['msg_id'] !== '') {
            try {
                dispatch(new HandleWeComAppOperatorMessage(
                    userId: $event['user_id'],
                    msgId: $event['msg_id'],
                    text: $event['text'],
                ));
            } catch (Throwable $exception) {
                // A callback must be acknowledged quickly. Queue failures are
                // logged for operators instead of causing WeCom to retry the
                // same message indefinitely.
                report($exception);
            }
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

    /** @return array{msg_type: string, user_id: string, msg_id: string, text: string, agent_id: string} */
    private function parsePlainMessage(string $plainText): array
    {
        $plainText = trim($plainText);

        if (str_starts_with($plainText, '{')) {
            $json = json_decode($plainText, true);

            if (is_array($json)) {
                return [
                    'msg_type' => strtolower((string) ($json['MsgType'] ?? $json['msgtype'] ?? '')),
                    'user_id' => trim((string) ($json['FromUserName'] ?? $json['from_user_name'] ?? '')),
                    'msg_id' => trim((string) ($json['MsgId'] ?? $json['msgid'] ?? '')),
                    'text' => trim((string) ($json['Content'] ?? $json['content'] ?? '')),
                    'agent_id' => trim((string) ($json['AgentID'] ?? $json['AgentId'] ?? $json['agentid'] ?? '')),
                ];
            }
        }

        $xml = simplexml_load_string($plainText, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);

        if ($xml === false) {
            return [
                'msg_type' => '',
                'user_id' => '',
                'msg_id' => '',
                'text' => '',
                'agent_id' => '',
            ];
        }

        return [
            'msg_type' => strtolower((string) ($xml->MsgType ?? $xml->msgtype ?? '')),
            'user_id' => trim((string) ($xml->FromUserName ?? $xml->from_user_name ?? '')),
            'msg_id' => trim((string) ($xml->MsgId ?? $xml->msgid ?? '')),
            'text' => trim((string) ($xml->Content ?? $xml->content ?? '')),
            'agent_id' => trim((string) ($xml->AgentID ?? $xml->AgentId ?? $xml->agentid ?? '')),
        ];
    }
}
