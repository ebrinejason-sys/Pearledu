<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\Announcement;
use App\Models\CbtAttempt;
use App\Models\CbtExam;
use App\Models\LmsAssignment;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\Attendance\AttendanceService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AudienceEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private SchoolClass $classA;
    private SchoolClass $classB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);
        $this->classA = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P1A', 'code' => 'P1A']);
        $this->classB = SchoolClass::create(['school_id' => $this->school->id, 'level' => 'primary', 'name' => 'P1B', 'code' => 'P1B']);
    }

    private function actingAsInSchool(User $user): static
    {
        app(TenantContext::class)->forSchool($this->school->id);

        return $this->actingAs($user);
    }

    private function makeStudentUser(SchoolClass $class, string $email): array
    {
        $user = User::factory()->create([
            'email' => $email,
            'status' => 'active',
            'password' => Hash::make('password12345'),
        ]);
        RoleAssignment::create([
            'user_id' => $user->id,
            'role_id' => Role::where('key', 'student')->value('id'),
            'school_id' => $this->school->id,
            'is_active' => true,
        ]);
        $student = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
            'full_name' => $user->full_name,
            'class_id' => $class->id,
            'status' => 'active',
        ]);

        return [$user, $student];
    }

    public function test_attendance_rejects_students_outside_selected_class(): void
    {
        $inClass = Student::create([
            'school_id' => $this->school->id,
            'full_name' => 'In Class',
            'class_id' => $this->classA->id,
            'status' => 'active',
        ]);
        $outClass = Student::create([
            'school_id' => $this->school->id,
            'full_name' => 'Out Class',
            'class_id' => $this->classB->id,
            'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);
        app(AttendanceService::class)->bulkUpsert(
            $this->school->id,
            $this->classA->id,
            now()->toDateString(),
            [
                ['student_id' => $inClass->id, 'status' => 'present'],
                ['student_id' => $outClass->id, 'status' => 'absent'],
            ],
            null,
            false,
        );
    }

    public function test_student_only_sees_lms_assignments_for_own_class(): void
    {
        [$user] = $this->makeStudentUser($this->classA, 'learner.a@test.local');

        LmsAssignment::create([
            'school_id' => $this->school->id,
            'class_id' => $this->classA->id,
            'title' => 'For Class A',
        ]);
        LmsAssignment::create([
            'school_id' => $this->school->id,
            'class_id' => $this->classB->id,
            'title' => 'For Class B Secret',
        ]);
        LmsAssignment::create([
            'school_id' => $this->school->id,
            'class_id' => null,
            'title' => 'Whole School LMS',
        ]);

        $response = $this->actingAsInSchool($user)->get(route('app.lms.index'));
        $response->assertOk();
        $response->assertSee('For Class A');
        $response->assertSee('Whole School LMS');
        $response->assertDontSee('For Class B Secret');
    }

    public function test_lms_submit_rejects_other_class_and_past_due(): void
    {
        [$user, $student] = $this->makeStudentUser($this->classA, 'learner.due@test.local');

        $other = LmsAssignment::create([
            'school_id' => $this->school->id,
            'class_id' => $this->classB->id,
            'title' => 'Other class work',
        ]);
        $late = LmsAssignment::create([
            'school_id' => $this->school->id,
            'class_id' => $this->classA->id,
            'title' => 'Late work',
            'due_at' => now()->subDay(),
        ]);

        $this->actingAsInSchool($user)
            ->post(route('app.lms.assignments.submit', $other), ['body' => 'Nope'])
            ->assertForbidden();

        $this->actingAsInSchool($user)
            ->post(route('app.lms.assignments.submit', $late), ['body' => 'Too late'])
            ->assertSessionHasErrors('body');

        $this->assertSame($student->id, $student->id);
    }

    public function test_student_only_sees_cbt_exams_for_own_class(): void
    {
        [$user] = $this->makeStudentUser($this->classA, 'learner.cbt@test.local');

        CbtExam::create([
            'school_id' => $this->school->id,
            'class_id' => $this->classA->id,
            'title' => 'Class A Exam',
            'is_published' => true,
            'duration_minutes' => 30,
        ]);
        CbtExam::create([
            'school_id' => $this->school->id,
            'class_id' => $this->classB->id,
            'title' => 'Class B Exam Hidden',
            'is_published' => true,
            'duration_minutes' => 30,
        ]);

        $response = $this->actingAsInSchool($user)->get(route('app.cbt.index'));
        $response->assertOk();
        $response->assertSee('Class A Exam');
        $response->assertDontSee('Class B Exam Hidden');
    }

    public function test_cbt_start_rejects_other_class_and_enforces_duration(): void
    {
        [$user] = $this->makeStudentUser($this->classA, 'learner.timer@test.local');

        $foreign = CbtExam::create([
            'school_id' => $this->school->id,
            'class_id' => $this->classB->id,
            'title' => 'Foreign',
            'is_published' => true,
            'duration_minutes' => 30,
        ]);
        $this->actingAsInSchool($user)
            ->post(route('app.cbt.exams.start', $foreign))
            ->assertForbidden();

        $exam = CbtExam::create([
            'school_id' => $this->school->id,
            'class_id' => $this->classA->id,
            'title' => 'Timed',
            'is_published' => true,
            'duration_minutes' => 30,
        ]);
        $student = Student::where('user_id', $user->id)->firstOrFail();
        $attempt = CbtAttempt::create([
            'school_id' => $this->school->id,
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'user_id' => $user->id,
            'started_at' => now()->subMinutes(45),
            'status' => 'in_progress',
        ]);

        $this->actingAsInSchool($user)
            ->get(route('app.cbt.attempts.take', $attempt))
            ->assertStatus(422);
    }

    public function test_announcement_normalizes_audience_and_portal_sees_school_wide(): void
    {
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();

        $this->actingAsInSchool($admin)->post(route('app.announcements.store'), [
            'title' => 'Assembly',
            'body' => 'Tomorrow morning',
            'audience' => 'school',
        ])->assertRedirect();

        $this->assertDatabaseHas('announcements', [
            'school_id' => $this->school->id,
            'title' => 'Assembly',
            'audience' => 'all',
        ]);

        // Legacy rows with audience=school still visible via portal query aliases.
        Announcement::create([
            'school_id' => $this->school->id,
            'title' => 'Legacy',
            'body' => 'Old school audience',
            'audience' => 'school',
        ]);

        [$user, $student] = $this->makeStudentUser($this->classA, 'learner.ann@test.local');
        $items = app(\App\Services\Portal\PortalService::class)->announcements($student, $user);
        $this->assertTrue($items->contains(fn ($a) => $a->title === 'Assembly'));
        $this->assertTrue($items->contains(fn ($a) => $a->title === 'Legacy'));
    }
}
