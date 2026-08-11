<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveTenant;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Guardianship;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\SchoolPay\SchoolPayClient;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SchoolPayIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $parent;

    private Student $student;

    private FeeInvoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutMiddleware(ResolveTenant::class);

        $this->school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);

        $this->school->forceFill([
            'schoolpay_enabled' => true,
            'schoolpay_school_code' => '809',
            'schoolpay_api_password' => 'test-api-password',
        ])->save();

        $this->parent = User::where('email', 'parent@standrews.test')->firstOrFail();
        $this->student = Student::where('school_id', $this->school->id)->firstOrFail();
        $this->student->forceFill(['schoolpay_payment_code' => '1005416321'])->save();

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
            'reference' => 'INV-SP-1',
            'amount' => 50000,
            'balance' => 50000,
            'status' => 'open',
        ]);
    }

    private function actingAsParent(): static
    {
        app(TenantContext::class)->forSchool($this->school->id);

        return $this->actingAs($this->parent)->withSession([
            TenantContext::SESSION_SCHOOL_ID => $this->school->id,
        ]);
    }

    public function test_parent_can_initiate_schoolpay_adhoc_payment(): void
    {
        Http::fake([
            '*/AndroidRS/AdhocPayments/Register/*' => Http::response([
                'paymentReference' => '21AD100011',
                'returnCode' => 0,
                'returnMessage' => 'Request has been processed',
                'status' => 'PENDING',
            ], 200),
            '*/AndroidRS/AdhocPayments/Request/*' => Http::response([
                'paymentReference' => '21AD100011',
                'returnCode' => 0,
                'returnMessage' => 'Debit request sent',
                'status' => 'PENDING',
            ], 200),
        ]);

        $response = $this->actingAsParent()->post(
            route('app.portal.fees.schoolpay', $this->invoice),
            ['amount' => 20000, 'phone' => '0770123456'],
        );

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $payment = FeePayment::where('invoice_id', $this->invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('schoolpay', $payment->method);
        $this->assertSame('21AD100011', $payment->schoolpay_reference);
        $this->assertSame('50000.00', $this->invoice->fresh()->balance);
        $this->assertNotNull($payment->external_reference);
    }

    public function test_adhoc_callback_confirms_pending_payment(): void
    {
        app(TenantContext::class)->forSchool($this->school->id);

        $payment = FeePayment::create([
            'school_id' => $this->school->id,
            'invoice_id' => $this->invoice->id,
            'amount' => 20000,
            'method' => 'schoolpay',
            'status' => 'pending',
            'external_reference' => 'PETESTREF01',
            'schoolpay_reference' => '21AD100011',
            'recorded_by' => $this->parent->id,
        ]);

        $response = $this->postJson(route('webhooks.schoolpay.callback', $this->school->id), [
            'amount' => 20000,
            'channelName' => 'MTN MobileMoney',
            'paymentReference' => '21AD100011',
            'receiptNumber' => '5615855',
            'status' => 'PAID',
            'transactionId' => '1188813',
            'returnCode' => 0,
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $payment->refresh();
        $this->assertSame('confirmed', $payment->status);
        $this->assertSame('5615855', $payment->provider_txn_id);
        $this->assertSame('30000.00', $this->invoice->fresh()->balance);
        $this->assertSame('partial', $this->invoice->fresh()->status);
    }

    public function test_webhook_applies_school_fees_with_valid_signature(): void
    {
        $receipt = '18847257';
        $signature = app(SchoolPayClient::class)->webhookSignature('test-api-password', $receipt);

        $response = $this->postJson(route('webhooks.schoolpay.notify', $this->school->id), [
            'signature' => $signature,
            'type' => 'SCHOOL_FEES',
            'payment' => [
                'amount' => '15000',
                'paymentDateAndTime' => '2026-01-29 20:01:52',
                'schoolpayReceiptNumber' => $receipt,
                'settlementBankCode' => 'TROPICAL',
                'sourceChannelTransDetail' => 'John Doe',
                'sourceChannelTransactionId' => 'TXN_9876543220',
                'sourcePaymentChannel' => 'MTN MobileMoney',
                'studentName' => $this->student->full_name,
                'studentPaymentCode' => '1005416321',
                'studentRegistrationNumber' => '',
                'transactionCompletionStatus' => 'Completed',
            ],
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $payment = FeePayment::where('provider_txn_id', $receipt)->first();
        $this->assertNotNull($payment);
        $this->assertSame('confirmed', $payment->status);
        $this->assertSame('schoolpay', $payment->method);
        $this->assertSame('35000.00', $this->invoice->fresh()->balance);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $response = $this->postJson(route('webhooks.schoolpay.notify', $this->school->id), [
            'signature' => 'deadbeef',
            'type' => 'SCHOOL_FEES',
            'payment' => [
                'amount' => '15000',
                'schoolpayReceiptNumber' => '18847257',
                'studentPaymentCode' => '1005416321',
                'transactionCompletionStatus' => 'Completed',
            ],
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, FeePayment::count());
        $this->assertSame('50000.00', $this->invoice->fresh()->balance);
    }

    public function test_sync_applies_missing_transactions_idempotently(): void
    {
        Http::fake([
            '*/AndroidRS/SyncSchoolTransactions/*' => Http::response([
                'returnCode' => 0,
                'returnMessage' => '1 transaction(s) found',
                'transactions' => [[
                    'amount' => '10000',
                    'paymentDateAndTime' => '2026-08-10 12:00:00',
                    'schoolpayReceiptNumber' => '18843014',
                    'sourcePaymentChannel' => 'Airtel Money',
                    'studentName' => $this->student->full_name,
                    'studentPaymentCode' => '1005416321',
                    'transactionCompletionStatus' => 'Completed',
                ]],
                'supplementaryFeePayments' => [],
            ], 200),
        ]);

        $this->artisan('schoolpay:sync', [
            '--school' => $this->school->id,
            '--date' => '2026-08-10',
        ])->assertSuccessful();

        $this->assertSame(1, FeePayment::where('provider_txn_id', '18843014')->count());
        $this->assertSame('40000.00', $this->invoice->fresh()->balance);

        // Second sync must not double-apply.
        $this->artisan('schoolpay:sync', [
            '--school' => $this->school->id,
            '--date' => '2026-08-10',
        ])->assertSuccessful();

        $this->assertSame(1, FeePayment::where('provider_txn_id', '18843014')->count());
        $this->assertSame('40000.00', $this->invoice->fresh()->balance);
    }

    public function test_range_sync_uses_school_range_transactions(): void
    {
        Http::fake([
            '*/AndroidRS/SchoolRangeTransactions/*' => Http::response([
                'returnCode' => 0,
                'returnMessage' => '1 transaction(s) found',
                'transactions' => [[
                    'amount' => '5000',
                    'schoolpayReceiptNumber' => '18849999',
                    'sourcePaymentChannel' => 'MTN MobileMoney',
                    'studentPaymentCode' => '1005416321',
                    'transactionCompletionStatus' => 'Completed',
                ]],
                'supplementaryFeePayments' => [],
            ], 200),
        ]);

        $this->artisan('schoolpay:sync', [
            '--school' => $this->school->id,
            '--from' => '2026-08-01',
            '--to' => '2026-08-10',
        ])->assertSuccessful();

        $this->assertSame(1, FeePayment::where('provider_txn_id', '18849999')->count());
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/AndroidRS/SchoolRangeTransactions/809/2026-08-01/2026-08-10/');
        });
    }

    public function test_student_payment_code_must_be_ten_digits(): void
    {
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);

        $response = $this->actingAs($admin)->withSession([
            TenantContext::SESSION_SCHOOL_ID => $this->school->id,
        ])->put(route('app.students.update', $this->student), [
            'full_name' => $this->student->full_name,
            'status' => $this->student->status,
            'class_id' => $this->student->class_id,
            'schoolpay_payment_code' => '12345',
        ]);

        $response->assertSessionHasErrors('schoolpay_payment_code');
    }

    public function test_school_settings_can_enable_schoolpay(): void
    {
        $admin = User::where('email', 'admin@standrews.test')->first()
            ?? User::where('email', 'head@standrews.test')->firstOrFail();

        app(TenantContext::class)->forSchool($this->school->id);

        $response = $this->actingAs($admin)->withSession([
            TenantContext::SESSION_SCHOOL_ID => $this->school->id,
        ])->put(route('app.settings.school.update'), [
            'name' => $this->school->name,
            'theme' => $this->school->theme ?: 'pearledu',
            'schoolpay_enabled' => '1',
            'schoolpay_school_code' => '9999',
            'schoolpay_api_password' => 'new-secret-password',
            'emis_enabled' => '1',
        ]);

        $response->assertRedirect();
        $this->school->refresh();
        $this->assertTrue($this->school->schoolpay_enabled);
        $this->assertTrue($this->school->emis_enabled);
        $this->assertSame('9999', $this->school->schoolpay_school_code);
        $this->assertSame('new-secret-password', $this->school->schoolpay_api_password);
    }

    public function test_emis_export_requires_feature_opt_in(): void
    {
        $admin = User::where('email', 'admin@standrews.test')->firstOrFail();
        app(TenantContext::class)->forSchool($this->school->id);
        $this->school->forceFill(['emis_enabled' => false])->save();

        $response = $this->actingAs($admin)->withSession([
            TenantContext::SESSION_SCHOOL_ID => $this->school->id,
        ])->get(route('app.emis.export'));

        $response->assertNotFound();

        $this->school->forceFill(['emis_enabled' => true])->save();

        $ok = $this->actingAs($admin)->withSession([
            TenantContext::SESSION_SCHOOL_ID => $this->school->id,
        ])->get(route('app.emis.export'));

        $ok->assertOk();
    }
}
