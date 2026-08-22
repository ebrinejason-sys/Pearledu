<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\FeeInvoice;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StaffMessage;
use App\Models\StaffSalary;
use App\Models\Student;
use App\Models\User;
use App\Services\Hr\StaffBadgeService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ActsAsPlatformOperator;
use Tests\TestCase;

class SchoolOpsUpgradeTest extends TestCase
{
    use ActsAsPlatformOperator;
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

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_platform_admin_updates_workspace_emis_and_schoolpay_after_recent_auth(): void
    {
        $operator = $this->user('admin@voxsign.co.ug');
        app(TenantContext::class)->forPlatform();

        $this->actingAs($operator)
            ->post(route('platform.schools.enter', $this->school))
            ->assertRedirect(route('platform.workspace'));

        $this->actingAs($operator)
            ->withSession(['platform.entered_school_id' => $this->school->id])
            ->get(route('platform.workspace.settings'))
            ->assertOk()
            ->assertSee('EMIS');

        $this->actingAs($operator)
            ->withSession(['platform.entered_school_id' => $this->school->id])
            ->withRecentPlatformAuth()
            ->put(route('platform.workspace.settings.update'), [
                'emis_number' => 'EMIS-OPS-1',
                'emis_enabled' => '1',
                'schoolpay_enabled' => '1',
                'schoolpay_school_code' => 'SCH001',
                'schoolpay_api_password' => 'secret-pass',
            ])
            ->assertRedirect();

        $this->assertSame('EMIS-OPS-1', $this->school->fresh()->emis_number);
        $this->assertTrue((bool) $this->school->fresh()->schoolpay_enabled);
    }

    public function test_emis_data_entrant_cannot_edit_workspace_integrations(): void
    {
        $entrant = User::factory()->create(['email' => 'emis@platform.test', 'status' => 'active']);
        $this->ensurePlatformAdminRole($entrant, 'emis_data_entrant');
        app(TenantContext::class)->forPlatform();

        $this->actingAs($entrant)
            ->withSession(['platform.entered_school_id' => $this->school->id])
            ->get(route('platform.workspace.settings'))
            ->assertForbidden();

        $this->actingAs($entrant)
            ->withSession(['platform.entered_school_id' => $this->school->id])
            ->withRecentPlatformAuth()
            ->put(route('platform.workspace.settings.update'), [
                'emis_number' => 'NOPE',
            ])
            ->assertForbidden();
    }

    public function test_school_user_cannot_open_platform_workspace_settings(): void
    {
        $admin = $this->user('admin@standrews.test');

        $this->actingAsInSchool($admin)
            ->get(route('platform.workspace.settings'))
            ->assertForbidden();
    }

    public function test_entered_workspace_staff_list_offers_imitation(): void
    {
        $operator = $this->user('admin@voxsign.co.ug');
        $staff = $this->user('head@standrews.test');
        app(TenantContext::class)->forPlatform();

        $this->actingAs($operator)
            ->withSession(['platform.entered_school_id' => $this->school->id])
            ->get(route('platform.staff.index'))
            ->assertOk()
            ->assertSee('Read-only')
            ->assertSee($staff->full_name);
    }

    public function test_secretary_prints_id_and_clocks_staff_director_views_only(): void
    {
        $secretary = $this->user('secretary@standrews.test');
        $director = $this->user('director@standrews.test');
        $bursar = $this->user('bursar@standrews.test');
        $teacher = $this->user('teacher@standrews.test');

        $badge = app(StaffBadgeService::class)->issue($this->school, $teacher);

        $this->actingAsInSchool($secretary)->get(route('app.staff.index'))->assertOk();
        $this->actingAsInSchool($secretary)->get(route('app.staff.id', $teacher))->assertOk()->assertSee($badge->code);
        $this->actingAsInSchool($secretary)->get(route('app.staff.clock'))->assertOk();
        $this->actingAsInSchool($secretary)
            ->post(route('app.staff.clock.punch'), ['code' => $badge->code])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('staff_time_punches', [
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'direction' => 'in',
        ]);

        $this->actingAsInSchool($director)->get(route('app.staff.clock'))->assertOk();
        $this->actingAsInSchool($director)->get(route('app.staff.clock.history'))->assertOk();
        $this->actingAsInSchool($director)
            ->post(route('app.staff.clock.punch'), ['code' => $badge->code])
            ->assertForbidden();

        $this->actingAsInSchool($bursar)->get(route('app.staff.clock'))->assertForbidden();
        $this->actingAsInSchool($bursar)->get(route('app.staff.id', $teacher))->assertForbidden();
    }

