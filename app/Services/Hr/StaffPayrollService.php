<?php

namespace App\Services\Hr;

use App\Models\School;
use App\Models\StaffSalary;
use App\Models\StaffSalaryPayment;
use App\Models\User;
use App\Services\Audit\AuditLogger;

class StaffPayrollService
{
    public function __construct(private AuditLogger $audit) {}

    public function setSalary(School $school, User $staff, array $data, User $actor): StaffSalary
    {
        $salary = StaffSalary::query()->updateOrCreate(
            ['school_id' => $school->id, 'user_id' => $staff->id],
            [
                'amount' => (int) $data['amount'],
                'currency' => $data['currency'] ?? 'UGX',
                'effective_on' => $data['effective_on'],
                'notes' => $data['notes'] ?? null,
            ],
        );

        $this->audit->record('staff.salary.set', $salary, [
            'user_id' => $staff->id,
            'amount' => $salary->amount,
        ], $actor);

        return $salary;
    }

    public function recordPayment(School $school, User $staff, array $data, User $actor): StaffSalaryPayment
    {
        $payment = StaffSalaryPayment::create([
            'school_id' => $school->id,
            'user_id' => $staff->id,
            'recorded_by' => $actor->id,
            'amount' => (int) $data['amount'],
            'currency' => $data['currency'] ?? 'UGX',
            'paid_on' => $data['paid_on'],
            'method' => $data['method'] ?? 'bank',
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->audit->record('staff.salary.paid', $payment, [
            'user_id' => $staff->id,
            'amount' => $payment->amount,
        ], $actor);

        return $payment;
    }
}
