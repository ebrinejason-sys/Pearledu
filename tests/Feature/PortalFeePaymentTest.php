<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Guardianship;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Fees\FeePaymentService;
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

        $this->actingAsInSchool($this->bursar)->post(route('app.fees.payments.reject', $payment));

        $payment->refresh();
        $this->invoice->refresh();

        $this->assertSame('rejected', $payment->status);
        $this->assertSame('100000.00', $this->invoice->balance);
        $this->assertSame('open', $this->invoice->status);
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
}
