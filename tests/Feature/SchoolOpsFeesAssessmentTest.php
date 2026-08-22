<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Mail\FeePaymentReceiptMail;
use App\Models\AcademicYear;
use App\Models\AssessmentPeriod;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Guardianship;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use App\Support\AssessmentSet;
use App\Support\FeeKind;
use App\Support\Residency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\TeacherInviteLoad;
use Tests\TestCase;

class SchoolOpsFeesAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $bursar;

    private User $director;

    private User $dos;

    private User $classTeacher;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);
        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $this->admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        $this->bursar = User::where('email', 'bursar@standrews.test')->firstOrFail();
        $this->director = User::where('email', 'director@standrews.test')->firstOrFail();
        $this->dos = User::where('email', 'dos@standrews.test')->firstOrFail();
        $this->classTeacher = User::where('email', 'classteacher@standrews.test')->firstOrFail();
        $this->teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);
    }

    private function actingAsInSchool(User $user): static
    {
        app(TenantContext::class)->forSchool($this->school->id);

        return $this->actingAs($user)->withSession([
            TenantContext::SESSION_SCHOOL_ID => $this->school->id,
        ]);
    }

    public function test_bursar_invoices_day_and_boarding_structures_and_learner_group_fees(): void
    {
        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'level' => 'primary',
            'name' => 'P6-fee',
            'code' => 'P6FEE-'.uniqid(),
        ]);
        $day = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'status' => 'active',
            'full_name' => 'Day Learner',
            'residency' => Residency::DAY,
        ]);
        $boarder = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'status' => 'active',
            'full_name' => 'Boarder Learner',
            'residency' => Residency::BOARDING,
        ]);

        $this->actingAsInSchool($this->bursar)->post(route('app.fees.structures.store'), [
            'name' => 'P6 day tuition',
            'amount' => 200000,
            'kind' => FeeKind::TUITION,
            'residency' => Residency::DAY,
            'applies_to' => 'class',
            'class_id' => $class->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAsInSchool($this->bursar)->post(route('app.fees.structures.store'), [
            'name' => 'P6 boarding tuition',
            'amount' => 450000,
            'kind' => FeeKind::TUITION,
            'residency' => Residency::BOARDING,
            'applies_to' => 'class',
            'class_id' => $class->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAsInSchool($this->bursar)->post(route('app.fees.structures.store'), [
            'name' => 'Van',
            'amount' => 80000,
            'kind' => FeeKind::TRANSPORT,
            'residency' => Residency::ANY,
            'applies_to' => 'learners',
            'student_ids' => [$day->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $dayTuition = FeeStructure::query()->where('name', 'P6 day tuition')->firstOrFail();
        $boardTuition = FeeStructure::query()->where('name', 'P6 boarding tuition')->firstOrFail();
        $van = FeeStructure::query()->where('name', 'Van')->firstOrFail();

        $this->actingAsInSchool($this->bursar)->post(route('app.fees.invoices.bulk'), [
            'fee_structure_id' => $dayTuition->id,
            'class_id' => $class->id,
        ])->assertRedirect();
        $this->actingAsInSchool($this->bursar)->post(route('app.fees.invoices.bulk'), [
            'fee_structure_id' => $boardTuition->id,
            'class_id' => $class->id,
        ])->assertRedirect();
        $this->actingAsInSchool($this->bursar)->post(route('app.fees.invoices.bulk'), [
            'fee_structure_id' => $van->id,
        ])->assertRedirect();

        $this->assertTrue(FeeInvoice::query()->where('student_id', $day->id)->where('fee_structure_id', $dayTuition->id)->exists());
        $this->assertFalse(FeeInvoice::query()->where('student_id', $boarder->id)->where('fee_structure_id', $dayTuition->id)->exists());
        $this->assertTrue(FeeInvoice::query()->where('student_id', $boarder->id)->where('fee_structure_id', $boardTuition->id)->exists());
        $this->assertFalse(FeeInvoice::query()->where('student_id', $day->id)->where('fee_structure_id', $boardTuition->id)->exists());
        $this->assertTrue(FeeInvoice::query()->where('student_id', $day->id)->where('fee_structure_id', $van->id)->exists());
        $this->assertFalse(FeeInvoice::query()->where('student_id', $boarder->id)->where('fee_structure_id', $van->id)->exists());
    }

    public function test_director_can_view_fees_but_cannot_create_structures(): void
    {
        $this->actingAsInSchool($this->director)->get(route('app.fees.index'))->assertOk();
        $this->actingAsInSchool($this->director)->post(route('app.fees.structures.store'), [
            'name' => 'Should fail',
            'amount' => 1,
            'kind' => FeeKind::OTHER,
            'residency' => Residency::ANY,
            'applies_to' => 'class',
            'class_id' => SchoolClass::query()->where('school_id', $this->school->id)->value('id'),
        ])->assertForbidden();
    }

    public function test_bursar_records_payment_in_popup_flow_and_can_print_or_email_receipt(): void
    {
        Mail::fake();
        $student = Student::where('school_id', $this->school->id)->firstOrFail();
        $parent = User::where('email', 'parent@standrews.test')->firstOrFail();
        Guardianship::firstOrCreate([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'guardian_user_id' => $parent->id,
        ], ['relationship' => 'parent', 'is_primary' => true]);

        $invoice = FeeInvoice::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'reference' => 'INV-RCPT-1',
            'amount' => 50000,
            'balance' => 50000,
            'status' => 'open',
        ]);

        $this->actingAsInSchool($this->bursar)->get(route('app.fees.invoices'))
            ->assertOk()
            ->assertSee('Record payment', false);

        $this->actingAsInSchool($this->bursar)->get(route('app.fees.cleared'))->assertOk();

        $this->actingAsInSchool($this->bursar)->post(route('app.fees.payments.store'), [
            'invoice_id' => $invoice->id,
            'amount' => 50000,
            'method' => 'cash',
        ])->assertRedirect()->assertSessionHas('receipt_id');

        $payment = FeePayment::query()->where('invoice_id', $invoice->id)->firstOrFail();
        $this->actingAsInSchool($this->bursar)
            ->get(route('app.fees.receipts.show', $payment))
            ->assertOk()
            ->assertSee('Official receipt', false);

        $this->actingAsInSchool($this->bursar)
            ->post(route('app.fees.receipts.email', $payment))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        Mail::assertSent(FeePaymentReceiptMail::class);
    }

    public function test_class_teacher_updates_homeroom_bio_and_restreams_siblings_only(): void
    {
        $east = SchoolClass::create([
            'school_id' => $this->school->id,
            'level' => 'primary',
            'name' => 'P6-stream',
            'stream' => 'East',
            'code' => 'P6E-'.uniqid(),
        ]);
        $west = SchoolClass::create([
            'school_id' => $this->school->id,
            'level' => 'primary',
            'name' => 'P6-stream',
            'stream' => 'West',
            'code' => 'P6W-'.uniqid(),
        ]);
        $other = SchoolClass::create([
            'school_id' => $this->school->id,
            'level' => 'primary',
            'name' => 'P7-stream',
            'stream' => 'East',
            'code' => 'P7E-'.uniqid(),
        ]);
        AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026-stream',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'is_current' => true,
        ]);
        $mine = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $east->id,
            'status' => 'active',
            'full_name' => 'Homeroom Child',
        ]);
        $theirs = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $other->id,
            'status' => 'active',
            'full_name' => 'Other Class Child',
        ]);
        RoleAssignment::query()
            ->where('user_id', $this->classTeacher->id)
            ->where('school_id', $this->school->id)
            ->update(['class_id' => $east->id]);

        $this->actingAsInSchool($this->classTeacher)
            ->get(route('app.students.edit', $mine))
            ->assertOk();
        $this->actingAsInSchool($this->classTeacher)
            ->get(route('app.students.edit', $theirs))
            ->assertForbidden();

        $this->actingAsInSchool($this->classTeacher)->put(route('app.students.update', $mine), [
            'full_name' => 'Homeroom Child Updated',
            'residency' => Residency::BOARDING,
            'nationality' => 'Uganda',
            'class_id' => $east->id,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Homeroom Child Updated', $mine->fresh()->full_name);
        $this->assertSame(Residency::BOARDING, $mine->fresh()->residency);

        $this->actingAsInSchool($this->classTeacher)->put(route('app.students.update', $mine), [
            'full_name' => 'Homeroom Child Updated',
            'residency' => Residency::BOARDING,
            'nationality' => 'Uganda',
            'class_id' => $west->id,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($west->id, (int) $mine->fresh()->class_id);

        $this->actingAsInSchool($this->classTeacher)->put(route('app.students.update', $mine->fresh()), [
            'full_name' => 'Homeroom Child Updated',
            'residency' => Residency::BOARDING,
            'nationality' => 'Uganda',
            'class_id' => $other->id,
        ])->assertForbidden();

        $this->actingAsInSchool($this->classTeacher)
            ->delete(route('app.students.destroy', $mine))
            ->assertForbidden();
    }

    public function test_class_teacher_revokes_upload_only_after_deadline(): void
    {
        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'level' => 'primary',
            'name' => 'P5-lock',
            'code' => 'P5L-'.uniqid(),
        ]);
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Science-lock',
            'code' => 'SCI-L-'.uniqid(),
        ]);
        $year = AcademicYear::query()->where('school_id', $this->school->id)->where('is_current', true)->first()
            ?? AcademicYear::create([
                'school_id' => $this->school->id,
                'name' => '2026-lock',
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-12-31',
                'is_current' => true,
            ]);
        $student = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'status' => 'active',
        ]);
        TeachingAssignment::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacher->id,
            'academic_year_id' => $year->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'status' => 'active',
        ]);
        RoleAssignment::query()
            ->where('user_id', $this->classTeacher->id)
            ->where('school_id', $this->school->id)
            ->update(['class_id' => $class->id]);

        $open = AssessmentPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'MOT open',
            'kind' => AssessmentSet::MOT,
            'max_score' => 100,
            'status' => 'draft',
            'entry_deadline' => now()->addDay()->toDateString(),
        ]);
        $closed = AssessmentPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'BOT closed',
            'kind' => AssessmentSet::BOT,
            'max_score' => 100,
            'status' => 'draft',
            'entry_deadline' => now()->subDay()->toDateString(),
        ]);

        $this->actingAsInSchool($this->classTeacher)->get(route('app.teaching.homeroom'))
            ->assertOk()
            ->assertSee('BOT', false)
            ->assertSee('MOT', false)
            ->assertSee('Science-lock', false);

        $this->actingAsInSchool($this->classTeacher)->post(route('app.assessment.marksheets.revoke'), [
            'period_id' => $open->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
        ])->assertRedirect()->assertSessionHasErrors('marksheet');

        $this->actingAsInSchool($this->classTeacher)->post(route('app.assessment.marksheets.revoke'), [
            'period_id' => $closed->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAsInSchool($this->teacher)->post(route('app.assessment.marks.store'), [
            'period_id' => $closed->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'rows' => [
                ['student_id' => $student->id, 'score' => 70],
            ],
        ])->assertForbidden();
    }

    public function test_dos_custom_test_appears_on_class_teacher_dashboard(): void
    {
        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'level' => 'primary',
            'name' => 'P5-custom',
            'code' => 'P5C-'.uniqid(),
        ]);
        RoleAssignment::query()
            ->where('user_id', $this->classTeacher->id)
            ->where('school_id', $this->school->id)
            ->update(['class_id' => $class->id]);

        $this->actingAsInSchool($this->dos)->post(route('app.assessment.periods.store'), [
            'name' => 'Holiday catch-up',
            'kind' => AssessmentSet::CUSTOM,
            'max_score' => 50,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAsInSchool($this->classTeacher)->get(route('app.teaching.homeroom'))
            ->assertOk()
            ->assertSee('Holiday catch-up', false);
    }

    public function test_teacher_invite_requires_subject_and_classes_and_subject_teacher_sees_my_classes(): void
    {
        $load = TeacherInviteLoad::ensure($this->school);

        $this->actingAsInSchool($this->admin)->post(route('app.staff.store'), [
            'full_name' => 'No Load Teacher',
            'email' => 'noload@standrews.test',
            'gender' => 'male',
            'nin' => 'CM12345678901',
            'role_keys' => ['subject_teacher'],
        ])->assertRedirect()->assertSessionHasErrors('teaching_assignments');

        $this->actingAsInSchool($this->admin)->post(route('app.staff.store'), [
            'full_name' => 'Loaded Teacher',
            'email' => 'loaded@standrews.test',
            'gender' => 'female',
            'nin' => 'CF98765432109',
            'role_keys' => ['subject_teacher'],
            'teaching_assignments' => $load['teaching_assignments'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $invited = User::where('email', 'loaded@standrews.test')->firstOrFail();
        $this->assertDatabaseHas('teaching_assignments', [
            'user_id' => $invited->id,
            'subject_id' => $load['subject']->id,
            'class_id' => $load['class']->id,
            'status' => 'active',
        ]);

        TeachingAssignment::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacher->id,
            'academic_year_id' => $load['year']->id,
            'class_id' => $load['class']->id,
            'subject_id' => $load['subject']->id,
            'status' => 'active',
        ]);

        $this->actingAsInSchool($this->teacher)->get(route('app.teaching.mine'))
            ->assertOk()
            ->assertSee('My classes', false)
            ->assertSee($load['subject']->name, false);

        $this->actingAsInSchool($this->classTeacher)
            ->get(route('app.assessment.marks'))
            ->assertForbidden();
    }

    public function test_school_admin_can_set_class_stream_and_director_sees_emis_census(): void
    {
        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'level' => 'primary',
            'name' => 'P4-admin',
            'code' => 'P4A-'.uniqid(),
        ]);
        $this->actingAsInSchool($this->admin)->put(route('app.classes.update', $class), [
            'stream' => 'Blue',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Blue', $class->fresh()->stream);

        Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'status' => 'active',
            'gender' => 'female',
            'nationality' => 'Uganda',
        ]);

        $this->actingAsInSchool($this->director)->get(route('app.home'))
            ->assertOk()
            ->assertSee('Learners', false)
            ->assertSee('Teaching staff', false)
            ->assertSee('NIN tracking', false);
    }
}
