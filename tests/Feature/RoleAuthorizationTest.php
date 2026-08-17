<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\AcademicYear;
use App\Models\FeeInvoice;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
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

    public function test_bursar_cannot_open_assessment(): void
    {
        $bursar = User::where('email', 'bursar@standrews.test')->firstOrFail();

        $this->actingAsInSchool($bursar)->get(route('app.assessment.index'))->assertForbidden();
        $this->actingAsInSchool($bursar)->get(route('app.assessment.marks'))->assertForbidden();
    }

    public function test_director_can_view_fees_but_cannot_create_structure(): void
    {
        $director = User::where('email', 'director@standrews.test')->firstOrFail();

        $this->actingAsInSchool($director)->get(route('app.fees.index'))->assertOk();
        $this->actingAsInSchool($director)->post(route('app.fees.structures.store'), [
            'name' => 'Illegal fee',
            'amount' => 1000,
        ])->assertForbidden();
    }

    public function test_head_teacher_cannot_enter_marks(): void
    {
        $head = User::where('email', 'head@standrews.test')->firstOrFail();

        $this->actingAsInSchool($head)->get(route('app.assessment.marks'))->assertForbidden();
        $this->actingAsInSchool($head)->get(route('app.assessment.broadsheet'))->assertOk();
        $this->actingAsInSchool($head)->get(route('app.fees.index'))->assertOk();
    }

    public function test_dos_manages_assessment_and_cannot_open_fees(): void
    {
        $dos = User::where('email', 'dos@standrews.test')->firstOrFail();

        $this->actingAsInSchool($dos)->get(route('app.assessment.index'))->assertOk();
        $this->actingAsInSchool($dos)->post(route('app.assessment.periods.store'), [
            'name' => 'End of term',
            'max_score' => 100,
        ])->assertRedirect();
        $this->actingAsInSchool($dos)->get(route('app.fees.index'))->assertForbidden();
        $this->actingAsInSchool($dos)->get(route('app.subjects.index'))->assertOk();
        $this->actingAsInSchool($dos)->get(route('app.settings.school'))->assertForbidden();
    }

    public function test_student_can_view_own_fees_but_cannot_pay(): void
    {
        $studentUser = User::where('email', 'student@standrews.test')->firstOrFail();
        $student = Student::where('user_id', $studentUser->id)->firstOrFail();
        $invoice = FeeInvoice::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'reference' => 'INV-STU-1',
            'amount' => 50000,
            'balance' => 50000,
            'status' => 'open',
        ]);

        $this->actingAsInSchool($studentUser)->get(route('app.portal.fees'))->assertOk();
        $this->actingAsInSchool($studentUser)->post(route('app.portal.fees.pay', $invoice), [
            'amount' => 1000,
            'method' => 'cash',
        ])->assertForbidden();
    }

    public function test_subject_teacher_cannot_mark_unassigned_class_attendance(): void
    {
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026-role',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'is_current' => true,
        ]);
        $classA = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P6A-role', 'code' => 'P6AR']);
        $classB = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P6B-role', 'code' => 'P6BR']);
        $subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Bio', 'code' => 'BIO']);
        $learner = Student::create([
            'school_id' => $this->school->id,
            'full_name' => 'Attendance Learner',
            'class_id' => $classB->id,
            'status' => 'active',
        ]);
        TeachingAssignment::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'academic_year_id' => $year->id,
            'class_id' => $classA->id,
            'subject_id' => $subject->id,
            'status' => 'active',
        ]);

        $this->actingAsInSchool($teacher)->get(route('app.attendance.index', ['class_id' => $classB->id]))->assertForbidden();
        $this->actingAsInSchool($teacher)->post(route('app.attendance.store'), [
            'class_id' => $classB->id,
            'attended_on' => now()->toDateString(),
            'records' => [
                ['student_id' => $learner->id, 'status' => 'present'],
            ],
        ])->assertForbidden();
    }

    public function test_class_teacher_cannot_open_other_class_student(): void
    {
        $classTeacher = User::where('email', 'classteacher@standrews.test')->firstOrFail();
        $classA = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P7A-role', 'code' => 'P7AR']);
        $classB = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P7B-role', 'code' => 'P7BR']);
        RoleAssignment::query()
            ->where('user_id', $classTeacher->id)
            ->where('school_id', $this->school->id)
            ->update(['class_id' => $classA->id]);

        $other = Student::create([
            'school_id' => $this->school->id,
            'full_name' => 'Other Homeroom',
            'class_id' => $classB->id,
            'status' => 'active',
        ]);

        $this->actingAsInSchool($classTeacher)->get(route('app.students.show', $other))->assertForbidden();
        $this->actingAsInSchool($classTeacher)->get(route('app.students.create'))->assertForbidden();
    }

    public function test_director_can_view_student_but_cannot_edit(): void
    {
        $director = User::where('email', 'director@standrews.test')->firstOrFail();
        $student = Student::where('school_id', $this->school->id)->firstOrFail();

        $this->actingAsInSchool($director)->get(route('app.students.show', $student))->assertOk();
        $this->actingAsInSchool($director)->get(route('app.students.edit', $student))->assertForbidden();
    }
}
