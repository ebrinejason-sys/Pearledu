<?php

namespace Tests\Unit;

use App\Models\AcademicYear;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Authorization\LearnerScope;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private LearnerScope $scope;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);
        $this->scope = app(LearnerScope::class);
    }

    public function test_class_teacher_views_only_homeroom_and_cannot_mutate(): void
    {
        $classTeacher = User::where('email', 'classteacher@standrews.test')->firstOrFail();
        $classA = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P3A-lrn', 'code' => 'P3ALRN']);
        $classB = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P3B-lrn', 'code' => 'P3BLRN']);
        $inClass = Student::create(['school_id' => $this->school->id, 'full_name' => 'In Homeroom', 'class_id' => $classA->id, 'status' => 'active']);
        $other = Student::create(['school_id' => $this->school->id, 'full_name' => 'Other Class', 'class_id' => $classB->id, 'status' => 'active']);

        RoleAssignment::query()
            ->where('user_id', $classTeacher->id)
            ->where('school_id', $this->school->id)
            ->update(['class_id' => $classA->id]);

        $this->assertTrue($this->scope->canViewStudent($classTeacher, $this->school->id, $inClass));
        $this->assertFalse($this->scope->canViewStudent($classTeacher, $this->school->id, $other));
        $this->assertFalse($this->scope->canMutateStudent($classTeacher, $this->school->id, $inClass));
        $this->assertSame([$classA->id], $this->scope->viewableClassIds($classTeacher, $this->school->id));
    }

    public function test_subject_teacher_views_assigned_class_only(): void
    {
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026-lrn',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'is_current' => true,
        ]);
        $classA = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P4A-lrn', 'code' => 'P4ALRN']);
        $classB = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P4B-lrn', 'code' => 'P4BLRN']);
        $math = Subject::create(['school_id' => $this->school->id, 'name' => 'Math-lrn', 'code' => 'MLRN']);
        TeachingAssignment::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'academic_year_id' => $year->id,
            'class_id' => $classA->id,
            'subject_id' => $math->id,
            'status' => 'active',
        ]);

        $assigned = Student::create(['school_id' => $this->school->id, 'full_name' => 'Assigned Learner', 'class_id' => $classA->id, 'status' => 'active']);
        $other = Student::create(['school_id' => $this->school->id, 'full_name' => 'Unassigned Learner', 'class_id' => $classB->id, 'status' => 'active']);

        $this->assertTrue($this->scope->canViewStudent($teacher, $this->school->id, $assigned));
        $this->assertFalse($this->scope->canViewStudent($teacher, $this->school->id, $other));
        $this->assertFalse($this->scope->canMutateStudent($teacher, $this->school->id, $assigned));
    }

    public function test_director_views_all_and_cannot_mutate(): void
    {
        $director = User::where('email', 'director@standrews.test')->firstOrFail();
        $student = Student::create(['school_id' => $this->school->id, 'full_name' => 'Any Learner', 'status' => 'active']);

        $this->assertTrue($this->scope->canViewStudent($director, $this->school->id, $student));
        $this->assertFalse($this->scope->canMutateStudent($director, $this->school->id, $student));
        $this->assertNull($this->scope->viewableClassIds($director, $this->school->id));
    }

    public function test_admin_can_mutate(): void
    {
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        $student = Student::create(['school_id' => $this->school->id, 'full_name' => 'Admin Learner', 'status' => 'active']);

        $this->assertTrue($this->scope->canMutateStudent($admin, $this->school->id, $student));
        $this->assertNull($this->scope->viewableClassIds($admin, $this->school->id));
    }
}
