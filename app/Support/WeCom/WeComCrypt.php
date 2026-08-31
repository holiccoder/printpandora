<?php

namespace App\Support\WeCom;

use RuntimeException;

/**
 * Implements the callback encryption protocol used by Enterprise WeChat.
 *
 * The protocol uses a 32-byte PKCS7 block, even though AES-CBC itself uses a
 * 16-byte cipher block. Padding is therefore applied and removed explicitly.
 */
class WeComCrypt
{
    public function __construct(private readonly ?string $receiveId = null) {}

    public function verifySignature(
        string $token,
        string $timestamp,
        string $nonce,
        string $encryptedMessage,
        string $signature,
    ): bool {
        $values = [$token, $timestamp, $nonce, $encryptedMessage];
        sort($values, SORT_STRING);
        $expected = sha1(implode('', $values));

        return hash_equals($expected, $signature);
    }

    public function decrypt(string $encryptedMessage, string $encodingAesKey): string
    {
        $key = $this->decodeKey($encodingAesKey);
        $cipherText = base64_decode($encryptedMessage, true);

        if ($cipherText === false || $cipherText === '') {
            throw new RuntimeException('WeCom callback message is not valid base64.');
        }

        $plainText = openssl_decrypt(
            $cipherText,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            substr($key, 0, 16),
        );

        if ($plainText === false) {
            throw new RuntimeException('WeCom callback message could not be decrypted.');
        }

        $plainText = $this->unpad($plainText);

        if (strlen($plainText) < 20) {
            throw new RuntimeException('WeCom callback message is truncated.');
        }

        $messageLength = unpack('Nlength', substr($plainText, 16, 4))['length'] ?? null;

        if (! is_int($messageLength) || $messageLength < 0) {
            throw new RuntimeException('WeCom callback message length is invalid.');
        }

        $message = substr($plainText, 20, $messageLength);
        $receiveId = substr($plainText, 20 + $messageLength);

        if (strlen($message) !== $messageLength) {
            throw new RuntimeException('WeCom callback message length does not match its payload.');
        }

        $expectedReceiveId = $this->receiveId ?? (string) config('services.wecom.corp_id');

        if ($expectedReceiveId === '' || ! hash_equals($expectedReceiveId, $receiveId)) {
            throw new RuntimeException('WeCom callback receive id does not match the configured corp id.');
        }

        return $message;
    }

    public function encrypt(
        string $plainText,
        string $encodingAesKey,
        ?string $receiveId = null,
    ): string {
        $key = $this->decodeKey($encodingAesKey);
        $receiveId ??= $this->receiveId ?? (string) config('services.wecom.corp_id');
        $message = random_bytes(16).pack('N', strlen($plainText)).$plainText.$receiveId;
        $message .= $this->pad($message);

        $encrypted = openssl_encrypt(
            $message,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            substr($key, 0, 16),
        );

        if ($encrypted === false) {
            throw new RuntimeException('WeCom callback message could not be encrypted.');
        }

        return base64_encode($encrypted);
    }

    private function decodeKey(string $encodingAesKey): string
    {
        $key = base64_decode($encodingAesKey.'=', true);

        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('WeCom EncodingAESKey must decode to 32 bytes.');
        }

        return $key;
    }

    private function pad(string $value): string
    {
        $padding = 32 - (strlen($value) % 32);

        return str_repeat(chr($padding), $padding);
    }

    private function unpad(string $value): string
    {
        $length = strlen($value);

        if ($length === 0 || $length % 32 !== 0) {
            throw new RuntimeException('WeCom callback message padding is invalid.');
        }

        $padding = ord($value[$length - 1]);

        if ($padding < 1 || $padding > 32) {
            throw new RuntimeException('WeCom callback message padding is invalid.');
        }

        $paddingBytes = substr($value, -$padding);

        if ($paddingBytes !== str_repeat(chr($padding), $padding)) {
            throw new RuntimeException('WeCom callback message padding is invalid.');
        }

        return substr($value, 0, $length - $padding);
    }
}
