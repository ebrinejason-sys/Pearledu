<?php
namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolShowProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_pending_school_shows_pending_invite_pill(): void
    {
        // DemoTenantSeeder leaves the school admin invited (no passwords published),
        // so the seeded school's state stays 'pending_invite'.
        $operator = User::where('email', 'admin@voxsign.co.ug')->firstOrFail();
        $school = School::where('slug', 'like', 'pearledu%')->firstOrFail();

        $response = $this->actingAs($operator)->get(route('platform.schools.show', $school));

        $response->assertOk();
        $response->assertSee('Pending invite');
    }

    public function test_ready_school_shows_ready_pill_and_verified_timestamp(): void
    {
        $operator = User::where('email', 'admin@voxsign.co.ug')->firstOrFail();
        $school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $school->forceFill(['activated_at' => now()])->save();

        $response = $this->actingAs($operator)->get(route('platform.schools.show', $school));

        $response->assertOk();
        $response->assertSee('Ready');
        $response->assertSee('Verified live');
    }
}
