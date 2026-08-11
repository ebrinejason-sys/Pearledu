<?php

namespace App\Services\SchoolPay;

use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Fees\FeePaymentService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Orchestrates SchoolPay → PearlEdu fee ledger:
 * - Parent adhoc MoMo (Register/Request + callback)
 * - Portal webhooks (SCHOOL_FEES / OTHER_FEES)
 * - Daily sync reconciliation
 */
class SchoolPayPaymentService
{
    public function __construct(
        private SchoolPayClient $client,
        private FeePaymentService $fees,
        private TenantContext $tenancy,
    ) {}

    public function schoolConfigured(School $school): bool
    {
        return $school->schoolpay_enabled
            && filled($school->schoolpay_school_code)
            && filled($school->schoolpay_api_password);
    }

    /**
     * Start a SchoolPay adhoc payment against an open invoice.
     *
     * @return array{payment:FeePayment,schoolpay_reference:?string,status:string}
     */
    public function initiateAdhoc(
        School $school,
        FeeInvoice $invoice,
        float $amount,
        string $phoneNumber,
        User $payer,
        string $callbackUrl,
    ): array {
        if (! config('schoolpay.adhoc_enabled')) {
            throw ValidationException::withMessages([
                'schoolpay' => 'SchoolPay online payments are disabled on this deployment.',
            ]);
        }

        $this->assertSchoolInvoice($school, $invoice);
        $this->client->credentials($school);

        $amount = round($amount, 2);
        $available = $this->fees->availableBalance($invoice);
        if ($amount <= 0 || $amount > $available + 0.0001) {
            throw ValidationException::withMessages([
                'amount' => 'Payment exceeds the outstanding balance.',
            ]);
        }

        $phone = $this->normalizeUgPhone($phoneNumber);
        [$firstName, $lastName] = $this->splitName($payer->name ?: 'Parent Guardian');
        $reason = 'Fees '.$invoice->reference;
        $externalReference = $this->makeExternalReference($invoice);

        $payment = $this->fees->record([
            'school_id' => (int) $school->id,
            'invoice_id' => (int) $invoice->id,
            'amount' => $amount,
            'method' => 'schoolpay',
            'provider_ref' => null,
            'external_reference' => $externalReference,
            'recorded_by' => $payer->id,
        ], confirmImmediately: false);

        try {
            $registered = $this->client->registerAdhoc($school, [
                'amount' => (int) round($amount),
                'externalReference' => $externalReference,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'reason' => $reason,
                'callBackUrl' => $callbackUrl,
            ]);

            $requested = $this->client->requestAdhoc($school, [
                'amount' => (int) round($amount),
                'externalReference' => $externalReference,
                'phoneNumber' => $phone,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'reason' => $reason,
            ]);
        } catch (\Throwable $e) {
            $payment->status = 'rejected';
            $payment->verified_at = now();
            $payment->save();
            throw $e;
        }

        $schoolpayRef = (string) ($requested['paymentReference']
            ?? $registered['paymentReference']
            ?? '');

        if ($schoolpayRef !== '') {
            $payment->schoolpay_reference = $schoolpayRef;
            $payment->save();
        }

        return [
            'payment' => $payment->fresh(),
            'schoolpay_reference' => $schoolpayRef !== '' ? $schoolpayRef : null,
            'status' => (string) ($requested['status'] ?? $registered['status'] ?? 'PENDING'),
        ];
    }

