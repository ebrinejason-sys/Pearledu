<?php

namespace Tests\Unit;

use App\Services\SchoolPay\SchoolPayClient;
use PHPUnit\Framework\TestCase;

class SchoolPayClientHashTest extends TestCase
{
    public function test_request_hash_matches_schoolpay_md5_formula(): void
    {
        $client = new SchoolPayClient;
        $hash = $client->hash('123456', '2024-01-15', 'your_secret_password');

        $this->assertSame(
            strtoupper(md5('123456'.'2024-01-15'.'your_secret_password')),
            $hash,
        );
    }

    public function test_webhook_signature_is_sha256_of_password_plus_receipt(): void
    {
        $client = new SchoolPayClient;
        $sig = $client->webhookSignature('api-pass', '18847257');

        $this->assertSame(hash('sha256', 'api-pass'.'18847257'), $sig);
    }
}
