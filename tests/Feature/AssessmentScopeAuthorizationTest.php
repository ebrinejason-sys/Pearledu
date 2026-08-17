<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\AcademicYear;
use App\Models\AssessmentPeriod;
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

class AssessmentScopeAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private SchoolClass $classA;

    private SchoolClass $classB;

    private Subject $math;

    private Subject $sst;

    private AssessmentPeriod $period;

    private Student $studentA;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);

        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);

        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'is_current' => true,
        ]);

        $this->classA = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P5A-asmt', 'code' => 'P5AAS']);
        $this->classB = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P5B-asmt', 'code' => 'P5BAS']);
        $this->math = Subject::create(['school_id' => $this->school->id, 'name' => 'Mathematics', 'code' => 'MTH']);
        $this->sst = Subject::create(['school_id' => $this->school->id, 'name' => 'SST', 'code' => 'SST']);
        $this->period = AssessmentPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Midterm',
            'max_score' => 100,
        ]);
        $this->studentA = Student::create([
            'school_id' => $this->school->id,
            'full_name' => 'Learner A',
            'class_id' => $this->classA->id,
            'status' => 'active',
        ]);
    }

    private function actingAsInSchool(User $user): static
    {
        app(TenantContext::class)->forSchool($this->school->id);

        return $this->actingAs($user);
    }

    private function assignTeacher(User $teacher, SchoolClass $class, Subject $subject): void
    {
        TeachingAssignment::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'status' => 'active',
        ]);
    }

    public function test_subject_teacher_cannot_save_unassigned_subject(): void
    {
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $this->assignTeacher($teacher, $this->classA, $this->math);

        $response = $this->actingAsInSchool($teacher)->post(route('app.assessment.marks.store'), [
            'period_id' => $this->period->id,
            'class_id' => $this->classA->id,
            'subject_id' => $this->sst->id,
            'rows' => [
                ['student_id' => $this->studentA->id, 'score' => 70],
            ],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('marks', [
            'student_id' => $this->studentA->id,
            'subject_id' => $this->sst->id,
        ]);
    }

    public function test_subject_teacher_can_save_assigned_subject(): void
    {
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $this->assignTeacher($teacher, $this->classA, $this->math);

        $response = $this->actingAsInSchool($teacher)->post(route('app.assessment.marks.store'), [
            'period_id' => $this->period->id,
            'class_id' => $this->classA->id,
            'subject_id' => $this->math->id,
            'rows' => [
                ['student_id' => $this->studentA->id, 'score' => 82, 'grade' => 'A'],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('marks', [
            'student_id' => $this->studentA->id,
            'subject_id' => $this->math->id,
            'class_id' => $this->classA->id,
            'score' => 82,
        ]);
    }

    public function test_class_teacher_cannot_open_marks_entry(): void
    {
        $classTeacher = User::where('email', 'classteacher@standrews.test')->firstOrFail();
        RoleAssignment::query()
            ->where('user_id', $classTeacher->id)
            ->where('school_id', $this->school->id)
            ->update(['class_id' => $this->classA->id]);

        $this->actingAsInSchool($classTeacher)
            ->get(route('app.assessment.marks'))
            ->assertForbidden();
    }

    public function test_class_teacher_can_view_assigned_broadsheet_only(): void
    {
        $classTeacher = User::where('email', 'classteacher@standrews.test')->firstOrFail();
        RoleAssignment::query()
            ->where('user_id', $classTeacher->id)
            ->where('school_id', $this->school->id)
            ->update(['class_id' => $this->classA->id]);

        $this->actingAsInSchool($classTeacher)
            ->get(route('app.assessment.broadsheet', [
                'period_id' => $this->period->id,
                'class_id' => $this->classA->id,
            ]))
            ->assertOk();

        $this->actingAsInSchool($classTeacher)
            ->get(route('app.assessment.broadsheet', [
                'period_id' => $this->period->id,
                'class_id' => $this->classB->id,
            ]))
            ->assertForbidden();
    }

    public function test_marks_page_only_lists_assigned_classes_and_subjects(): void
    {
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $this->assignTeacher($teacher, $this->classA, $this->math);

        $response = $this->actingAsInSchool($teacher)
            ->get(route('app.assessment.marks', [
                'period_id' => $this->period->id,
                'class_id' => $this->classA->id,
            ]));

        $response->assertOk();
        $response->assertSee('P5A-asmt');
        $response->assertDontSee('P5B-asmt');
        $response->assertSee('Mathematics');
        $response->assertDontSee('SST');
    }

    public function test_admin_can_create_teaching_assignment_for_teacher(): void
    {
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();

        $response = $this->actingAsInSchool($admin)->post(route('app.teaching.store'), [
            'user_id' => $teacher->id,
            'subject_id' => $this->math->id,
            'class_id' => $this->classA->id,
            'academic_year_id' => $this->year->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('teaching_assignments', [
            'user_id' => $teacher->id,
            'subject_id' => $this->math->id,
            'class_id' => $this->classA->id,
            'academic_year_id' => $this->year->id,
            'status' => 'active',
        ]);
    }
}
