<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\AcademicYear;
use App\Models\AssessmentPeriod;
use App\Models\FeeInvoice;
use App\Models\FeeStructure;
use App\Models\Guardianship;
use App\Models\Mark;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\Assessment\GradingSchemeService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreWorkflowAssessmentAndFeesTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $this->admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);
    }

    private function actingAsAdmin(): static
    {
        app(TenantContext::class)->forSchool($this->school->id);

        return $this->actingAs($this->admin);
    }

    public function test_bulk_invoices_are_idempotent(): void
    {
        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'level' => 'primary',
            'name' => 'P3',
            'code' => 'P3-FEE',
        ]);
        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026-fees',
            'starts_on' => '2026-02-02',
            'ends_on' => '2026-12-04',
            'is_current' => true,
        ]);
        $student = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'status' => 'active',
        ]);
        $structure = FeeStructure::create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'name' => 'Term II tuition',
            'amount' => 100000,
            'currency' => 'UGX',
            'is_active' => true,
        ]);

        $this->actingAsAdmin()->post(route('app.fees.invoices.bulk'), [
            'fee_structure_id' => $structure->id,
            'class_id' => $class->id,
        ])->assertRedirect();

        $this->actingAsAdmin()->post(route('app.fees.invoices.bulk'), [
            'fee_structure_id' => $structure->id,
            'class_id' => $class->id,
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertSame(1, FeeInvoice::where('student_id', $student->id)->where('fee_structure_id', $structure->id)->where('status', '!=', 'void')->count());
    }

    public function test_score_82_becomes_d1_and_parents_only_see_published_marks(): void
    {
        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'level' => 'primary',
            'name' => 'S2',
            'code' => 'S2-ASS',
        ]);
        $subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Mathematics', 'code' => 'MTH-CORE']);
        $student = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'status' => 'active',
        ]);
        $period = AssessmentPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Midterm',
            'max_score' => 100,
            'status' => 'mark_entry_open',
        ]);

        $response = $this->actingAsAdmin()->post(route('app.assessment.marks.store'), [
            'period_id' => $period->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'rows' => [[
                'student_id' => $student->id,
                'score' => 82,
                'grade' => 'XX',
            ]],
        ]);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $mark = Mark::where('student_id', $student->id)->firstOrFail();
        $this->assertSame('D1', $mark->grade);
        $this->assertSame(1, (int) $mark->points);
        $this->assertSame('Excellent', $mark->remark);

        $parent = User::where('email', 'parent@standrews.test')->firstOrFail();
        Guardianship::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'guardian_user_id' => $parent->id,
            'is_primary' => true,
        ]);

        $this->actingAs($parent);
        app(TenantContext::class)->forSchool($this->school->id);
        $this->get(route('app.portal.results', ['student_id' => $student->id]))
            ->assertOk()
            ->assertDontSee('D1');

        $this->actingAsAdmin()->post(route('app.assessment.periods.transition', $period), ['to' => 'mark_entry_closed']);
        $this->actingAsAdmin()->post(route('app.assessment.periods.transition', $period), ['to' => 'review']);
        $this->actingAsAdmin()->post(route('app.assessment.periods.transition', $period), ['to' => 'published']);

        $this->actingAs($parent);
        app(TenantContext::class)->forSchool($this->school->id);
        $this->get(route('app.portal.results', ['student_id' => $student->id]))
            ->assertOk()
            ->assertSee('D1');

        $this->actingAsAdmin()->post(route('app.assessment.periods.transition', $period), ['to' => 'locked']);
        $this->actingAsAdmin()->post(route('app.assessment.marks.store'), [
            'period_id' => $period->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'rows' => [['student_id' => $student->id, 'score' => 50]],
        ])->assertSessionHasErrors('period_id');
    }

    public function test_uneb_scheme_grades_boundary_scores(): void
    {
        $scheme = app(GradingSchemeService::class)->seedDefault($this->school->id);
        $svc = app(GradingSchemeService::class);

        $this->assertSame('D1', $svc->gradeFor(80, $scheme)['grade']);
        $this->assertSame('D2', $svc->gradeFor(75, $scheme)['grade']);
        $this->assertSame('F9', $svc->gradeFor(0, $scheme)['grade']);
    }
}
