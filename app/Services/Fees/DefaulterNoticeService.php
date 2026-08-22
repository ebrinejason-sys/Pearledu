<?php

namespace App\Services\Fees;

use App\Models\FeeInvoice;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Staff\StaffMessageService;
use Illuminate\Support\Collection;
use RuntimeException;

class DefaulterNoticeService
{
    public function __construct(
        private StaffMessageService $messages,
        private AuditLogger $audit,
    ) {}

    /**
     * @return Collection<int, array{student: Student, balance: float, invoices: int<0, max>}>
     */
    public function forClass(School $school, int $classId): Collection
    {
        $students = Student::query()
            ->where('school_id', $school->id)
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get();

        return $students->map(function (Student $student) use ($school) {
            $invoices = FeeInvoice::query()
                ->where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->whereIn('status', ['open', 'partial'])
                ->where('balance', '>', 0)
                ->get();

            return [
                'student' => $student,
                'balance' => (float) $invoices->sum('balance'),
                'invoices' => (int) $invoices->count(),
            ];
        })->filter(fn (array $row) => $row['balance'] > 0)->values();
    }

    public function notifyClassTeacher(School $school, SchoolClass $class, User $actor): void
    {
        $teacher = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('class_id', $class->id)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->where('key', Role::CLASS_TEACHER))
            ->with('user')
            ->first()
            ?->user;

        if (! $teacher instanceof User) {
            throw new RuntimeException('No class teacher is assigned to this class.');
        }

        $rows = $this->forClass($school, (int) $class->id);
        if ($rows->isEmpty()) {
            throw new RuntimeException('There are no fee defaulters in this class.');
        }

        $lines = $rows->map(fn (array $row) => $row['student']->full_name.' — UGX '.number_format($row['balance'], 0))->implode("\n");
        $body = 'Fee defaulters in '.$class->name.":\n\n".$lines."\n\nPlease follow up with the parents.";

        $this->messages->start(
            $school,
            $actor,
            [$teacher->id],
            $body,
            'Defaulters · '.$class->name,
        );

        $this->audit->record('fees.defaulters.notified', $class, [
            'class_id' => $class->id,
            'count' => $rows->count(),
            'teacher_id' => $teacher->id,
        ], $actor);
    }
}
