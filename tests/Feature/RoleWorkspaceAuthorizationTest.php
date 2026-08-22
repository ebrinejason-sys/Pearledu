<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\AcademicYear;
use App\Models\AssessmentPeriod;
use App\Models\AttendanceRecord;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Provisioning\StaffRoleService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TeacherInviteLoad;
use Tests\TestCase;

class RoleWorkspaceAuthorizationTest extends TestCase
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

    private function currentYear(): AcademicYear
    {
        AcademicYear::query()->where('school_id', $this->school->id)->update(['is_current' => false]);

        return AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026-ws',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'is_current' => true,
        ]);
    }

    public function test_teacher_can_post_lms_only_for_assigned_class_and_subject(): void
    {
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $year = $this->currentYear();
        $classA = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P4A-ws', 'code' => 'P4AWS']);
        $classB = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P4B-ws', 'code' => 'P4BWS']);
        $math = Subject::create(['school_id' => $this->school->id, 'name' => 'Math WS', 'code' => 'MWS']);
        $sst = Subject::create(['school_id' => $this->school->id, 'name' => 'SST WS', 'code' => 'SWS']);
        TeachingAssignment::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'academic_year_id' => $year->id,
            'class_id' => $classA->id,
            'subject_id' => $math->id,
            'status' => 'active',
        ]);

        $this->actingAsInSchool($teacher)->post(route('app.lms.materials.store'), [
            'title' => 'Notes',
            'class_id' => $classA->id,
            'subject_id' => $math->id,
        ])->assertRedirect();

        $this->actingAsInSchool($teacher)->post(route('app.lms.materials.store'), [
            'title' => 'Wrong class',
            'class_id' => $classB->id,
            'subject_id' => $math->id,
        ])->assertForbidden();

        $this->actingAsInSchool($teacher)->post(route('app.lms.materials.store'), [
            'title' => 'Wrong subject',
            'class_id' => $classA->id,
            'subject_id' => $sst->id,
        ])->assertForbidden();
    }

    public function test_teacher_cannot_author_cbt_for_unassigned_subject(): void
    {
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $year = $this->currentYear();
        $classA = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P4C-ws', 'code' => 'P4CWS']);
        $math = Subject::create(['school_id' => $this->school->id, 'name' => 'Math CBT', 'code' => 'MCBT']);
        $sst = Subject::create(['school_id' => $this->school->id, 'name' => 'SST CBT', 'code' => 'SCBT']);
        TeachingAssignment::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'academic_year_id' => $year->id,
            'class_id' => $classA->id,
            'subject_id' => $math->id,
            'status' => 'active',
        ]);

        $this->actingAsInSchool($teacher)->post(route('app.cbt.exams.store'), [
            'title' => 'Quiz',
            'class_id' => $classA->id,
            'subject_id' => $sst->id,
        ])->assertForbidden();
    }

    public function test_class_teacher_homeroom_is_preserved_when_adding_another_role(): void
    {
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        $teacher = User::where('email', 'classteacher@standrews.test')->firstOrFail();
        $classA = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P8A-ws', 'code' => 'P8AWS']);
        $classTeacherId = Role::query()->where('key', Role::CLASS_TEACHER)->value('id');
        RoleAssignment::query()
            ->where('user_id', $teacher->id)
            ->where('school_id', $this->school->id)
            ->where('role_id', $classTeacherId)
            ->update(['class_id' => $classA->id]);

        $load = TeacherInviteLoad::ensure($this->school);

        app(StaffRoleService::class)->sync(
            $this->school,
            $teacher,
            [Role::CLASS_TEACHER, Role::SUBJECT_TEACHER],
            $admin,
            false,
            null,
            $load['teaching_assignments'],
        );

        $this->assertDatabaseHas('role_assignments', [
            'user_id' => $teacher->id,
            'school_id' => $this->school->id,
            'role_id' => $classTeacherId,
            'class_id' => $classA->id,
            'is_active' => true,
        ]);
        $this->assertTrue($teacher->fresh()->permissionsForSchool($this->school->id) !== []);
        $this->assertContains('assessment.enter', $teacher->fresh()->permissionsForSchool($this->school->id));
        $this->assertContains('class.view', $teacher->fresh()->permissionsForSchool($this->school->id));
    }

    public function test_class_teacher_still_cannot_enter_marks_without_teaching_assignment(): void
    {
        $classTeacher = User::where('email', 'classteacher@standrews.test')->firstOrFail();

        $this->actingAsInSchool($classTeacher)->get(route('app.assessment.marks'))->assertForbidden();
        $this->actingAsInSchool($classTeacher)->get(route('app.teaching.homeroom'))->assertOk();
    }

    public function test_parent_can_view_linked_child_attendance_but_not_another_student(): void
    {
        $parent = User::where('email', 'parent@standrews.test')->firstOrFail();
        $linked = Student::where('full_name', 'Stella Student')->firstOrFail();
        $other = Student::create([
            'school_id' => $this->school->id,
            'full_name' => 'Unrelated Child',
            'status' => 'active',
        ]);
        AttendanceRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $linked->id,
            'class_id' => $linked->class_id,
            'attended_on' => now()->toDateString(),
            'status' => 'present',
        ]);

        $this->actingAsInSchool($parent)
            ->get(route('app.portal.attendance', ['student_id' => $linked->id]))
            ->assertOk()
            ->assertSee('present');

        $this->actingAsInSchool($parent)
            ->get(route('app.portal.attendance', ['student_id' => $other->id]))
            ->assertForbidden();
    }

    public function test_student_cannot_view_another_learner_attendance(): void
    {
        $studentUser = User::where('email', 'student@standrews.test')->firstOrFail();
        $own = Student::where('user_id', $studentUser->id)->firstOrFail();
        $other = Student::create([
            'school_id' => $this->school->id,
            'full_name' => 'Other Learner WS',
            'status' => 'active',
        ]);

        $this->actingAsInSchool($studentUser)
            ->get(route('app.portal.attendance', ['student_id' => $own->id]))
            ->assertOk();

        $this->actingAsInSchool($studentUser)
            ->get(route('app.portal.attendance', ['student_id' => $other->id]))
            ->assertForbidden();
    }

    public function test_dos_can_open_staff_and_cannot_mutate_fees(): void
    {
        $dos = User::where('email', 'dos@standrews.test')->firstOrFail();

        $this->actingAsInSchool($dos)->get(route('app.staff.index'))->assertOk();
        $this->actingAsInSchool($dos)->get(route('app.enrollments.index'))->assertOk();
        $this->actingAsInSchool($dos)->post(route('app.fees.structures.store'), [
            'name' => 'Illegal',
            'amount' => 1000,
        ])->assertForbidden();
    }

    public function test_bursar_cannot_enter_marks_or_attendance(): void
    {
        $bursar = User::where('email', 'bursar@standrews.test')->firstOrFail();

        $this->actingAsInSchool($bursar)->get(route('app.assessment.marks'))->assertForbidden();
        $this->actingAsInSchool($bursar)->get(route('app.attendance.index'))->assertForbidden();
        $this->actingAsInSchool($bursar)->get(route('app.fees.index'))->assertOk();
    }

    public function test_head_and_director_cannot_write_fees_or_marks(): void
    {
        $head = User::where('email', 'head@standrews.test')->firstOrFail();
        $director = User::where('email', 'director@standrews.test')->firstOrFail();

        $this->actingAsInSchool($head)->get(route('app.home'))->assertOk();
        $this->actingAsInSchool($head)->get(route('app.assessment.marks'))->assertForbidden();
        $this->actingAsInSchool($director)->post(route('app.fees.payments.store'), [
            'invoice_id' => 1,
            'amount' => 10,
            'method' => 'cash',
        ])->assertForbidden();
    }

    public function test_marksheet_submit_locks_teacher_edits_until_returned(): void
    {
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $dos = User::where('email', 'dos@standrews.test')->firstOrFail();
        $year = $this->currentYear();
        $class = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P5M-ws', 'code' => 'P5MWS']);
        $math = Subject::create(['school_id' => $this->school->id, 'name' => 'Math MS', 'code' => 'MMS']);
        $period = AssessmentPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Mid WS',
            'max_score' => 100,
            'status' => 'mark_entry_open',
        ]);
        $learner = Student::create([
            'school_id' => $this->school->id,
            'full_name' => 'Mark Learner',
            'class_id' => $class->id,
            'status' => 'active',
        ]);
        TeachingAssignment::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
            'subject_id' => $math->id,
            'status' => 'active',
        ]);

        $payload = [
            'period_id' => $period->id,
            'class_id' => $class->id,
            'subject_id' => $math->id,
            'rows' => [['student_id' => $learner->id, 'score' => 70]],
        ];

        $this->actingAsInSchool($teacher)->post(route('app.assessment.marks.store'), $payload)->assertRedirect();
        $this->actingAsInSchool($teacher)->post(route('app.assessment.marksheets.submit'), [
            'period_id' => $period->id,
            'class_id' => $class->id,
            'subject_id' => $math->id,
        ])->assertRedirect();

        $this->actingAsInSchool($teacher)->post(route('app.assessment.marks.store'), [
            'period_id' => $period->id,
            'class_id' => $class->id,
            'subject_id' => $math->id,
            'rows' => [['student_id' => $learner->id, 'score' => 99]],
        ])->assertForbidden();

        $this->actingAsInSchool($dos)->post(route('app.assessment.marksheets.verify'), [
            'period_id' => $period->id,
            'class_id' => $class->id,
            'subject_id' => $math->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('assessment_marksheets', [
            'class_id' => $class->id,
            'subject_id' => $math->id,
            'status' => 'verified',
        ]);
    }

    public function test_multi_role_teacher_and_class_teacher_unions_scope(): void
    {
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        $user = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $year = $this->currentYear();
        $classA = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P9A-ws', 'code' => 'P9AWS']);
        $classB = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P9B-ws', 'code' => 'P9BWS']);
        $math = Subject::create(['school_id' => $this->school->id, 'name' => 'Math Union', 'code' => 'MUN']);
        TeachingAssignment::create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
            'academic_year_id' => $year->id,
            'class_id' => $classA->id,
            'subject_id' => $math->id,
            'status' => 'active',
        ]);

        app(StaffRoleService::class)->sync(
            $this->school,
            $user,
            [Role::SUBJECT_TEACHER, Role::CLASS_TEACHER],
            $admin,
            false,
            $classB->id,
        );

        $this->actingAsInSchool($user)->get(route('app.teaching.mine'))->assertOk();
        $this->actingAsInSchool($user)->get(route('app.teaching.homeroom'))->assertOk()->assertSee('P9B-ws');
        $this->actingAsInSchool($user)->get(route('app.students.index'))->assertOk();
    }
}
