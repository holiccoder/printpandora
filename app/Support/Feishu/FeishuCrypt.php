<?php

namespace App\Support\Feishu;

use RuntimeException;

/**
 * Implements the optional encrypted callback protocol used by Feishu.
 */
class FeishuCrypt
{
    public function decrypt(string $encryptKey, string $cipherBase64): string
    {
        $key = hash('sha256', $encryptKey, true);
        $cipherText = base64_decode($cipherBase64, true);

        if ($cipherText === false || $cipherText === '') {
            throw new RuntimeException('Feishu callback message is not valid base64.');
        }

        $plainText = openssl_decrypt(
            $cipherText,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            substr($key, 0, 16),
        );

        if ($plainText === false) {
            throw new RuntimeException('Feishu callback message could not be decrypted.');
        }

        return $plainText;
    }

    public function verifySignature(
        string $encryptKey,
        string $timestamp,
        string $nonce,
        string $rawBody,
        string $signature,
    ): bool {
        $expected = hash('sha256', $timestamp.$nonce.$encryptKey.$rawBody);

        return hash_equals($expected, $signature);
    }
}
