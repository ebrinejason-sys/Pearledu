<?php

namespace App\Services\Assessment;

use App\Models\AssessmentMarksheet;
use App\Models\AssessmentPeriod;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Authorization\AssessmentScope;
use Illuminate\Validation\ValidationException;

class MarksheetWorkflow
{
    public function __construct(
        private AssessmentScope $scope,
        private AssessmentPeriodWorkflow $periods,
        private AuditLogger $audit,
    ) {}

    public function locate(int $schoolId, int $periodId, int $classId, int $subjectId): AssessmentMarksheet
    {
        return AssessmentMarksheet::query()->firstOrCreate(
            [
                'school_id' => $schoolId,
                'assessment_period_id' => $periodId,
                'class_id' => $classId,
                'subject_id' => $subjectId,
            ],
            ['status' => AssessmentMarksheet::STATUS_DRAFT],
        );
    }

    public function find(int $schoolId, int $periodId, int $classId, int $subjectId): ?AssessmentMarksheet
    {
        return AssessmentMarksheet::query()
            ->where('school_id', $schoolId)
            ->where('assessment_period_id', $periodId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->first();
    }

    public function canEditMarks(User $user, AssessmentPeriod $period, ?AssessmentMarksheet $sheet): bool
    {
        if (! $this->periods->canEnterMarks($period)) {
            return false;
        }
        if ($sheet && ! $this->scope->canEnter($user, (int) $period->school_id, (int) $sheet->class_id, (int) $sheet->subject_id)) {
            return false;
        }
        if ($sheet === null || $sheet->status === AssessmentMarksheet::STATUS_DRAFT) {
            return true;
        }

        return $this->scope->canManage($user, (int) $period->school_id);
    }

    public function submit(User $user, AssessmentPeriod $period, int $classId, int $subjectId): AssessmentMarksheet
    {
        $schoolId = (int) $period->school_id;
        abort_unless($this->scope->canEnter($user, $schoolId, $classId, $subjectId), 403);
        if (! $this->has($user, $schoolId, 'marksheet.submit') && ! $this->scope->canManage($user, $schoolId)) {
            abort(403, 'You cannot submit this marksheet.');
        }

        $sheet = $this->locate($schoolId, (int) $period->id, $classId, $subjectId);
        if ($sheet->status !== AssessmentMarksheet::STATUS_DRAFT) {
            throw ValidationException::withMessages(['marksheet' => 'Only a draft marksheet can be submitted.']);
        }
        if (! $this->periods->canEnterMarks($period)) {
            throw ValidationException::withMessages(['marksheet' => 'Mark entry is not open for this period.']);
        }

        $sheet->update([
            'status' => AssessmentMarksheet::STATUS_SUBMITTED,
            'submitted_by' => $user->id,
            'submitted_at' => now(),
        ]);

        $this->audit->record('assessment.marksheet.submitted', $sheet, [
            'period_id' => $period->id,
            'class_id' => $classId,
            'subject_id' => $subjectId,
        ], actor: $user);

        return $sheet;
    }

    public function verify(User $user, AssessmentPeriod $period, int $classId, int $subjectId): AssessmentMarksheet
    {
        $schoolId = (int) $period->school_id;
        if (! $this->has($user, $schoolId, 'marksheet.verify') && ! $this->scope->canManage($user, $schoolId)) {
            abort(403, 'You cannot verify this marksheet.');
        }

        $sheet = $this->locate($schoolId, (int) $period->id, $classId, $subjectId);
        if ($sheet->status !== AssessmentMarksheet::STATUS_SUBMITTED) {
            throw ValidationException::withMessages(['marksheet' => 'Only a submitted marksheet can be verified.']);
        }

        $sheet->update([
            'status' => AssessmentMarksheet::STATUS_VERIFIED,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        $this->audit->record('assessment.marksheet.verified', $sheet, [
            'period_id' => $period->id,
            'class_id' => $classId,
            'subject_id' => $subjectId,
        ], actor: $user);

        return $sheet;
    }

    public function returnToDraft(User $user, AssessmentPeriod $period, int $classId, int $subjectId): AssessmentMarksheet
    {
        $schoolId = (int) $period->school_id;
        abort_unless($this->scope->canManage($user, $schoolId), 403);
        if (! $this->periods->canEnterMarks($period)) {
            throw ValidationException::withMessages(['marksheet' => 'Reopen mark entry before returning a marksheet to draft.']);
        }

        $sheet = $this->locate($schoolId, (int) $period->id, $classId, $subjectId);
        $sheet->update([
            'status' => AssessmentMarksheet::STATUS_DRAFT,
            'verified_by' => null,
            'verified_at' => null,
        ]);

        $this->audit->record('assessment.marksheet.returned', $sheet, [
            'period_id' => $period->id,
            'class_id' => $classId,
            'subject_id' => $subjectId,
        ], actor: $user);

        return $sheet;
    }

    private function has(User $user, int $schoolId, string $permission): bool
    {
        return in_array($permission, $user->permissionsForSchool($schoolId), true);
    }
}