    /**
     * Adhoc payment callback from SchoolPay (callBackUrl).
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleAdhocCallback(School $school, array $payload): ?FeePayment
    {
        $this->tenancy->forSchool((int) $school->id);

        $status = strtoupper((string) ($payload['status'] ?? ''));
        if ($status !== 'PAID') {
            return null;
        }

        $paymentReference = (string) ($payload['paymentReference'] ?? '');
        $receipt = (string) ($payload['receiptNumber'] ?? $payload['transactionId'] ?? '');
        $amount = isset($payload['amount']) ? round((float) $payload['amount'], 2) : null;
        $channel = isset($payload['channelName']) ? (string) $payload['channelName'] : null;

        if ($paymentReference === '' && $receipt === '') {
            Log::warning('SchoolPay adhoc callback missing identifiers', [
                'school_id' => $school->id,
                'payload' => $payload,
            ]);

            return null;
        }

        return DB::transaction(function () use ($school, $paymentReference, $receipt, $amount, $channel) {
            $payment = null;
            if ($paymentReference !== '') {
                $payment = FeePayment::query()
                    ->where('school_id', $school->id)
                    ->where('schoolpay_reference', $paymentReference)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $payment && $receipt !== '') {
                $payment = FeePayment::query()
                    ->where('school_id', $school->id)
                    ->where('provider_txn_id', $receipt)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $payment) {
                Log::warning('SchoolPay adhoc callback for unknown payment', [
                    'school_id' => $school->id,
                    'paymentReference' => $paymentReference,
                    'receipt' => $receipt,
                ]);

                return null;
            }

            return $this->confirmProviderPayment($payment, [
                'provider_txn_id' => $receipt !== '' ? $receipt : null,
                'provider_ref' => $receipt !== '' ? $receipt : $payment->provider_ref,
                'channel_name' => $channel,
                'schoolpay_reference' => $paymentReference !== '' ? $paymentReference : $payment->schoolpay_reference,
                'expected_amount' => $amount,
            ]);
        });
    }

    /**
     * Portal webhook notification (SCHOOL_FEES / OTHER_FEES).
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(School $school, array $payload): ?FeePayment
    {
        $this->tenancy->forSchool((int) $school->id);
        [, $password] = $this->client->credentials($school);

        $signature = (string) ($payload['signature'] ?? '');
        $paymentData = is_array($payload['payment'] ?? null) ? $payload['payment'] : [];
        $receipt = (string) ($paymentData['schoolpayReceiptNumber'] ?? '');

        if ($receipt === '' || $signature === '') {
            throw ValidationException::withMessages(['signature' => 'Missing SchoolPay signature or receipt.']);
        }

        $expected = $this->client->webhookSignature($password, $receipt);
        if (! hash_equals($expected, strtolower($signature)) && ! hash_equals($expected, $signature)) {
            throw ValidationException::withMessages(['signature' => 'Invalid SchoolPay webhook signature.']);
        }

        $status = (string) ($paymentData['transactionCompletionStatus'] ?? '');
        if (strcasecmp($status, 'Completed') !== 0) {
            return null;
        }

        $amount = round((float) ($paymentData['amount'] ?? 0), 2);
        if ($amount <= 0) {
            return null;
        }

        $studentCode = (string) ($paymentData['studentPaymentCode'] ?? '');
        $channel = (string) ($paymentData['sourcePaymentChannel'] ?? 'SchoolPay');

        return DB::transaction(function () use ($school, $receipt, $amount, $studentCode, $channel, $paymentData) {
            $existing = FeePayment::query()
                ->where('school_id', $school->id)
                ->where('provider_txn_id', $receipt)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $student = $this->findStudentByPaymentCode($school, $studentCode);
            if (! $student) {
                Log::warning('SchoolPay webhook student payment code not mapped', [
                    'school_id' => $school->id,
                    'studentPaymentCode' => $studentCode,
                    'receipt' => $receipt,
                ]);

                return null;
            }

            $invoice = $this->nextOpenInvoice($student);
            if (! $invoice) {
                Log::warning('SchoolPay webhook with no open invoice', [
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'receipt' => $receipt,
                    'amount' => $amount,
                ]);

                return null;
            }

            // Cap at invoice available balance; remainder needs bursar review via sync logs.
            $applyAmount = min($amount, $this->fees->availableBalance($invoice));
            if ($applyAmount <= 0) {
                return null;
            }

            $payment = $this->fees->record([
                'school_id' => (int) $school->id,
                'invoice_id' => (int) $invoice->id,
                'amount' => $applyAmount,
                'method' => 'schoolpay',
                'provider_ref' => $receipt,
                'provider_txn_id' => $receipt,
                'channel_name' => $channel,
                'recorded_by' => null,
            ], confirmImmediately: true);

            $payment->provider_txn_id = $receipt;
            $payment->channel_name = $channel;
            $payment->provider_ref = $receipt;
            if (! empty($paymentData['sourceChannelTransactionId'])) {
                $payment->schoolpay_reference = (string) $paymentData['sourceChannelTransactionId'];
            }
            $payment->save();

            return $payment;
        });
    }

    /**
     * Pull SchoolPay transactions for a date and apply any missing receipts.
     *
     * @return array{applied:int,skipped:int,unmatched:int}
     */
    public function syncDay(School $school, string $date): array
    {
        $this->tenancy->forSchool((int) $school->id);
        $payload = $this->client->syncTransactions($school, $date);

        return $this->applySyncPayload($school, $payload, $date);
    }

