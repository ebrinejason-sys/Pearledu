<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\AuditLog;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Guardianship;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Fees\FeePaymentService;
use App\Services\Fees\StudentLedgerService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalFeePaymentTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $parent;

    private User $bursar;

    private Student $student;

    private FeeInvoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);

        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);

        $this->parent = User::where('email', 'parent@standrews.test')->firstOrFail();
        $this->bursar = User::where('email', 'bursar@standrews.test')->firstOrFail();
        $this->student = Student::where('school_id', $this->school->id)->firstOrFail();

        Guardianship::firstOrCreate([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'guardian_user_id' => $this->parent->id,
        ], [
            'relationship' => 'parent',
            'is_primary' => true,
        ]);

        $this->invoice = FeeInvoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'reference' => 'INV-TEST-1',
            'amount' => 100000,
            'balance' => 100000,
            'status' => 'open',
        ]);
    }

    private function actingAsInSchool(User $user): static
    {
        app(TenantContext::class)->forSchool($this->school->id);

        return $this->actingAs($user)->withSession([
            TenantContext::SESSION_SCHOOL_ID => $this->school->id,
        ]);
    }

    public function test_parent_payment_stays_pending_and_does_not_clear_balance(): void
    {
        $response = $this->actingAsInSchool($this->parent)->post(
            route('app.portal.fees.pay', $this->invoice),
            [
                'amount' => 40000,
                'method' => 'mtn_momo',
                'provider_ref' => 'MOMO-123',
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->invoice->refresh();
        $this->assertSame('100000.00', $this->invoice->balance);
        $this->assertSame('open', $this->invoice->status);

        $payment = FeePayment::where('invoice_id', $this->invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('40000.00', $payment->amount);
    }

    public function test_staff_confirm_applies_balance(): void
    {
        $payment = app(FeePaymentService::class)->record([
            'school_id' => $this->school->id,
            'invoice_id' => $this->invoice->id,
            'amount' => 40000,
            'method' => 'mtn_momo',
            'provider_ref' => 'MOMO-123',
            'recorded_by' => $this->parent->id,
        ], confirmImmediately: false);

        $response = $this->actingAsInSchool($this->bursar)->post(
            route('app.fees.payments.confirm', $payment),
        );

        $response->assertRedirect();
        $payment->refresh();
        $this->invoice->refresh();

        $this->assertSame('confirmed', $payment->status);
        $this->assertSame('60000.00', $this->invoice->balance);
        $this->assertSame('partial', $this->invoice->status);
    }

    public function test_staff_reject_leaves_balance_unchanged(): void
    {
        $payment = app(FeePaymentService::class)->record([
            'school_id' => $this->school->id,
            'invoice_id' => $this->invoice->id,
            'amount' => 40000,
            'method' => 'bank',
            'recorded_by' => $this->parent->id,
        ], confirmImmediately: false);

        $this->actingAsInSchool($this->bursar)
            ->get(route('app.fees.invoices'))
            ->assertOk()
            ->assertSee('Reject', false);

        $this->actingAsInSchool($this->bursar)->post(route('app.fees.payments.reject', $payment), [
            'reason' => 'Duplicate parent submission',
        ]);

        $payment->refresh();
        $this->invoice->refresh();

        $this->assertSame('rejected', $payment->status);
        $this->assertSame('Duplicate parent submission', $payment->decision_reason);
        $this->assertSame('100000.00', $this->invoice->balance);
        $this->assertSame('open', $this->invoice->status);
        $this->assertTrue(AuditLog::query()->where('action', 'fees.payment.rejected')->where('entity_id', $payment->id)->exists());
    }

    public function test_reject_requires_a_reason(): void
    {
        $payment = app(FeePaymentService::class)->record([
            'school_id' => $this->school->id,
            'invoice_id' => $this->invoice->id,
            'amount' => 40000,
            'method' => 'bank',
            'recorded_by' => $this->parent->id,
        ], confirmImmediately: false);

        $this->actingAsInSchool($this->bursar)
            ->post(route('app.fees.payments.reject', $payment), ['reason' => 'no'])
            ->assertSessionHasErrors('reason');

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_staff_record_confirms_immediately(): void
    {
        $response = $this->actingAsInSchool($this->bursar)->post(route('app.fees.payments.store'), [
            'invoice_id' => $this->invoice->id,
            'amount' => 100000,
            'method' => 'cash',
        ]);

        $response->assertRedirect();
        $this->invoice->refresh();
        $payment = FeePayment::where('invoice_id', $this->invoice->id)->first();

        $this->assertSame('confirmed', $payment->status);
        $this->assertSame('0.00', $this->invoice->balance);
        $this->assertSame('paid', $this->invoice->status);
    }

    public function test_pending_amount_blocks_over_submission(): void
    {
        app(FeePaymentService::class)->record([
            'school_id' => $this->school->id,
            'invoice_id' => $this->invoice->id,
            'amount' => 80000,
            'method' => 'mtn_momo',
            'recorded_by' => $this->parent->id,
        ], confirmImmediately: false);

        $response = $this->actingAsInSchool($this->parent)->post(
            route('app.portal.fees.pay', $this->invoice),
            ['amount' => 30000, 'method' => 'mtn_momo'],
        );

        $response->assertSessionHasErrors('amount');
        $this->assertSame(1, FeePayment::where('invoice_id', $this->invoice->id)->count());
    }

    public function test_bursar_reverse_requires_reason_restores_balance_and_is_audited(): void
    {
        $payment = app(FeePaymentService::class)->record([
            'school_id' => $this->school->id,
            'invoice_id' => $this->invoice->id,
            'amount' => 100000,
            'method' => 'cash',
            'recorded_by' => $this->bursar->id,
        ], confirmImmediately: true);

        $this->actingAsInSchool($this->bursar)
            ->get(route('app.fees.cleared'))
            ->assertOk()
            ->assertSee('Reverse', false);

        $this->actingAsInSchool($this->bursar)
            ->from(route('app.fees.cleared'))
            ->post(route('app.fees.payments.reverse', $payment), ['reason' => 'Posted to the wrong learner'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $payment->refresh();
        $this->invoice->refresh();
        $this->assertSame('reversed', $payment->status);
        $this->assertSame('Posted to the wrong learner', $payment->decision_reason);
        $this->assertSame('100000.00', $this->invoice->balance);
        $this->assertSame('open', $this->invoice->status);
        $this->assertTrue(
            FeePayment::query()->where('reverses_payment_id', $payment->id)->where('status', 'confirmed')->exists()
        );
        $this->assertTrue(AuditLog::query()->where('action', 'fees.payment.reversed')->where('entity_id', $payment->id)->exists());

        $statement = app(StudentLedgerService::class)->statement($this->student->fresh());
        $this->assertSame(100000.0, $statement['balance']);
        $this->assertTrue(
            collect($statement['lines'])->contains(fn ($line) => str_contains((string) $line['description'], 'Posted to the wrong learner'))
        );
    }

    public function test_director_and_teacher_cannot_reverse_or_reject_payments(): void
    {
        $director = User::where('email', 'director@standrews.test')->firstOrFail();
        $teacher = User::where('email', 'teacher@standrews.test')->firstOrFail();
        $head = User::where('email', 'head@standrews.test')->firstOrFail();

        $confirmed = app(FeePaymentService::class)->record([
            'school_id' => $this->school->id,
            'invoice_id' => $this->invoice->id,
            'amount' => 25000,
            'method' => 'cash',
            'recorded_by' => $this->bursar->id,
        ], confirmImmediately: true);

        $pending = app(FeePaymentService::class)->record([
            'school_id' => $this->school->id,
            'invoice_id' => $this->invoice->id,
            'amount' => 10000,
            'method' => 'bank',
            'recorded_by' => $this->parent->id,
        ], confirmImmediately: false);

        foreach ([$director, $teacher, $head] as $actor) {
            $this->actingAsInSchool($actor)
                ->post(route('app.fees.payments.reverse', $confirmed), [
                    'reason' => 'Trying to reverse without bursar role',
                ])
                ->assertForbidden();
            $this->actingAsInSchool($actor)
                ->post(route('app.fees.payments.reject', $pending), [
                    'reason' => 'Trying to reject without bursar role',
                ])
                ->assertForbidden();
        }

        $this->assertSame('confirmed', $confirmed->fresh()->status);
        $this->assertSame('pending', $pending->fresh()->status);

        $this->actingAsInSchool($director)
            ->get(route('app.fees.invoices'))
            ->assertOk()
            ->assertDontSee('Reverse this payment', false)
            ->assertDontSee('Reject this payment', false);
    }
}
