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
     */
    public function record(array $data): FeePayment
    {
        return DB::transaction(function () use ($data) {
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
            if ($amount > (float) $invoice->balance + 0.0001) {
                throw ValidationException::withMessages(['amount' => 'Payment exceeds the outstanding balance.']);
            }

            $payment = FeePayment::create([
                'school_id' => $invoice->school_id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'method' => $data['method'] ?? 'cash',
                'provider_ref' => $data['provider_ref'] ?? null,
                'recorded_by' => $data['recorded_by'] ?? null,
            ]);

            $newBalance = round((float) $invoice->balance - $amount, 2);
            $invoice->balance = max(0, $newBalance);
            $invoice->status = $invoice->balance <= 0.0001 ? 'paid' : 'partial';
            $invoice->save();

            return $payment;
        });
    }
}
