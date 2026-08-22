<?php

namespace App\Services\Fees;

use App\Models\Enrollment;
use App\Models\FeeAdjustment;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Student;
use App\Support\FeeKind;
use App\Support\Residency;
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

    /**
     * Create a learner-specific extra (van, club) and invoice this student.
     * The class day/boarding tuition is a separate saved structure.
     *
     * @param  array{name: string, amount: float|int|string, kind?: string, due_on?: ?string, term_id?: ?int}  $data
     */
    public function applyCustomFee(Student $student, array $data): FeeInvoice
    {
        $schoolId = (int) $student->school_id;
        $kind = (string) ($data['kind'] ?? FeeKind::OTHER);
        if (! in_array($kind, FeeKind::keys(), true)) {
            $kind = FeeKind::OTHER;
        }

        return DB::transaction(function () use ($student, $data, $schoolId, $kind) {
            $structure = FeeStructure::create([
                'school_id' => $schoolId,
                'name' => $data['name'],
                'amount' => $data['amount'],
                'kind' => $kind,
                'residency' => Residency::ANY,
                'applies_to' => 'learners',
                'class_id' => null,
                'term_id' => $data['term_id'] ?? null,
                'currency' => 'UGX',
                'is_active' => true,
            ]);
            $structure->syncLearners($schoolId, [(int) $student->id]);
            $this->ensureInvoice($schoolId, (int) $student->id, $structure, $data['due_on'] ?? null);

            $invoice = $this->liveInvoice($schoolId, (int) $student->id, (int) $structure->id);
            if (! $invoice) {
                throw ValidationException::withMessages([
                    'amount' => 'The fee could not be applied to this learner.',
                ]);
            }

            return $invoice;
        });
    }

    /**
     * Invoice one learner from an already-saved structure (class tuition or named group).
     */
    public function invoiceStudent(Student $student, FeeStructure $structure, ?string $dueOn = null): FeeInvoice
    {
        $schoolId = (int) $student->school_id;
        if ((int) $structure->school_id !== $schoolId) {
            throw ValidationException::withMessages([
                'fee_structure_id' => 'That fee structure does not belong to this school.',
            ]);
        }

        if ($structure->isLearnerTargeted()) {
            $structure->attachLearners($schoolId, [(int) $student->id]);
            $structure->unsetRelation('learners');
        } elseif (! $structure->appliesToStudent($student)) {
            throw ValidationException::withMessages([
                'fee_structure_id' => 'This fee is for a different class or residency (day/boarding).',
            ]);
        }

        $this->ensureInvoice($schoolId, (int) $student->id, $structure, $dueOn);
        $invoice = $this->liveInvoice($schoolId, (int) $student->id, (int) $structure->id);
        if (! $invoice) {
            throw ValidationException::withMessages([
                'fee_structure_id' => 'The fee could not be applied to this learner.',
            ]);
        }

        return $invoice;
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

    /**
     * Remove a saved fee type. Unpaid invoices are voided. Confirmed or pending
     * payments must be reversed or rejected first so the ledger stays auditable.
     */
    public function deleteStructure(FeeStructure $structure): void
    {
        $blocking = FeePayment::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereHas('invoice', function ($q) use ($structure) {
                $q->where('school_id', $structure->school_id)
                    ->where('fee_structure_id', $structure->id);
            })
            ->exists();

        if ($blocking) {
            throw ValidationException::withMessages([
                'structure' => 'Reverse or reject payments on this fee type before deleting it. You can archive it instead.',
            ]);
        }

        DB::transaction(function () use ($structure) {
            $invoices = FeeInvoice::query()
                ->where('school_id', $structure->school_id)
                ->where('fee_structure_id', $structure->id)
                ->where('status', '!=', 'void')
                ->get();

            foreach ($invoices as $invoice) {
                $this->void($invoice);
            }

            // Composite tenant FK is (school_id, fee_structure_id) ON DELETE SET NULL.
            // Null only the structure id so invoices keep their school and ledger history.
            FeeInvoice::query()
                ->where('school_id', $structure->school_id)
                ->where('fee_structure_id', $structure->id)
                ->update(['fee_structure_id' => null]);

            $structure->learners()->detach();
            $structure->delete();
        });
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
