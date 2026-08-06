<?php

namespace App\Services\Fees;

use App\Models\FeeInvoice;
use App\Models\FeePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeePaymentService
{
    /**
     * @param  array{
     *   school_id:int,
     *   invoice_id:int,
     *   amount:float|string,
     *   method?:string,
     *   provider_ref?:string|null,
     *   recorded_by?:int|null
     * }  $data
     * @param  bool  $confirmImmediately  Staff recordings confirm and clear balance; parent portal submissions stay pending.
     */
    public function record(array $data, bool $confirmImmediately = true): FeePayment
    {
        return DB::transaction(function () use ($data, $confirmImmediately) {
            /** @var FeeInvoice $invoice */
            $invoice = FeeInvoice::query()->lockForUpdate()->findOrFail($data['invoice_id']);

            if ($invoice->school_id !== (int) $data['school_id']) {
                throw ValidationException::withMessages(['invoice_id' => 'Invoice not found for this school.']);
            }

            if (in_array($invoice->status, ['paid', 'void'], true)) {
                throw ValidationException::withMessages(['invoice_id' => 'This invoice cannot accept payments.']);
            }

            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
            }

            $available = $this->availableBalance($invoice);
            if ($amount > $available + 0.0001) {
                throw ValidationException::withMessages(['amount' => 'Payment exceeds the outstanding balance.']);
            }

            $payment = FeePayment::create([
                'school_id' => $invoice->school_id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'method' => $data['method'] ?? 'cash',
                'provider_ref' => $data['provider_ref'] ?? null,
                'status' => $confirmImmediately ? 'confirmed' : 'pending',
                'recorded_by' => $data['recorded_by'] ?? null,
                'verified_by' => $confirmImmediately ? ($data['recorded_by'] ?? null) : null,
                'verified_at' => $confirmImmediately ? now() : null,
            ]);

            if ($confirmImmediately) {
                $this->applyToInvoice($invoice, $amount);
            }

            return $payment;
        });
    }

    public function confirm(FeePayment $payment, int $verifiedBy): FeePayment
    {
        return DB::transaction(function () use ($payment, $verifiedBy) {
            /** @var FeePayment $payment */
            $payment = FeePayment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== 'pending') {
                throw ValidationException::withMessages(['payment' => 'Only pending payments can be confirmed.']);
            }

            /** @var FeeInvoice $invoice */
            $invoice = FeeInvoice::query()->lockForUpdate()->findOrFail($payment->invoice_id);

            if (in_array($invoice->status, ['paid', 'void'], true)) {
                throw ValidationException::withMessages(['payment' => 'This invoice cannot accept payments.']);
            }

            $amount = round((float) $payment->amount, 2);
            if ($amount > (float) $invoice->balance + 0.0001) {
                throw ValidationException::withMessages(['payment' => 'Payment exceeds the outstanding balance.']);
            }

            $payment->status = 'confirmed';
            $payment->verified_by = $verifiedBy;
            $payment->verified_at = now();
            $payment->save();

            $this->applyToInvoice($invoice, $amount);

            return $payment;
        });
    }

    public function reject(FeePayment $payment, int $verifiedBy): FeePayment
    {
        return DB::transaction(function () use ($payment, $verifiedBy) {
            /** @var FeePayment $payment */
            $payment = FeePayment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== 'pending') {
                throw ValidationException::withMessages(['payment' => 'Only pending payments can be rejected.']);
            }

            $payment->status = 'rejected';
            $payment->verified_by = $verifiedBy;
            $payment->verified_at = now();
            $payment->save();

            return $payment;
        });
    }

    /** Balance not yet claimed by confirmed or pending payments. */
    public function availableBalance(FeeInvoice $invoice): float
    {
        $pending = (float) FeePayment::query()
            ->where('invoice_id', $invoice->id)
            ->where('status', 'pending')
            ->sum('amount');

        return max(0, round((float) $invoice->balance - $pending, 2));
    }

    private function applyToInvoice(FeeInvoice $invoice, float $amount): void
    {
        $newBalance = round((float) $invoice->balance - $amount, 2);
        $invoice->balance = max(0, $newBalance);
        $invoice->status = $invoice->balance <= 0.0001 ? 'paid' : 'partial';
        $invoice->save();
    }
}
