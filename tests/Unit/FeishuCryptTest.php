<?php

namespace Tests\Unit;

use App\Support\Feishu\FeishuCrypt;
use PHPUnit\Framework\TestCase;

class FeishuCryptTest extends TestCase
{
    private const ENCRYPT_KEY = 'feishu-encrypt-key';

    public function test_it_decrypts_an_aes_callback_payload(): void
    {
        $crypt = new FeishuCrypt;
        $plainText = '{"type":"url_verification","challenge":"echo-value"}';
        $key = hash('sha256', self::ENCRYPT_KEY, true);
        $encrypted = openssl_encrypt(
            $plainText,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            substr($key, 0, 16),
        );

        self::assertIsString($encrypted);

        $this->assertSame(
            $plainText,
            $crypt->decrypt(self::ENCRYPT_KEY, base64_encode($encrypted)),
        );
    }

    public function test_it_verifies_the_lark_sha256_signature(): void
    {
        $timestamp = '1710000000';
        $nonce = 'nonce-value';
        $rawBody = '{"encrypt":"encrypted-message"}';
        $signature = hash('sha256', $timestamp.$nonce.self::ENCRYPT_KEY.$rawBody);
        $crypt = new FeishuCrypt;

        $this->assertTrue($crypt->verifySignature(
            self::ENCRYPT_KEY,
            $timestamp,
            $nonce,
            $rawBody,
            $signature,
        ));
        $this->assertFalse($crypt->verifySignature(
            self::ENCRYPT_KEY,
            $timestamp,
            $nonce,
            $rawBody,
            'invalid-signature',
        ));
    }
}
