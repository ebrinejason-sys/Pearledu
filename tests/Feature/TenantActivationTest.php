<?php
namespace Tests\Feature;

use App\Models\SchoolInvitation;
use App\Models\User;
use App\Services\Provisioning\SchoolProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_first_real_login_on_tenant_subdomain_marks_school_ready(): void
    {
        $result = app(SchoolProvisioner::class)->onboard(
            school: ['name' => 'Ready Test School', 'district' => 'Kampala'],
            levels: ['primary'],
            admin: ['full_name' => 'Rita Ready', 'email' => 'rita@readytest.test'],
            operatorId: null,
        );
        $school = $result['school'];
        $this->assertSame('pending_invite', $school->provisioningState());

        $invitation = SchoolInvitation::where('school_id', $school->id)->latest('id')->first();

        $this->post("/invitations/{$invitation->id}/accept", [
            'token' => $result['invite_token'],
            'password' => 'password12345',
            'password_confirmation' => 'password12345',
        ])->assertRedirect('/login');

        $school->refresh();
        $this->assertSame('invite_accepted', $school->provisioningState());
        $this->assertNull($school->activated_at);

        $this->post("http://{$school->slug}.voxsign.test/login", [
            'email' => 'rita@readytest.test',
            'password' => 'password12345',
        ])->assertRedirect(route('app.home'));

        $school->refresh();
        $this->assertNotNull($school->activated_at);
        $this->assertSame('ready', $school->provisioningState());
    }

    public function test_uninvited_admin_login_attempt_does_not_activate_school(): void
    {
        $result = app(SchoolProvisioner::class)->onboard(
            school: ['name' => 'Slow Start School'],
            levels: ['primary'],
            admin: ['full_name' => 'Ivy Idle', 'email' => 'ivy@slowstart.test'],
            operatorId: null,
        );
        $school = $result['school'];

        $response = $this->post("http://{$school->slug}.voxsign.test/login", [
            'email' => 'ivy@slowstart.test',
            'password' => 'whatever-not-set-yet',
        ]);

        $response->assertSessionHasErrors('email');
        $school->refresh();
        $this->assertNull($school->activated_at);
    }
}
