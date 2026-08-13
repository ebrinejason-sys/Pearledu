<?php

namespace App\Services\Fees;

use App\Models\FeeInvoice;
use App\Models\Student;

class StudentLedgerService
{
    /**
     * @return array{
     *   lines: list<array{date:string, description:string, debit:float, credit:float}>,
     *   balance: float
     * }
     */
    public function statement(Student $student): array
    {
        $invoices = FeeInvoice::query()
            ->where('student_id', $student->id)
            ->where('status', '!=', 'void')
            ->with([
                'structure',
                'payments' => fn ($q) => $q->where('status', 'confirmed')->orderBy('id'),
                'adjustments',
            ])
            ->orderBy('id')
            ->get();

        $lines = [];
        $balance = 0.0;

        foreach ($invoices as $invoice) {
            $debit = (float) $invoice->amount;
            $balance += $debit;
            $lines[] = [
                'date' => optional($invoice->created_at)->toDateString() ?? '',
                'description' => $invoice->structure?->name ?? ($invoice->reference ?: 'Fee invoice'),
                'debit' => $debit,
                'credit' => 0.0,
            ];

            foreach ($invoice->adjustments as $adjustment) {
                $credit = (float) $adjustment->amount;
                $balance -= $credit;
                $lines[] = [
                    'date' => optional($adjustment->created_at)->toDateString() ?? '',
                    'description' => ucfirst($adjustment->type).($adjustment->reason ? ': '.$adjustment->reason : ''),
                    'debit' => 0.0,
                    'credit' => $credit,
                ];
            }

            foreach ($invoice->payments as $payment) {
                $credit = (float) $payment->amount;
                $balance -= $credit;
                $label = $payment->reverses_payment_id ? 'Reversal' : 'Payment';
                $lines[] = [
                    'date' => optional($payment->created_at)->toDateString() ?? '',
                    'description' => $label.' ('.$payment->method.')',
                    'debit' => 0.0,
                    'credit' => $credit,
                ];
            }
        }

        return [
            'lines' => $lines,
            'balance' => round($balance, 2),
        ];
    }
}
