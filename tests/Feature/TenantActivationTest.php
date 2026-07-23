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

    public function test_accepting_invite_activates_account_and_marks_school_ready(): void
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

        $response = $this->post("/invitations/{$invitation->id}/accept", [
            'token' => $result['invite_token'],
            'password' => 'password12345',
            'password_confirmation' => 'password12345',
        ]);

        // May land on tenant subdomain /home or same-host app.home when hosts match.
        $response->assertRedirect();
        $this->assertTrue(
            str_ends_with(parse_url($response->headers->get('Location'), PHP_URL_PATH) ?? '', '/home')
            || $response->headers->get('Location') === route('app.home')
        );

        $this->assertAuthenticated();
        $school->refresh();
        $this->assertNotNull($school->activated_at);
        $this->assertSame('ready', $school->provisioningState());
        $this->assertSame('active', User::where('email', 'rita@readytest.test')->value('status'));
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
            'identifier' => 'ivy@slowstart.test',
            'password' => 'whatever-not-set-yet',
        ]);

        $response->assertSessionHasErrors('identifier');
        $school->refresh();
        $this->assertNull($school->activated_at);
    }
}
