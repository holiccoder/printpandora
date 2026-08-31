<?php

namespace Tests\Unit;

use App\Support\WeCom\WeComCrypt;
use PHPUnit\Framework\TestCase;

class WeComCryptTest extends TestCase
{
    private const AES_KEY = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG';

    public function test_it_round_trips_a_wecom_callback_message(): void
    {
        $crypt = new WeComCrypt('ww-test-corp');
        $plainText = '<xml><Event>kf_msg_or_event</Event></xml>';

        $encrypted = $crypt->encrypt($plainText, self::AES_KEY);

        $this->assertSame($plainText, $crypt->decrypt($encrypted, self::AES_KEY));
    }

    public function test_it_verifies_the_sorted_sha1_signature(): void
    {
        $token = 'callback-token';
        $timestamp = '1710000000';
        $nonce = 'nonce-value';
        $encryptedMessage = 'encrypted-message';
        $signature = sha1('1710000000callback-tokenencrypted-messagenonce-value');
        $crypt = new WeComCrypt('ww-test-corp');

        $this->assertTrue($crypt->verifySignature(
            $token,
            $timestamp,
            $nonce,
            $encryptedMessage,
            $signature,
        ));
        $this->assertFalse($crypt->verifySignature(
            $token,
            $timestamp,
            $nonce,
            $encryptedMessage,
            'invalid-signature',
        ));
    }
}
