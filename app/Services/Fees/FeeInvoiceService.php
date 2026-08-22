<?php

namespace App\Services\Fees;

use App\Models\Enrollment;
use App\Models\FeeAdjustment;
use App\Models\FeeInvoice;
use App\Models\FeeStructure;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeeInvoiceService
{
    /**
     * @return array{created:int, already_existed:int, skipped:int}
     */
    public function generateClassInvoices(
        int $schoolId,
        int $structureId,
        int $classId,
        ?string $dueOn = null,
        ?int $academicYearId = null,
    ): array {
        $structure = FeeStructure::query()
            ->where('school_id', $schoolId)
            ->findOrFail($structureId);

        $studentIds = $this->studentIdsForStructure($schoolId, $structure, $classId, $academicYearId);
        $created = 0;
        $already = 0;
        $skipped = 0;

        DB::transaction(function () use ($schoolId, $structure, $studentIds, $dueOn, &$created, &$already, &$skipped) {
            foreach ($studentIds as $studentId) {
                $result = $this->ensureInvoice($schoolId, $studentId, $structure, $dueOn);
                if ($result === 'created') {
                    $created++;
                } elseif ($result === 'exists') {
                    $already++;
                } else {
                    $skipped++;
                }
            }
        });

        if ($studentIds === []) {
            $skipped++;
        }

        return ['created' => $created, 'already_existed' => $already, 'skipped' => $skipped];
    }

    /**
     * Active fee structures for the learner's class (and optional term).
     *
     * @return array{created:int, already_existed:int, skipped:int}
     */
    public function assignDefaultStructures(Student $student, ?int $classId = null, ?int $termId = null): array
    {
        $classId ??= $student->class_id;
        if (! $classId) {
            return ['created' => 0, 'already_existed' => 0, 'skipped' => 1];
        }

        $structures = FeeStructure::query()
            ->where('school_id', $student->school_id)
            ->where('is_active', true)
            ->where(function ($q) use ($classId) {
                $q->whereNull('class_id')->orWhere('class_id', $classId);
            })
            ->when($termId, fn ($q) => $q->where(function ($inner) use ($termId) {
                $inner->whereNull('term_id')->orWhere('term_id', $termId);
            }))
            ->get();

        $created = 0;
        $already = 0;
        $skipped = 0;

        foreach ($structures as $structure) {
            if (! $structure->appliesToStudent($student)) {
                $skipped++;
                continue;
            }
            $result = $this->ensureInvoice((int) $student->school_id, (int) $student->id, $structure, null);
            if ($result === 'created') {
                $created++;
            } elseif ($result === 'exists') {
                $already++;
            } else {
                $skipped++;
            }
        }

        if ($structures->isEmpty()) {
            $skipped++;
        }

        return ['created' => $created, 'already_existed' => $already, 'skipped' => $skipped];
    }

    public function createSingle(
        int $schoolId,
        int $studentId,
        float $amount,
        ?int $structureId = null,
        ?string $dueOn = null,
    ): FeeInvoice {
        if ($structureId) {
            $structure = FeeStructure::query()->where('school_id', $schoolId)->findOrFail($structureId);
            $existing = $this->liveInvoice($schoolId, $studentId, $structure->id);
            if ($existing) {
                return $existing;
            }
        }

        return FeeInvoice::create([
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'fee_structure_id' => $structureId,
            'amount' => $amount,
            'balance' => $amount,
            'status' => 'open',
            'due_on' => $dueOn,
            'reference' => 'INV-'.now()->format('YmdHis').'-'.$studentId,
        ]);
    }

    public function void(FeeInvoice $invoice): FeeInvoice
    {
        $confirmed = $invoice->payments()->where('status', 'confirmed')->exists();
        if ($confirmed) {
            throw ValidationException::withMessages([
                'invoice' => 'Reverse confirmed payments before voiding this invoice.',
            ]);
        }

        $invoice->update(['status' => 'void', 'balance' => 0]);

        return $invoice;
    }

    public function applyDiscount(FeeInvoice $invoice, float $amount, string $reason, ?int $createdBy = null): FeeAdjustment
    {
        if (in_array($invoice->status, ['void', 'paid'], true) && $invoice->status === 'void') {
            throw ValidationException::withMessages(['invoice' => 'Cannot discount a void invoice.']);
        }

        $amount = round($amount, 2);
        if ($amount <= 0 || $amount > (float) $invoice->amount + 0.0001) {
            throw ValidationException::withMessages(['amount' => 'Discount must be between 0 and the invoice amount.']);
        }

        return DB::transaction(function () use ($invoice, $amount, $reason, $createdBy) {
            $invoice = FeeInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $newAmount = round((float) $invoice->amount - $amount, 2);
            $newBalance = round((float) $invoice->balance - $amount, 2);
            $invoice->amount = max(0, $newAmount);
            $invoice->balance = max(0, $newBalance);
            $invoice->status = $invoice->balance <= 0.0001 ? 'paid' : ($invoice->balance + 0.0001 >= (float) $invoice->amount ? 'open' : 'partial');
            $invoice->save();

            return FeeAdjustment::create([
                'school_id' => $invoice->school_id,
                'student_id' => $invoice->student_id,
                'invoice_id' => $invoice->id,
                'type' => 'discount',
                'amount' => $amount,
                'reason' => $reason,
                'created_by' => $createdBy,
            ]);
        });
    }

    private function ensureInvoice(int $schoolId, int $studentId, FeeStructure $structure, ?string $dueOn): string
    {
        if ($this->liveInvoice($schoolId, $studentId, $structure->id)) {
            return 'exists';
        }

        FeeInvoice::create([
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'fee_structure_id' => $structure->id,
            'amount' => $structure->amount,
            'balance' => $structure->amount,
            'status' => 'open',
            'due_on' => $dueOn,
            'reference' => 'INV-'.now()->format('YmdHis').'-'.$studentId,
        ]);

        return 'created';
    }

    private function liveInvoice(int $schoolId, int $studentId, int $structureId): ?FeeInvoice
    {
        return FeeInvoice::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->where('fee_structure_id', $structureId)
            ->where('status', '!=', 'void')
            ->first();
    }

    /**
     * @return array{created:int, already_existed:int, skipped:int}
     */
    public function generateForStructure(int $schoolId, FeeStructure $structure, ?int $classId, ?string $dueOn, ?int $academicYearId): array
    {
        return $this->generateClassInvoices(
            $schoolId,
            (int) $structure->id,
            $classId ?: (int) ($structure->class_id ?: 0),
            $dueOn,
            $academicYearId,
        );
    }

    /** @return list<int> */
    private function studentIdsForStructure(int $schoolId, FeeStructure $structure, int $classId, ?int $academicYearId): array
    {
        if ($structure->isLearnerTargeted()) {
            return $structure->learners()
                ->where('students.school_id', $schoolId)
                ->where('students.status', 'active')
                ->pluck('students.id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $targetClass = $classId ?: (int) ($structure->class_id ?: 0);
        $ids = $targetClass
            ? $this->studentIdsForClass($schoolId, $targetClass, $academicYearId)
            : Student::query()
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

        if (($structure->residency ?: 'any') === 'any') {
            return $ids;
        }

        return Student::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $ids)
            ->get()
            ->filter(fn (Student $student) => $structure->appliesToStudent($student))
            ->map(fn (Student $student) => (int) $student->id)
            ->values()
            ->all();
    }

    /** @return list<int> */
    private function studentIdsForClass(int $schoolId, int $classId, ?int $academicYearId): array
    {
        if ($classId <= 0) {
            return [];
        }

        $fromEnrollments = Enrollment::query()
            ->where('school_id', $schoolId)
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($fromEnrollments !== []) {
            return array_values(array_unique($fromEnrollments));
        }

        return Student::query()
            ->where('school_id', $schoolId)
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
