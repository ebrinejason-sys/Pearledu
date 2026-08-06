<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StudentAccountLinkTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $admin;
    private Student $learner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);

        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);

        $this->admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        $this->learner = Student::where('school_id', $this->school->id)
            ->whereNull('user_id')
            ->firstOrFail();
    }

    private function actingAsAdmin(): static
    {
        app(TenantContext::class)->forSchool($this->school->id);

        return $this->actingAs($this->admin)->withSession([
            TenantContext::SESSION_SCHOOL_ID => $this->school->id,
        ]);
    }

    public function test_admin_can_invite_student_login(): void
    {
        Mail::fake();

        $response = $this->actingAsAdmin()->post(route('app.students.account.store', $this->learner), [
            'mode' => 'invite',
            'full_name' => 'New Learner Login',
            'email' => 'newlearner@standrews.test',
            'phone' => '+256700111222',
        ]);

        $response->assertRedirect();
        $this->learner->refresh();
        $this->assertNotNull($this->learner->user_id);

        $user = User::where('email', 'newlearner@standrews.test')->firstOrFail();
        $this->assertSame('invited', $user->status);
        $this->assertSame($user->id, $this->learner->user_id);
        $this->assertDatabaseHas('role_assignments', [
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('school_invitations', [
            'user_id' => $user->id,
            'role_key' => 'student',
            'school_id' => $this->school->id,
        ]);
    }

    public function test_demo_parent_and_student_are_linked(): void
    {
        $parent = User::where('email', 'parent@standrews.test')->firstOrFail();
        $studentUser = User::where('email', 'student@standrews.test')->firstOrFail();

        $linked = Student::where('user_id', $studentUser->id)->first();
        $this->assertNotNull($linked);
        $this->assertDatabaseHas('guardianships', [
            'guardian_user_id' => $parent->id,
            'student_id' => $linked->id,
            'is_primary' => true,
        ]);
    }

    public function test_portal_pages_do_not_403_when_unlinked(): void
    {
        $orphan = User::factory()->create([
            'email' => 'orphan.parent@test.local',
            'status' => 'active',
            'password' => 'password',
        ]);
        \App\Models\RoleAssignment::create([
            'user_id' => $orphan->id,
            'role_id' => \App\Models\Role::where('key', 'parent')->value('id'),
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);

        app(TenantContext::class)->forSchool($this->school->id);
        $this->actingAs($orphan)->withSession([
            TenantContext::SESSION_SCHOOL_ID => $this->school->id,
        ])->get(route('app.portal.fees'))->assertOk()->assertSee('No linked learner yet');
    }
}