    public function test_director_views_payroll_bursar_writes_it(): void
    {
        $director = $this->user('director@standrews.test');
        $bursar = $this->user('bursar@standrews.test');
        $teacher = $this->user('teacher@standrews.test');

        $this->actingAsInSchool($director)->get(route('app.staff.payroll'))->assertOk();
        $this->actingAsInSchool($director)->post(route('app.staff.payroll.salary', $teacher), [
            'amount' => 800000,
            'effective_on' => now()->toDateString(),
        ])->assertForbidden();

        $this->actingAsInSchool($bursar)->post(route('app.staff.payroll.salary', $teacher), [
            'amount' => 800000,
            'effective_on' => now()->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(800000, StaffSalary::query()->where('user_id', $teacher->id)->value('amount'));

        $this->actingAsInSchool($bursar)->post(route('app.staff.payroll.pay', $teacher), [
            'amount' => 400000,
            'paid_on' => now()->toDateString(),
            'method' => 'bank',
        ])->assertRedirect();

        $this->actingAsInSchool($director)->get(route('app.staff.show', $teacher))->assertOk();
    }

    public function test_director_opens_class_overview_class_teacher_cannot(): void
    {
        $director = $this->user('director@standrews.test');
        $classTeacher = $this->user('classteacher@standrews.test');

        $this->actingAsInSchool($director)
            ->get(route('app.classes.overview'))
            ->assertOk()
            ->assertSee('Learners male / female');

        $this->actingAsInSchool($classTeacher)->get(route('app.classes.overview'))->assertForbidden();
    }

    public function test_students_filter_by_class_and_gender_and_class_teacher_cannot_leave_scope(): void
    {
        $admin = $this->user('admin@standrews.test');
        $classTeacher = $this->user('classteacher@standrews.test');
        $classA = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P2 Ops A', 'code' => 'P2OPSA']);
        $classB = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P2 Ops B', 'code' => 'P2OPSB']);
        Student::create(['school_id' => $this->school->id, 'full_name' => 'Ada Ops', 'class_id' => $classA->id, 'status' => 'active', 'gender' => 'female']);
        Student::create(['school_id' => $this->school->id, 'full_name' => 'Ben Ops', 'class_id' => $classA->id, 'status' => 'active', 'gender' => 'male']);
        Student::create(['school_id' => $this->school->id, 'full_name' => 'Cara Other', 'class_id' => $classB->id, 'status' => 'active', 'gender' => 'female']);

        RoleAssignment::query()
            ->where('user_id', $classTeacher->id)
            ->where('school_id', $this->school->id)
            ->whereHas('role', fn ($q) => $q->where('key', Role::CLASS_TEACHER))
            ->update(['class_id' => $classA->id]);

        $this->actingAsInSchool($admin)
            ->get(route('app.students.index', ['class_id' => $classA->id, 'gender' => 'female']))
            ->assertOk()
            ->assertSee('Ada Ops')
            ->assertDontSee('Ben Ops')
            ->assertDontSee('Cara Other');

        $this->actingAsInSchool($classTeacher)
            ->get(route('app.students.index', ['class_id' => $classB->id]))
            ->assertForbidden();
    }

    public function test_bursar_and_director_filter_defaulters_and_notify_class_teacher(): void
    {
        $bursar = $this->user('bursar@standrews.test');
        $director = $this->user('director@standrews.test');
        $classTeacher = $this->user('classteacher@standrews.test');
        $class = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P6 Def', 'code' => 'P6DEF']);
        $learner = Student::create([
            'school_id' => $this->school->id,
            'full_name' => 'Owing Learner',
            'class_id' => $class->id,
            'status' => 'active',
            'gender' => 'male',
        ]);
        FeeInvoice::create([
            'school_id' => $this->school->id,
            'student_id' => $learner->id,
            'reference' => 'INV-DEF-1',
            'amount' => 20000,
            'balance' => 20000,
            'status' => 'open',
        ]);
        RoleAssignment::query()
            ->where('user_id', $classTeacher->id)
            ->where('school_id', $this->school->id)
            ->whereHas('role', fn ($q) => $q->where('key', Role::CLASS_TEACHER))
            ->update(['class_id' => $class->id]);

        $this->actingAsInSchool($bursar)
            ->get(route('app.fees.defaulters', ['class_id' => $class->id]))
            ->assertOk()
            ->assertSee('Owing Learner');

        $this->actingAsInSchool($director)
            ->get(route('app.fees.defaulters', ['class_id' => $class->id, 'print' => 1]))
            ->assertOk()
            ->assertSee('Owing Learner');

        $this->actingAsInSchool($classTeacher)
            ->get(route('app.fees.defaulters', ['class_id' => $class->id]))
            ->assertForbidden();

        $this->actingAsInSchool($bursar)
            ->post(route('app.fees.defaulters.notify'), ['class_id' => $class->id])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertTrue(
            StaffMessage::query()
                ->where('school_id', $this->school->id)
                ->where('body', 'like', '%Owing Learner%')
                ->exists()
        );
    }

    public function test_staff_can_message_each_other_parents_cannot(): void
    {
        $head = $this->user('head@standrews.test');
        $teacher = $this->user('teacher@standrews.test');
        $parent = $this->user('parent@standrews.test');

        $this->actingAsInSchool($parent)->get(route('app.staff.messages.index'))->assertForbidden();

        $this->actingAsInSchool($head)
            ->post(route('app.staff.messages.store'), [
                'user_ids' => [$teacher->id],
                'subject' => 'Timetable',
                'body' => 'Please cover P5 in the morning.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('staff_messages', [
            'school_id' => $this->school->id,
            'user_id' => $head->id,
            'body' => 'Please cover P5 in the morning.',
        ]);
    }

    public function test_staff_profile_requires_nin_parent_requires_nin_learner_nin_optional(): void
    {
        $bursar = $this->user('bursar@standrews.test');
        $parent = $this->user('parent@standrews.test');
        $admin = $this->user('admin@standrews.test');

        $this->actingAsInSchool($bursar)->put(route('account.profile.update'), [
            'full_name' => $bursar->full_name,
            'email' => $bursar->email,
            'gender' => 'male',
        ])->assertSessionHasErrors('nin');

        $this->actingAsInSchool($bursar)->put(route('account.profile.update'), [
            'full_name' => $bursar->full_name,
            'email' => $bursar->email,
            'gender' => 'male',
            'nin' => 'CM20220000001',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAsInSchool($parent)->put(route('account.profile.update'), [
            'full_name' => $parent->full_name,
            'email' => $parent->email,
            'gender' => 'female',
        ])->assertSessionHasErrors('nin');

        $this->actingAsInSchool($admin)->post(route('app.students.store'), [
            'full_name' => 'Optional Nin Learner',
            'status' => 'active',
            'gender' => 'female',
            'emis_number' => 'EMIS-NIN-OPT',
        ])->assertRedirect();

        $this->assertDatabaseHas('students', [
            'full_name' => 'Optional Nin Learner',
            'gender' => 'female',
        ]);
    }
}
