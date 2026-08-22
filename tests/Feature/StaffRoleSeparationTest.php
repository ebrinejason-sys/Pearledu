<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\Role;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Services\Navigation\NavigationBuilder;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\TeacherInviteLoad;
use Tests\TestCase;

class StaffRoleSeparationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);
    }

    private function actingAsInSchool(User $user): static
    {
        app(TenantContext::class)->forSchool($this->school->id);

        return $this->actingAs($user)->withSession([
            TenantContext::SESSION_SCHOOL_ID => $this->school->id,
        ]);
    }

    public function test_dos_can_invite_teachers_but_cannot_edit_existing_staff_roles(): void
    {
        Mail::fake();
        config(['mail.from.address' => 'no-reply@voxsign.test']);
        $dos = User::where('email', 'dos@standrews.test')->firstOrFail();
        $head = User::where('email', 'head@standrews.test')->firstOrFail();

        $this->actingAsInSchool($dos)->get(route('app.staff.index'))
            ->assertOk()
            ->assertDontSee('Save responsibilities');

        $load = TeacherInviteLoad::ensure($this->school);

        $this->actingAsInSchool($dos)->post(route('app.staff.store'), [
            'full_name' => 'Invited Teacher',
            'email' => 'invited-teacher@standrews.test',
            'gender' => 'female',
            'nin' => 'CF12345678901',
            'staff_kind' => 'teaching',
            'role_keys' => ['subject_teacher'],
            'teaching_assignments' => $load['teaching_assignments'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'invited-teacher@standrews.test']);

        $this->actingAsInSchool($dos)->put(route('app.staff.roles', $head), [
            'role_keys' => ['subject_teacher'],
        ])->assertForbidden();

        $this->assertTrue($head->fresh()->hasRoleInSchool(Role::HEAD_TEACHER, $this->school->id));
    }

    public function test_head_teacher_cannot_strip_bursar_role(): void
    {
        $head = User::where('email', 'head@standrews.test')->firstOrFail();
        $bursar = User::where('email', 'bursar@standrews.test')->firstOrFail();

        $this->actingAsInSchool($head)
            ->put(route('app.staff.roles', $bursar), [
                'role_keys' => ['subject_teacher'],
            ])
            ->assertForbidden();

        $this->assertTrue($bursar->fresh()->hasRoleInSchool(Role::BURSAR, $this->school->id));
        $this->assertFalse($bursar->fresh()->hasRoleInSchool(Role::SUBJECT_TEACHER, $this->school->id));
    }

    public function test_teacher_navigation_does_not_include_finance_or_sms(): void
    {
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $this->actingAsInSchool($teacher);

        $labels = $this->navLabels(app(NavigationBuilder::class)->build($teacher));

        $this->assertContains('My classes', $labels);
        $this->assertNotContains('SMS', $labels);
        $this->assertNotContains('Fees', $labels);
        $this->assertNotContains('Staff', $labels);
        $this->assertNotContains('Assessment periods', $labels);
    }

    public function test_bursar_navigation_is_finance_not_academics(): void
    {
        $bursar = User::where('email', 'bursar@standrews.test')->firstOrFail();
        $this->actingAsInSchool($bursar);

        $labels = $this->navLabels(app(NavigationBuilder::class)->build($bursar));

        $this->assertContains('Fee types', $labels);
        $this->assertContains('SMS', $labels);
        $this->assertContains('View Learners', $labels);
        $this->assertNotContains('Assessment', $labels);
        $this->assertNotContains('My classes', $labels);
    }

    public function test_granting_teacher_to_existing_staff_requires_classified_load(): void
    {
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        $secretary = User::where('email', 'secretary@standrews.test')->firstOrFail();
        $load = TeacherInviteLoad::ensure($this->school);
        $science = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Integrated Science',
            'code' => 'SCI-LOAD-'.$this->school->id,
        ]);

        $this->actingAsInSchool($admin)
            ->from(route('app.staff.index'))
            ->put(route('app.staff.roles', $secretary), [
                'role_keys' => ['secretary', 'subject_teacher'],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('teaching_assignments');

        $this->assertFalse($secretary->fresh()->hasRoleInSchool(Role::SUBJECT_TEACHER, $this->school->id));
        $this->assertTrue($secretary->fresh()->hasRoleInSchool(Role::SECRETARY, $this->school->id));

        $this->actingAsInSchool($admin)
            ->put(route('app.staff.roles', $secretary), [
                'role_keys' => ['secretary', 'subject_teacher'],
                'teaching_assignments' => [
                    [
                        'subject_id' => $load['subject']->id,
                        'class_ids' => [(int) $load['class']->id],
                        'periods_per_week' => 5,
                    ],
                    [
                        'subject_id' => $science->id,
                        'class_ids' => [(int) $load['class']->id],
                        'periods_per_week' => 2,
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertTrue($secretary->fresh()->hasRoleInSchool(Role::SUBJECT_TEACHER, $this->school->id));
        $this->assertTrue($secretary->fresh()->hasRoleInSchool(Role::SECRETARY, $this->school->id));
        $this->assertDatabaseHas('teaching_assignments', [
            'user_id' => $secretary->id,
            'subject_id' => $load['subject']->id,
            'class_id' => $load['class']->id,
            'periods_per_week' => 5,
        ]);
        $this->assertDatabaseHas('teaching_assignments', [
            'user_id' => $secretary->id,
            'subject_id' => $science->id,
            'class_id' => $load['class']->id,
            'periods_per_week' => 2,
        ]);
    }

    public function test_staff_profile_edit_follows_invite_hierarchy(): void
    {
        $head = User::where('email', 'head@standrews.test')->firstOrFail();
        $director = User::where('email', 'director@standrews.test')->firstOrFail();
        $bursar = User::where('email', 'bursar@standrews.test')->firstOrFail();
        $secretary = User::where('email', 'secretary@standrews.test')->firstOrFail();
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();

        $this->actingAsInSchool($head)
            ->put(route('app.staff.profile.update', $bursar), [
                'full_name' => 'Should Fail Bursar',
            ])
            ->assertForbidden();
        $this->assertNotSame('Should Fail Bursar', $bursar->fresh()->full_name);

        $this->actingAsInSchool($director)
            ->put(route('app.staff.profile.update', $head), [
                'full_name' => $head->full_name,
                'phone' => '0700000001',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('0700000001', $head->fresh()->phone);

        $this->actingAsInSchool($secretary)
            ->put(route('app.staff.profile.update', $bursar), [
                'full_name' => $bursar->full_name,
                'phone' => '0700000002',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('0700000002', $bursar->fresh()->phone);

        $this->actingAsInSchool($head)
            ->get(route('app.staff.show', $teacher))
            ->assertOk()
            ->assertSee('Edit details', false);
        $this->actingAsInSchool($head)
            ->get(route('app.staff.show', $bursar))
            ->assertOk()
            ->assertDontSee('Edit details', false);
    }

    public function test_staff_directory_is_graphical_and_excludes_learners_and_parents(): void
    {
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();

        $this->actingAsInSchool($admin)->get(route('app.staff.index'))
            ->assertOk()
            ->assertSee('Leadership', false)
            ->assertSee('Teaching staff', false)
            ->assertSee('Office and support', false)
            ->assertSee('Daniel Director', false)
            ->assertSee('Bernard Bursar', false)
            ->assertSee('Sarah Secretary', false)
            ->assertSee('developed by Voxsign Technologies', false)
            ->assertSee('viewBox="30 30 340 340"', false)
            ->assertDontSee('Stella Student', false)
            ->assertDontSee('student@standrews.test', false)
            ->assertDontSee('Patricia Parent', false)
            ->assertDontSee('parent@standrews.test', false)
            ->assertDontSee('Own learner portal', false);
    }
}