    /**
     * Pull SchoolPay transactions for a date range (max 31 days) via SchoolRangeTransactions.
     * Hash uses fromDate per SchoolPay docs: MD5(schoolCode + fromDate + password).
     *
     * @return array{applied:int,skipped:int,unmatched:int}
     */
    public function syncRange(School $school, string $fromDate, string $toDate): array
    {
        $this->tenancy->forSchool((int) $school->id);
        $payload = $this->client->syncRange($school, $fromDate, $toDate);

        return $this->applySyncPayload($school, $payload, $fromDate.'..'.$toDate);
    }

    /**
     * @param  array{returnCode:int|null,returnMessage:?string,transactions:array<int,array<string,mixed>>,supplementaryFeePayments:array<int,array<string,mixed>>}  $payload
     * @return array{applied:int,skipped:int,unmatched:int}
     */
    private function applySyncPayload(School $school, array $payload, string $windowLabel): array
    {
        if (($payload['returnCode'] ?? null) !== null && (int) $payload['returnCode'] !== 0) {
            Log::info('SchoolPay sync returned non-zero', [
                'school_id' => $school->id,
                'window' => $windowLabel,
                'returnCode' => $payload['returnCode'],
                'returnMessage' => $payload['returnMessage'] ?? null,
            ]);
        }

        $stats = ['applied' => 0, 'skipped' => 0, 'unmatched' => 0];

        foreach (array_merge($payload['transactions'], $payload['supplementaryFeePayments']) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $result = $this->applySyncedTransaction($school, $row);
            $stats[$result]++;
        }

        return $stats;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return 'applied'|'skipped'|'unmatched'
     */
    public function applySyncedTransaction(School $school, array $row): string
    {
        $receipt = (string) ($row['schoolpayReceiptNumber'] ?? '');
        $amount = round((float) ($row['amount'] ?? 0), 2);
        $status = (string) ($row['transactionCompletionStatus'] ?? 'Completed');
        $studentCode = (string) ($row['studentPaymentCode'] ?? '');
        $channel = (string) ($row['sourcePaymentChannel'] ?? 'SchoolPay');

        if ($receipt === '' || $amount <= 0 || strcasecmp($status, 'Completed') !== 0) {
            return 'skipped';
        }

        return DB::transaction(function () use ($school, $receipt, $amount, $studentCode, $channel) {
            $existing = FeePayment::query()
                ->where('school_id', $school->id)
                ->where('provider_txn_id', $receipt)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->status === 'pending') {
                    $this->confirmProviderPayment($existing, [
                        'provider_txn_id' => $receipt,
                        'provider_ref' => $receipt,
                        'channel_name' => $channel,
                    ]);

                    return 'applied';
                }

                return 'skipped';
            }

            $student = $this->findStudentByPaymentCode($school, $studentCode);
            if (! $student) {
                return 'unmatched';
            }

            $invoice = $this->nextOpenInvoice($student);
            if (! $invoice) {
                return 'unmatched';
            }

            $applyAmount = min($amount, $this->fees->availableBalance($invoice));
            if ($applyAmount <= 0) {
                return 'skipped';
            }

            $payment = $this->fees->record([
                'school_id' => (int) $school->id,
                'invoice_id' => (int) $invoice->id,
                'amount' => $applyAmount,
                'method' => 'schoolpay',
                'provider_ref' => $receipt,
                'provider_txn_id' => $receipt,
                'channel_name' => $channel,
                'recorded_by' => null,
            ], confirmImmediately: true);

            $payment->provider_txn_id = $receipt;
            $payment->channel_name = $channel;
            $payment->save();

            return 'applied';
        });
    }

    /**
     * @param  array{
     *   provider_txn_id?:?string,
     *   provider_ref?:?string,
     *   channel_name?:?string,
     *   schoolpay_reference?:?string,
     *   expected_amount?:?float
     * }  $meta
     */
    private function confirmProviderPayment(FeePayment $payment, array $meta): FeePayment
    {
        if ($payment->status === 'confirmed') {
            return $payment;
        }

        if ($payment->status !== 'pending') {
            throw ValidationException::withMessages([
                'payment' => 'Only pending SchoolPay payments can be confirmed.',
            ]);
        }

        if (isset($meta['expected_amount']) && $meta['expected_amount'] !== null) {
            $expected = round((float) $meta['expected_amount'], 2);
            $actual = round((float) $payment->amount, 2);
            if (abs($expected - $actual) > 0.01) {
                Log::warning('SchoolPay amount mismatch on confirm', [
                    'payment_id' => $payment->id,
                    'expected' => $expected,
                    'actual' => $actual,
                ]);
                throw ValidationException::withMessages([
                    'amount' => 'SchoolPay amount does not match the pending payment.',
                ]);
            }
        }

        if (! empty($meta['provider_txn_id'])) {
            $payment->provider_txn_id = $meta['provider_txn_id'];
        }
        if (array_key_exists('provider_ref', $meta) && $meta['provider_ref'] !== null) {
            $payment->provider_ref = $meta['provider_ref'];
        }
        if (! empty($meta['channel_name'])) {
            $payment->channel_name = $meta['channel_name'];
        }
        if (! empty($meta['schoolpay_reference'])) {
            $payment->schoolpay_reference = $meta['schoolpay_reference'];
        }
        $payment->save();

        return $this->fees->confirm($payment, verifiedBy: null);
    }

    private function assertSchoolInvoice(School $school, FeeInvoice $invoice): void
    {
        if ((int) $invoice->school_id !== (int) $school->id) {
            throw ValidationException::withMessages(['invoice' => 'Invoice not found for this school.']);
        }
    }

    private function findStudentByPaymentCode(School $school, string $code): ?Student
    {
        $code = preg_replace('/\D+/', '', trim($code)) ?? '';
        if (! preg_match('/^\d{10}$/', $code)) {
            return null;
        }

        return Student::query()
            ->where('school_id', $school->id)
            ->where('schoolpay_payment_code', $code)
            ->first();
    }

    private function nextOpenInvoice(Student $student): ?FeeInvoice
    {
        return FeeInvoice::query()
            ->where('school_id', $student->school_id)
            ->where('student_id', $student->id)
            ->whereIn('status', ['open', 'partial'])
            ->where('balance', '>', 0)
            ->orderBy('due_on')
            ->orderBy('id')
            ->first();
    }

    private function makeExternalReference(FeeInvoice $invoice): string
    {
        // SchoolPay expects a stable partner reference; keep it compact + unique.
        return 'PE'.$invoice->id.Str::upper(Str::random(8));
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = $parts[0] ?? 'Parent';
        $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'Guardian';

        return [mb_substr($first, 0, 40), mb_substr($last, 0, 40)];
    }

    private function normalizeUgPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '256') && strlen($digits) >= 12) {
            return '0'.substr($digits, 3);
        }
        if (str_starts_with($digits, '0') && strlen($digits) >= 10) {
            return $digits;
        }
        if (strlen($digits) === 9) {
            return '0'.$digits;
        }

        throw ValidationException::withMessages([
            'phone' => 'Enter a valid Uganda mobile number (e.g. 0770123456).',
        ]);
    }
}
