<?php
namespace Tests\Feature;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RequireSchoolMembershipTest extends TestCase
{
    use RefreshDatabase;

    private School $alpha;
    private School $beta;
    private User $alice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $ctx = app(TenantContext::class);
        $ctx->forPlatform();

        $this->alpha = School::create(['name' => 'Alpha', 'slug' => 'alpha', 'status' => 'active']);
        $this->beta = School::create(['name' => 'Beta', 'slug' => 'beta', 'status' => 'active']);

        $this->alice = User::create([
            'full_name' => 'Alice Admin', 'email' => 'alice@alpha.test',
            'status' => 'active', 'password' => Hash::make('password12345'),
        ]);
        RoleAssignment::create([
            'user_id' => $this->alice->id,
            'role_id' => Role::where('key', 'school_admin')->value('id'),
            'school_id' => $this->alpha->id,
            'is_active' => true,
        ]);
    }

    public function test_member_can_access_their_own_schools_home(): void
    {
        $response = $this->actingAs($this->alice)->get('http://alpha.voxsign.test/home');

        $response->assertOk();
    }

    public function test_non_member_gets_403_on_another_schools_subdomain(): void
    {
        $response = $this->actingAs($this->alice)->get('http://beta.voxsign.test/home');

        $response->assertForbidden();
    }
}
