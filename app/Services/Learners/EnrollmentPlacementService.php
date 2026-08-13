<?php

namespace App\Services\Learners;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Student;

/**
 * Single writer for academic placement. Enrollment is authoritative;
 * students.class_id is a derived cache for list/filter speed.
 */
class EnrollmentPlacementService
{
    public function place(
        Student $student,
        int $classId,
        AcademicYear $year,
        string $status = 'active',
        ?string $enrolledOn = null,
    ): Enrollment {
        $enrollment = Enrollment::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $year->id)
            ->first();

        $on = $enrolledOn ?? now()->toDateString();

        if ($enrollment) {
            $enrollment->update([
                'class_id' => $classId,
                'status' => $status,
                'enrolled_on' => $enrollment->enrolled_on ?? $on,
                'exited_on' => $status === 'active' ? null : ($enrollment->exited_on ?? $on),
            ]);
        } else {
            $enrollment = Enrollment::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'class_id' => $classId,
                'academic_year_id' => $year->id,
                'status' => $status,
                'enrolled_on' => $on,
                'exited_on' => $status === 'active' ? null : $on,
            ]);
        }

        $this->syncCachedClass($student, $status === 'active' ? $classId : $student->class_id);

        return $enrollment->fresh();
    }

    public function complete(Enrollment $enrollment, ?string $exitedOn = null): void
    {
        $enrollment->update([
            'status' => 'completed',
            'exited_on' => $exitedOn ?? now()->toDateString(),
        ]);
    }

    public function syncCachedClass(Student $student, ?int $classId): void
    {
        if ((int) $student->class_id === (int) $classId) {
            return;
        }

        $student->forceFill(['class_id' => $classId])->save();
    }
}
