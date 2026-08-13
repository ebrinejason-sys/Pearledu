<?php

namespace Tests\Unit;

use App\Services\Sms\Gateway\ProductionBlockedGateway;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ProductionBlockedGatewayTest extends TestCase
{
    public function test_refuses_send(): void
    {
        $gateway = new ProductionBlockedGateway('fake');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SMS_DRIVER=fake');

        $gateway->send('0700000000', 'hello', null);
    }
}
