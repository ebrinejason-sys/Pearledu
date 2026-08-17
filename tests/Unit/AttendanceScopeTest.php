<?php

namespace Tests\Unit;

use App\Models\AcademicYear;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Authorization\AttendanceScope;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceScopeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AttendanceScope $scope;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);
        $this->scope = app(AttendanceScope::class);
        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026-att',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'is_current' => true,
        ]);
    }

    public function test_subject_teacher_marks_only_assigned_class(): void
    {
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $classA = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P4A-att', 'code' => 'P4AATT']);
        $classB = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P4B-att', 'code' => 'P4BATT']);
        $math = Subject::create(['school_id' => $this->school->id, 'name' => 'Math-att', 'code' => 'MATT']);

        TeachingAssignment::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $classA->id,
            'subject_id' => $math->id,
            'status' => 'active',
        ]);

        $this->assertTrue($this->scope->canMarkClass($teacher, $this->school->id, $classA->id));
        $this->assertFalse($this->scope->canMarkClass($teacher, $this->school->id, $classB->id));
        $this->assertSame([$classA->id], $this->scope->markableClassIds($teacher, $this->school->id));
    }

    public function test_class_teacher_marks_only_homeroom(): void
    {
        $classTeacher = User::where('email', 'classteacher@standrews.test')->firstOrFail();
        $classA = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P3A-att', 'code' => 'P3AATT']);
        $classB = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P3B-att', 'code' => 'P3BATT']);

        RoleAssignment::query()
            ->where('user_id', $classTeacher->id)
            ->where('school_id', $this->school->id)
            ->update(['class_id' => $classA->id]);

        $this->assertTrue($this->scope->canMarkClass($classTeacher, $this->school->id, $classA->id));
        $this->assertFalse($this->scope->canMarkClass($classTeacher, $this->school->id, $classB->id));
    }

    public function test_director_can_view_but_not_mark(): void
    {
        $director = User::where('email', 'director@standrews.test')->firstOrFail();
        $class = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P1-att', 'code' => 'P1ATT']);

        $this->assertTrue($this->scope->canViewClass($director, $this->school->id, $class->id));
        $this->assertFalse($this->scope->canMarkClass($director, $this->school->id, $class->id));
        $this->assertNull($this->scope->viewableClassIds($director, $this->school->id));
        $this->assertSame([], $this->scope->markableClassIds($director, $this->school->id));
    }

    public function test_dos_marks_school_wide(): void
    {
        $dos = User::where('email', 'dos@standrews.test')->firstOrFail();
        $class = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P2-att', 'code' => 'P2ATT']);

        $this->assertTrue($this->scope->canManage($dos, $this->school->id));
        $this->assertTrue($this->scope->canMarkClass($dos, $this->school->id, $class->id));
        $this->assertNull($this->scope->markableClassIds($dos, $this->school->id));
    }
}
