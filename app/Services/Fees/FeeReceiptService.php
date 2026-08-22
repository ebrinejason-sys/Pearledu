<?php

namespace App\Services\Fees;

use App\Mail\FeePaymentReceiptMail;
use App\Models\FeePayment;
use App\Models\School;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class FeeReceiptService
{
    /**
     * @return list<string>
     */
    public function recipientEmails(FeePayment $payment): array
    {
        $payment->loadMissing(['invoice.student.guardianships.guardian', 'invoice.student.user']);
        $student = $payment->invoice?->student;
        if (! $student) {
            return [];
        }

        $emails = [];
        foreach ($student->guardianships as $link) {
            $email = $link->guardian?->email;
            if (is_string($email) && $email !== '') {
                $emails[strtolower($email)] = $email;
            }
        }
        $own = $student->user?->email;
        if (is_string($own) && $own !== '') {
            $emails[strtolower($own)] = $own;
        }

        return array_values($emails);
    }

    /**
     * @return list<string>
     */
    public function email(School $school, FeePayment $payment): array
    {
        abort_unless((int) $payment->school_id === (int) $school->id, 404);
        if ($payment->status !== 'confirmed') {
            throw ValidationException::withMessages([
                'payment' => 'Only confirmed payments can be emailed as receipts.',
            ]);
        }

        $emails = $this->recipientEmails($payment);
        if ($emails === []) {
            throw ValidationException::withMessages([
                'payment' => 'This learner has no guardian or student email on file.',
            ]);
        }

        $payment->loadMissing(['invoice.student.schoolClass', 'invoice.structure', 'recordedBy']);
        Mail::to($emails)->send(new FeePaymentReceiptMail($school, $payment));

        return $emails;
    }
}
