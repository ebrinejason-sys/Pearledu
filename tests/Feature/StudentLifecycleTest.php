<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Mail\GuardianInvitationMail;
use App\Models\AcademicYear;
use App\Models\AdmissionApplication;
use App\Models\Enrollment;
use App\Models\FeeInvoice;
use App\Models\FeeStructure;
use App\Models\Guardianship;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StudentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $this->admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);

        $this->class = SchoolClass::firstOrCreate(
            ['school_id' => $this->school->id, 'code' => 'P4-LIFE'],
            ['level' => 'primary', 'name' => 'P4'],
        );

        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'starts_on' => '2026-02-02',
            'ends_on' => '2026-12-04',
            'is_current' => true,
        ]);
    }

    private function actingAsAdmin(): static
    {
        app(TenantContext::class)->forSchool($this->school->id);

        return $this->actingAs($this->admin);
    }

    public function test_admit_student_creates_enrollment_guardian_and_invoices_once(): void
    {
        Mail::fake();

        Term::create([
            'school_id' => $this->school->id,
            'academic_year_id' => AcademicYear::where('school_id', $this->school->id)->value('id'),
            'name' => 'Term I',
            'sequence' => 1,
            'starts_on' => '2026-02-02',
            'ends_on' => '2026-05-08',
        ]);

        $structure = FeeStructure::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'name' => 'Tuition',
            'amount' => 800000,
            'currency' => 'UGX',
            'is_active' => true,
        ]);

        $application = AdmissionApplication::create([
            'school_id' => $this->school->id,
            'applicant_name' => 'Tushabe Ebrine',
            'guardian_name' => 'Parent Ebrine',
            'guardian_email' => 'parent-ebrine@example.test',
            'guardian_phone' => '0770000000',
            'requested_class_id' => $this->class->id,
            'status' => 'pending',
        ]);

        $this->actingAsAdmin()
            ->post(route('app.admissions.decide', $application), ['decision' => 'enrolled'])
            ->assertRedirect();

        $application->refresh();
        $this->assertSame('enrolled', $application->status);
        $this->assertNotNull($application->student_id);

        $student = Student::findOrFail($application->student_id);
        $this->assertSame($this->class->id, $student->class_id);
        $this->assertSame(1, Enrollment::where('student_id', $student->id)->count());
        $this->assertSame($this->class->id, $student->currentEnrollment()?->class_id);
        $this->assertSame(1, Guardianship::where('student_id', $student->id)->count());
        $this->assertSame(1, FeeInvoice::where('student_id', $student->id)->where('fee_structure_id', $structure->id)->count());
        Mail::assertSent(GuardianInvitationMail::class);

        $this->actingAsAdmin()
            ->post(route('app.admissions.decide', $application), ['decision' => 'enrolled'])
            ->assertRedirect();

        $this->assertSame(1, Student::where('full_name', 'Tushabe Ebrine')->count());
        $this->assertSame(1, Enrollment::where('student_id', $student->id)->count());
    }

    public function test_enrollment_controller_updates_cached_class_id_only_through_lifecycle(): void
    {
        $student = Student::factory()->create([
            'school_id' => $this->school->id,
            'full_name' => 'Place Me',
            'status' => 'active',
        ]);
        $yearId = AcademicYear::where('school_id', $this->school->id)->where('is_current', true)->value('id');

        $this->actingAsAdmin()->post(route('app.enrollments.store'), [
            'student_id' => $student->id,
            'class_id' => $this->class->id,
            'academic_year_id' => $yearId,
        ])->assertRedirect();

        $this->assertSame($this->class->id, $student->fresh()->class_id);
        $this->assertSame($this->class->id, $student->fresh()->currentClass()?->id);
    }
}
