<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Provisioning\SchoolProvisioner;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * On the shared pearledu host, ResolveTenant pins platform RLS first.
 * School context must be pinned before SubstituteBindings or school users
 * can load other tenants' records via implicit route binding (IDOR).
 */
class SharedHostTenantBindingTest extends TestCase
{
    use RefreshDatabase;

    private string $host;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->host = 'http://'.config('tenancy.pearledu_landing_host');
    }

    public function test_school_user_cannot_bind_another_tenant_student_on_shared_host(): void
    {
        $schoolA = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $adminA = User::where('email', 'admin@standrews.test')->firstOrFail();

        app(TenantContext::class)->forPlatform();
        $schoolB = app(SchoolProvisioner::class)->onboard(
            school: [
                'name' => 'Rival College',
                'district' => 'Entebbe',
                'emis_number' => '9990001',
                'theme' => 'pearledu',
            ],
            levels: ['primary'],
            admin: [
                'full_name' => 'Rival Admin',
                'email' => 'admin@rival.test',
            ],
            operatorId: User::where('is_platform', true)->value('id'),
        )['school'];

        app(TenantContext::class)->forSchool($schoolB->id);
        $foreignStudent = Student::create([
            'school_id' => $schoolB->id,
            'full_name' => 'Secret Rival Learner',
            'status' => 'active',
        ]);

        app(TenantContext::class)->clear();

        $response = $this->actingAs($adminA)
            ->withSession([TenantContext::SESSION_SCHOOL_ID => $schoolA->id])
            ->get($this->host.'/students/'.$foreignStudent->id);

        $response->assertNotFound();
        $response->assertDontSee('Secret Rival Learner');
    }

    public function test_school_user_can_view_own_tenant_student_on_shared_host(): void
    {
        $schoolA = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $adminA = User::where('email', 'admin@standrews.test')->firstOrFail();

        app(TenantContext::class)->forSchool($schoolA->id);
        $ownStudent = Student::create([
            'school_id' => $schoolA->id,
            'full_name' => 'Own School Learner',
            'status' => 'active',
        ]);

        $response = $this->actingAs($adminA)
            ->withSession([TenantContext::SESSION_SCHOOL_ID => $schoolA->id])
            ->get($this->host.'/students/'.$ownStudent->id);

        $response->assertOk();
        $response->assertSee('Own School Learner');
    }

    public function test_for_platform_clears_stale_school_id(): void
    {
        $ctx = app(TenantContext::class);
        $school = School::where('slug', 'like', 'pearledu%')->firstOrFail();

        $ctx->forSchool($school->id);
        $this->assertSame($school->id, $ctx->schoolId());

        $ctx->forPlatform();
        $this->assertTrue($ctx->isPlatform());
        $this->assertNull($ctx->schoolId());
    }
}
