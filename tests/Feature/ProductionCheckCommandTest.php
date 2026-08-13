<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_check_passes_in_testing_with_skip_db_optional(): void
    {
        // Testing env is not production — should succeed (warnings allowed).
        $this->artisan('app:production-check', ['--skip-db' => true])
            ->assertSuccessful();
    }

    public function test_production_check_fails_when_debug_forced_in_production_env(): void
    {
        $this->app['env'] = 'production';
        config([
            'app.env' => 'production',
            'app.debug' => true,
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'app.url' => 'https://pearledu.voxsign.co.ug',
            'session.secure' => true,
            'session.domain' => '.voxsign.co.ug',
            'session.encrypt' => true,
            'mail.default' => 'smtp',
            'mail.from.address' => 'no-reply@voxsign.co.ug',
            'mail.mailers.smtp.password' => 'secret',
            'sms.driver' => 'fake',
            'tenancy.base_domain' => 'voxsign.co.ug',
            'tenancy.pearledu_landing_host' => 'pearledu.voxsign.co.ug',
            'tenancy.landing_hosts' => ['voxsign.co.ug', 'www.voxsign.co.ug'],
            'app.seed_demo_tenant' => false,
            'schoolpay.base_url' => 'https://schoolpay.co.ug/paymentapi',
        ]);

        $this->artisan('app:production-check', ['--skip-db' => true])
            ->assertFailed();
    }

    public function test_production_check_fails_when_demo_seed_enabled_in_production(): void
    {
        $this->app['env'] = 'production';
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'app.url' => 'https://pearledu.voxsign.co.ug',
            'session.secure' => true,
            'session.domain' => '.voxsign.co.ug',
            'session.encrypt' => true,
            'mail.default' => 'smtp',
            'mail.from.address' => 'no-reply@voxsign.co.ug',
            'mail.mailers.smtp.password' => 'secret',
            'sms.driver' => 'fake',
            'tenancy.base_domain' => 'voxsign.co.ug',
            'tenancy.pearledu_landing_host' => 'pearledu.voxsign.co.ug',
            'tenancy.landing_hosts' => ['voxsign.co.ug', 'www.voxsign.co.ug'],
            'app.seed_demo_tenant' => true,
            'schoolpay.base_url' => 'https://schoolpay.co.ug/paymentapi',
        ]);

        $this->artisan('app:production-check', ['--skip-db' => true])
            ->expectsOutputToContain('SEED_DEMO_TENANT is enabled')
            ->assertFailed();
    }

    public function test_production_check_warns_when_demo_seed_enabled_locally(): void
    {
        config([
            'app.env' => 'local',
            'app.debug' => false,
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'app.url' => 'https://pearledu.voxsign.co.ug',
            'session.secure' => true,
            'app.seed_demo_tenant' => true,
            'mail.default' => 'smtp',
            'mail.from.address' => 'no-reply@voxsign.co.ug',
            'sms.driver' => 'fake',
            'tenancy.base_domain' => 'voxsign.co.ug',
            'tenancy.pearledu_landing_host' => 'pearledu.voxsign.co.ug',
            'tenancy.landing_hosts' => ['voxsign.co.ug', 'www.voxsign.co.ug'],
            'schoolpay.base_url' => 'https://schoolpay.co.ug/paymentapi',
        ]);

        $this->artisan('app:production-check', ['--skip-db' => true])
            ->expectsOutputToContain('SEED_DEMO_TENANT is enabled')
            ->assertSuccessful();
    }

    public function test_security_headers_are_present_on_web_responses(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
