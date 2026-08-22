<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\Provisioning\WalkthroughSchoolService;
use App\Services\Tenancy\TenantContext;
use Database\Seeders\PlatformSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalkthroughSchoolTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PlatformSeeder::class]);
        $this->withoutMiddleware(ResolveTenant::class);

        app(WalkthroughSchoolService::class)->seed('Walkthrough-12');
        $this->school = School::query()->where('emis_number', WalkthroughSchoolService::EMIS_NUMBER)->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);
    }

    private function actingAsInSchool(User $user): static
    {
        app(TenantContext::class)->forSchool($this->school->id);

        return $this->actingAs($user)->withSession([
            TenantContext::SESSION_SCHOOL_ID => $this->school->id,
        ]);
    }

    public function test_walkthrough_fills_baby_through_p7_with_one_hundred_learners(): void
    {
        $this->assertSame(100, Student::query()->where('school_id', $this->school->id)->count());

        foreach (WalkthroughSchoolService::ROSTER_CODES as $code) {
            $class = SchoolClass::query()
                ->where('school_id', $this->school->id)
                ->where('code', $code)
                ->firstOrFail();
            $this->assertSame(
                WalkthroughSchoolService::STUDENTS_PER_CLASS,
                Student::query()->where('school_id', $this->school->id)->where('class_id', $class->id)->count(),
                "Class {$code} should have learners.",
            );
        }
    }

    public function test_seed_is_idempotent(): void
    {
        app(WalkthroughSchoolService::class)->seed('Walkthrough-12');

        $this->assertSame(1, School::query()->where('emis_number', WalkthroughSchoolService::EMIS_NUMBER)->count());
        $this->assertSame(100, Student::query()->where('school_id', $this->school->id)->count());
        $this->assertSame(1, User::query()->where('email', 'admin@stkizito.test')->count());
    }

    public function test_named_staff_can_sign_in_and_land_on_home(): void
    {
        $this->post('/login', [
            'identifier' => 'head@stkizito.test',
            'password' => 'Walkthrough-12',
        ])->assertRedirect();

        $this->assertAuthenticatedAs(User::query()->where('email', 'head@stkizito.test')->firstOrFail());
        $this->get(route('app.home'))->assertOk();
    }

    public function test_bursar_can_open_fees_but_not_marks(): void
    {
        $bursar = User::query()->where('email', 'bursar@stkizito.test')->firstOrFail();

        $this->actingAsInSchool($bursar)->get(route('app.fees.index'))->assertOk();
        $this->actingAsInSchool($bursar)->get(route('app.assessment.marks'))->assertForbidden();
        $this->actingAsInSchool($bursar)->get(route('app.assessment.index'))->assertForbidden();
    }

    public function test_head_teacher_cannot_write_fees_or_open_period_admin(): void
    {
        $head = User::query()->where('email', 'head@stkizito.test')->firstOrFail();

        $this->actingAsInSchool($head)->get(route('app.assessment.index'))->assertForbidden();
        $this->actingAsInSchool($head)->post(route('app.fees.payments.store'), [
            'invoice_id' => 1,
            'amount' => 10,
            'method' => 'cash',
        ])->assertForbidden();
    }

    public function test_english_teacher_can_open_marks_class_teacher_cannot_enter(): void
    {
        $english = User::query()->where('email', 'english@stkizito.test')->firstOrFail();
        $homeroom = User::query()->where('email', 'ct.p4@stkizito.test')->firstOrFail();

        $this->actingAsInSchool($english)->get(route('app.assessment.marks'))->assertOk();
        $this->actingAsInSchool($homeroom)->get(route('app.assessment.marks'))->assertForbidden();
        $this->assertTrue($homeroom->hasRoleInSchool(Role::CLASS_TEACHER, $this->school->id));
        $this->assertFalse($homeroom->hasRoleInSchool(Role::SUBJECT_TEACHER, $this->school->id));
    }

    public function test_p4_class_teacher_cannot_open_p7_learner(): void
    {
        $homeroom = User::query()->where('email', 'ct.p4@stkizito.test')->firstOrFail();
        $p7 = SchoolClass::query()->where('school_id', $this->school->id)->where('code', 'P7')->firstOrFail();
        $other = Student::query()->where('school_id', $this->school->id)->where('class_id', $p7->id)->firstOrFail();

        $this->actingAsInSchool($homeroom)->get(route('app.students.show', $other))->assertForbidden();
    }

    public function test_parent_can_open_portal_and_student_cannot_open_staff_fees(): void
    {
        $parent = User::query()->where('email', 'parent@stkizito.test')->firstOrFail();
        $learner = User::query()->where('email', 'learner.p4@stkizito.test')->firstOrFail();

        $this->actingAsInSchool($parent)->get(route('app.portal.home'))->assertOk();
        $this->actingAsInSchool($learner)->get(route('app.portal.home'))->assertOk();
        $this->actingAsInSchool($learner)->get(route('app.fees.index'))->assertForbidden();
    }

    public function test_dos_can_open_assessment_period_admin(): void
    {
        $dos = User::query()->where('email', 'dos@stkizito.test')->firstOrFail();

        $this->actingAsInSchool($dos)->get(route('app.assessment.index'))->assertOk();
    }

    public function test_command_refuses_production_without_force(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('school:seed-walkthrough', ['--password' => 'Walkthrough-12'])
            ->assertFailed();
    }

    public function test_command_seeds_production_with_force(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('school:seed-walkthrough', [
            '--password' => 'Walkthrough-12',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertSame(1, School::query()->where('emis_number', WalkthroughSchoolService::EMIS_NUMBER)->count());
        $this->assertSame(100, Student::query()->where('school_id', $this->school->id)->count());
    }

    public function test_attendance_and_marks_forms_opt_into_offline_queue(): void
    {
        $homeroom = User::query()->where('email', 'ct.p4@stkizito.test')->firstOrFail();
        $english = User::query()->where('email', 'english@stkizito.test')->firstOrFail();

        $this->actingAsInSchool($homeroom)
            ->get(route('app.attendance.index'))
            ->assertOk()
            ->assertSee('data-offline-queue="attendance"', false)
            ->assertSee('js/offline-first.js', false);

        $this->actingAsInSchool($english)
            ->get(route('app.assessment.marks'))
            ->assertOk()
            ->assertSee('data-offline-queue="marks"', false);
    }
}
