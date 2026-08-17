<?php

namespace Tests\Unit;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Authorization\AssessmentScope;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AssessmentScope $scope;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);
        $this->scope = app(AssessmentScope::class);
        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'is_current' => true,
        ]);
    }

    private function assignTeacher(User $teacher, SchoolClass $class, Subject $subject, array $extra = []): TeachingAssignment
    {
        return TeachingAssignment::create(array_merge([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'status' => 'active',
        ], $extra));
    }

    public function test_subject_teacher_enters_only_assigned_class_subject(): void
    {
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $classA = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P4A', 'code' => 'P4A']);
        $classB = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P4B', 'code' => 'P4B']);
        $math = Subject::create(['school_id' => $this->school->id, 'name' => 'Mathematics', 'code' => 'MTH']);
        $sst = Subject::create(['school_id' => $this->school->id, 'name' => 'SST', 'code' => 'SST']);

        $this->assignTeacher($teacher, $classA, $math);

        $this->assertTrue($this->scope->canEnter($teacher, $this->school->id, $classA->id, $math->id));
        $this->assertFalse($this->scope->canEnter($teacher, $this->school->id, $classA->id, $sst->id));
        $this->assertFalse($this->scope->canEnter($teacher, $this->school->id, $classB->id, $math->id));
        $this->assertSame([$classA->id], $this->scope->enterableClassIds($teacher, $this->school->id));
        $this->assertSame([$math->id], $this->scope->enterableSubjectIds($teacher, $this->school->id, $classA->id));
    }

    public function test_class_teacher_views_assigned_class_but_cannot_enter_without_teaching_assignment(): void
    {
        $classTeacher = User::where('email', 'classteacher@standrews.test')->firstOrFail();
        $classA = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P3A', 'code' => 'P3A']);
        $classB = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P3B', 'code' => 'P3B']);
        $math = Subject::create(['school_id' => $this->school->id, 'name' => 'Math', 'code' => 'M']);

        RoleAssignment::query()
            ->where('user_id', $classTeacher->id)
            ->where('school_id', $this->school->id)
            ->update(['class_id' => $classA->id]);

        $this->assertFalse($this->scope->canEnterAnywhere($classTeacher, $this->school->id));
        $this->assertTrue($this->scope->canViewAnywhere($classTeacher, $this->school->id));
        $this->assertTrue($this->scope->canViewClass($classTeacher, $this->school->id, $classA->id));
        $this->assertFalse($this->scope->canViewClass($classTeacher, $this->school->id, $classB->id));
        $this->assertFalse($this->scope->canEnter($classTeacher, $this->school->id, $classA->id, $math->id));
        $this->assertSame([$classA->id], $this->scope->viewableClassIds($classTeacher, $this->school->id));
    }

    public function test_school_admin_is_unrestricted(): void
    {
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        $class = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P1 Scope', 'code' => 'P1-SCOPE']);
        $subject = Subject::create(['school_id' => $this->school->id, 'name' => 'English', 'code' => 'ENG']);

        $this->assertTrue($this->scope->canManage($admin, $this->school->id));
        $this->assertNull($this->scope->enterableClassIds($admin, $this->school->id));
        $this->assertNull($this->scope->viewableClassIds($admin, $this->school->id));
        $this->assertTrue($this->scope->canEnter($admin, $this->school->id, $class->id, $subject->id));
    }

    public function test_ended_teaching_assignment_stops_access(): void
    {
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $class = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P2 Scope', 'code' => 'P2-SCOPE']);
        $subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Science', 'code' => 'SCI']);

        $this->assignTeacher($teacher, $class, $subject, [
            'ends_on' => now()->subDay()->toDateString(),
        ]);

        $this->assertFalse($this->scope->canEnter($teacher, $this->school->id, $class->id, $subject->id));
        $this->assertSame([], $this->scope->enterableClassIds($teacher, $this->school->id));
    }

    public function test_non_current_year_assignment_does_not_grant_access(): void
    {
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $class = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P6 Scope', 'code' => 'P6-SCOPE']);
        $subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Art', 'code' => 'ART']);

        $prior = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2025',
            'starts_on' => '2025-01-01',
            'ends_on' => '2025-12-31',
            'is_current' => false,
        ]);

        TeachingAssignment::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'academic_year_id' => $prior->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'status' => 'active',
        ]);

        $this->assertFalse($this->scope->canEnter($teacher, $this->school->id, $class->id, $subject->id));
    }

    public function test_head_teacher_views_all_classes(): void
    {
        $head = User::where('email', 'head@standrews.test')->firstOrFail();
        $class = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P7', 'code' => 'P7H']);
        $subject = Subject::create(['school_id' => $this->school->id, 'name' => 'History', 'code' => 'HIS']);

        $this->assertFalse($this->scope->canManage($head, $this->school->id));
        $this->assertFalse($this->scope->canEnter($head, $this->school->id, $class->id, $subject->id));
        $this->assertTrue($this->scope->canViewClass($head, $this->school->id, $class->id));
        $this->assertNull($this->scope->viewableClassIds($head, $this->school->id));
    }

    public function test_class_teacher_permissions_no_longer_include_enter(): void
    {
        $roleId = Role::where('key', 'class_teacher')->value('id');
        $this->assertNotNull($roleId);
        $perms = config('permissions.roles.class_teacher');
        $this->assertContains('assessment.view', $perms);
        $this->assertNotContains('assessment.enter', $perms);
    }
}
