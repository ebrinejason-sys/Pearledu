<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\AcademicYear;
use App\Models\FeeInvoice;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\TimetablePeriod;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use App\Services\Timetable\TimetableGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableGeneratorAndFeesFilterTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $bursar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);

        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $this->admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        $this->bursar = User::where('email', 'bursar@standrews.test')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);
    }

    private function sessionFor(User $user): static
    {
        app(TenantContext::class)->forSchool($this->school->id);

        return $this->actingAs($user)->withSession([
            TenantContext::SESSION_SCHOOL_ID => $this->school->id,
        ]);
    }

    public function test_generator_places_lessons_from_teaching_assignments(): void
    {
        $year = AcademicYear::query()->where('school_id', $this->school->id)->where('is_current', true)->first()
            ?? AcademicYear::create([
                'school_id' => $this->school->id,
                'name' => '2026',
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-12-31',
                'is_current' => true,
            ]);

        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'level' => 'primary',
            'name' => 'P6',
            'stream' => 'East',
            'code' => 'P6E-'.uniqid(),
        ]);
        $subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Science', 'code' => 'SCI-'.uniqid()]);
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();

        $this->school->forceFill([
            'schedule_settings' => ['teaching_days' => [1, 2, 3]],
        ])->save();

        TimetablePeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Breakfast',
            'kind' => 'breakfast',
            'starts_at' => '07:00',
            'ends_at' => '07:30',
            'sequence' => 1,
        ]);
        TimetablePeriod::create([
            'school_id' => $this->school->id,
            'name' => 'P1',
            'kind' => 'class',
            'starts_at' => '08:00',
            'ends_at' => '08:40',
            'sequence' => 2,
        ]);
        TimetablePeriod::create([
            'school_id' => $this->school->id,
            'name' => 'P2',
            'kind' => 'class',
            'starts_at' => '08:40',
            'ends_at' => '09:20',
            'sequence' => 3,
        ]);

        TeachingAssignment::create([
            'school_id' => $this->school->id,
            'user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'academic_year_id' => $year->id,
            'status' => 'active',
            'periods_per_week' => 3,
        ]);

        $result = app(TimetableGenerator::class)->generate($this->school, $class->id, $year->id, true);

        $this->assertSame(3, $result['created']);
        $this->assertSame(0, count($result['unplaced']));
        $this->assertSame(3, TimetableSlot::query()->where('class_id', $class->id)->count());
        $this->assertFalse(
            TimetableSlot::query()
                ->whereHas('period', fn ($q) => $q->where('kind', 'breakfast'))
                ->exists()
        );
    }

    public function test_bursar_can_filter_demanded_and_cleared_invoices(): void
    {
        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'level' => 'primary',
            'name' => 'P4',
            'code' => 'FEE-P4-'.uniqid(),
        ]);
        $owing = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'status' => 'active',
            'full_name' => 'Owing Learner',
        ]);
        $cleared = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'status' => 'active',
            'full_name' => 'Cleared Learner',
        ]);

        FeeInvoice::create([
            'school_id' => $this->school->id,
            'student_id' => $owing->id,
            'reference' => 'INV-DEM-1',
            'amount' => 100000,
            'balance' => 40000,
            'status' => 'partial',
        ]);
        FeeInvoice::create([
            'school_id' => $this->school->id,
            'student_id' => $cleared->id,
            'reference' => 'INV-CLR-1',
            'amount' => 100000,
            'balance' => 0,
            'status' => 'paid',
        ]);

        $demanded = $this->sessionFor($this->bursar)->get(route('app.fees.index', ['status' => 'demanded']));
        $demanded->assertOk();
        $demanded->assertSee('INV-DEM-1', false);
        $demanded->assertDontSee('INV-CLR-1', false);
        $demanded->assertSee('Demanded', false);

        $clearedResp = $this->sessionFor($this->bursar)->get(route('app.fees.index', ['status' => 'cleared']));
        $clearedResp->assertOk();
        $clearedResp->assertSee('INV-CLR-1', false);
        $clearedResp->assertDontSee('INV-DEM-1', false);
    }

    public function test_timetable_page_shows_schedule_setup_steps(): void
    {
        $response = $this->sessionFor($this->admin)->get(route('app.timetable.index'));
        $response->assertOk();
        $response->assertSee('Teaching days', false);
        $response->assertSee('Daily blocks', false);
        $response->assertSee('Generate from teaching', false);
    }

    public function test_bursar_home_shows_demanded_and_cleared_shortcuts(): void
    {
        $response = $this->sessionFor($this->bursar)->get(route('app.home'));
        $response->assertOk();
        $response->assertSee('Demanded', false);
        $response->assertSee('Cleared', false);
        $response->assertSee('Demanded fees', false);
        $response->assertSee('Cleared fees', false);
    }
}
