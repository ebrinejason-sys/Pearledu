<?php
namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolInvitation;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolProvisioningStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(TenantContext::class)->forPlatform();
    }

    public function test_state_is_pending_invite_with_no_accepted_invitation(): void
    {
        $school = School::create(['name' => 'State School', 'slug' => 'statesch', 'status' => 'active']);

        $this->assertSame('pending_invite', $school->provisioningState());
    }

    public function test_state_is_invite_accepted_once_invitation_accepted_but_no_login_yet(): void
    {
        $school = School::create(['name' => 'State School', 'slug' => 'statesch', 'status' => 'active']);
        SchoolInvitation::create([
            'school_id' => $school->id,
            'role_key' => 'school_admin',
            'token_hash' => 'x',
            'expires_at' => now()->addDays(7),
            'accepted_at' => now(),
        ]);

        $this->assertSame('invite_accepted', $school->provisioningState());
    }

    public function test_state_is_ready_once_activated_at_is_set(): void
    {
        $school = School::create(['name' => 'State School', 'slug' => 'statesch', 'status' => 'active']);
        $school->forceFill(['activated_at' => now()])->save();

        $this->assertSame('ready', $school->provisioningState());
    }
}
