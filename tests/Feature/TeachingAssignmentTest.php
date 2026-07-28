<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeachingAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private AcademicYear $year;
    private SchoolClass $class;
    private Subject $subject;

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
        $this->class = SchoolClass::create([
            'school_id' => $this->school->id,
            'level' => 'primary',
            'name' => 'P1A',
            'code' => 'P1A',
        ]);
        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Literacy',
            'code' => 'LIT',
        ]);
    }

    public function test_rejects_parent_as_teacher(): void
    {
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        $parent = User::where('email', 'parent@standrews.test')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('app.teaching.store'), [
            'user_id' => $parent->id,
            'subject_id' => $this->subject->id,
            'class_id' => $this->class->id,
            'academic_year_id' => $this->year->id,
        ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertDatabaseMissing('teaching_assignments', [
            'user_id' => $parent->id,
            'subject_id' => $this->subject->id,
        ]);
    }

    public function test_effective_scope_excludes_ended_rows(): void
    {
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();

        TeachingAssignment::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'status' => 'active',
            'ends_on' => now()->subDay()->toDateString(),
        ]);

        $this->assertCount(0, TeachingAssignment::query()->effective()->get());
        $this->assertCount(1, TeachingAssignment::query()->forCurrentYear($this->school->id)->get());
    }
}
