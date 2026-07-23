<?php

namespace Tests\Feature;

use App\Models\SchoolInvitation;
use App\Services\Provisioning\SchoolProvisioner;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InviteAcceptHomeAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_after_accept_on_platform_host_home_is_reachable(): void
    {
        $result = app(SchoolProvisioner::class)->onboard(
            school: ['name' => 'Access School', 'district' => 'Gulu', 'theme' => 'pearledu'],
            levels: ['primary'],
            admin: ['full_name' => 'Ada Admin', 'email' => 'ada@access.test'],
            operatorId: null,
        );

        $invitation = SchoolInvitation::where('school_id', $result['school']->id)->latest('id')->firstOrFail();
        $host = 'http://pearledu.voxsign.test';

        $this->post($host.'/invitations/'.$invitation->id.'/accept', [
            'token' => $result['invite_token'],
            'password' => 'password12345',
            'password_confirmation' => 'password12345',
        ])->assertRedirect();

        // Simulate staying on the platform host (common when wildcard DNS lags).
        app(TenantContext::class)->clear();

        $this->get($host.'/home')->assertOk();
    }
}
