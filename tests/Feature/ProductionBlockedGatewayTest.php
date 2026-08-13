<?php

namespace Tests\Feature;

use App\Services\Sms\Gateway\FakeGateway;
use App\Services\Sms\Gateway\LogGateway;
use App\Services\Sms\Gateway\ProductionBlockedGateway;
use App\Services\Sms\Gateway\SmsGateway;
use App\Services\Sms\Gateway\UnconfiguredGateway;
use Tests\TestCase;

class ProductionBlockedGatewayTest extends TestCase
{
    public function test_app_binds_production_blocked_gateway_for_fake_in_production(): void
    {
        $this->app['env'] = 'production';
        config(['sms.driver' => 'fake']);

        $this->rebindSmsGateway();

        $this->assertInstanceOf(ProductionBlockedGateway::class, app(SmsGateway::class));
    }

    public function test_app_binds_fake_gateway_outside_production(): void
    {
        $this->app['env'] = 'local';
        config(['sms.driver' => 'fake']);

        $this->rebindSmsGateway();

        $this->assertInstanceOf(FakeGateway::class, app(SmsGateway::class));
    }

    private function rebindSmsGateway(): void
    {
        $this->app->forgetInstance(SmsGateway::class);

        $this->app->bind(SmsGateway::class, function () {
            $driver = (string) config('sms.driver', 'fake');

            if ($this->app->environment('production') && in_array($driver, ['fake', 'log'], true)) {
                return new ProductionBlockedGateway($driver);
            }

            return match ($driver) {
                'fake' => new FakeGateway,
                'log' => new LogGateway,
                'twilio' => new UnconfiguredGateway('twilio'),
                default => new UnconfiguredGateway($driver),
            };
        });
    }
}
