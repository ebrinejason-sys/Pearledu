<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\PromotionBatch;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\Promotions\PromotionService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionCommitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);
    }

    public function test_commit_creates_new_enrollments_and_updates_class(): void
    {
        $school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forSchool($school->id);

        $fromYear = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2025',
            'starts_on' => '2025-01-01',
            'ends_on' => '2025-12-31',
            'is_current' => false,
        ]);
        $toYear = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'is_current' => true,
        ]);

        $fromClass = SchoolClass::create(['school_id' => $school->id, 'level' => 'primary', 'name' => 'P6 Promo', 'code' => 'P6-PROM']);
        $toClass = SchoolClass::create(['school_id' => $school->id, 'level' => 'primary', 'name' => 'P7 Promo', 'code' => 'P7-PROM']);

        $student = Student::factory()->create([
            'school_id' => $school->id,
            'full_name' => 'Promote Me',
            'class_id' => $fromClass->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'class_id' => $fromClass->id,
            'academic_year_id' => $fromYear->id,
            'status' => 'active',
        ]);

        $svc = app(PromotionService::class);
        $batch = $svc->createBatch([
            'school_id' => $school->id,
            'from_year_id' => $fromYear->id,
            'to_year_id' => $toYear->id,
            'created_by' => User::where('email', 'admin@standrews.test')->value('id'),
            'items' => [[
                'student_id' => $student->id,
                'from_class_id' => $fromClass->id,
                'to_class_id' => $toClass->id,
                'outcome' => 'promote',
            ]],
        ]);

        $svc->commit($batch);

        $this->assertSame('committed', $batch->fresh()->status);
        $this->assertSame('completed', Enrollment::where('academic_year_id', $fromYear->id)->value('status'));
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'academic_year_id' => $toYear->id,
            'class_id' => $toClass->id,
            'status' => 'active',
        ]);
        $this->assertSame($toClass->id, $student->fresh()->class_id);
    }

    public function test_admin_can_commit_via_http(): void
    {
        $school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        app(TenantContext::class)->forSchool($school->id);

        $fromYear = AcademicYear::create([
            'school_id' => $school->id,
            'name' => 'FY-A',
            'starts_on' => '2024-01-01',
            'ends_on' => '2024-12-31',
        ]);
        $toYear = AcademicYear::create([
            'school_id' => $school->id,
            'name' => 'FY-B',
            'starts_on' => '2025-01-01',
            'ends_on' => '2025-12-31',
        ]);
        $fromClass = SchoolClass::create(['school_id' => $school->id, 'level' => 'primary', 'name' => 'C1', 'code' => 'C1']);
        $toClass = SchoolClass::create(['school_id' => $school->id, 'level' => 'primary', 'name' => 'C2', 'code' => 'C2']);
        $student = Student::factory()->create(['school_id' => $school->id, 'class_id' => $fromClass->id]);
        Enrollment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'class_id' => $fromClass->id,
            'academic_year_id' => $fromYear->id,
            'status' => 'active',
        ]);

        $batch = app(PromotionService::class)->createBatch([
            'school_id' => $school->id,
            'from_year_id' => $fromYear->id,
            'to_year_id' => $toYear->id,
            'items' => [[
                'student_id' => $student->id,
                'from_class_id' => $fromClass->id,
                'to_class_id' => $toClass->id,
                'outcome' => 'promote',
            ]],
        ]);

        $this->actingAs($admin)
            ->post(route('app.promotions.commit', $batch))
            ->assertRedirect();

        $this->assertSame('committed', PromotionBatch::find($batch->id)->status);
    }
}
