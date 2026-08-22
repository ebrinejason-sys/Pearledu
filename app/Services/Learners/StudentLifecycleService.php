<?php

namespace App\Services\Learners;

use App\Models\AcademicYear;
use App\Models\AdmissionApplication;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\Academics\CurrentAcademicContext;
use App\Services\Fees\FeeInvoiceService;
use App\Services\Students\GuardianLinkService;
use App\Support\Residency;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentLifecycleService
{
    public function __construct(
        private CurrentAcademicContext $academic,
        private EnrollmentPlacementService $placement,
        private GuardianLinkService $guardians,
        private FeeInvoiceService $billing,
    ) {}

    /**
     * Admit: student + current-year enrollment + guardian invite + default invoices.
     *
     * @return array{student: Student, enrollment: ?Enrollment, warnings: list<string>, invoices: array}
     */
    public function admitFromApplication(
        AdmissionApplication $application,
        ?int $classId = null,
        ?int $invitedBy = null,
        ?string $residency = null,
    ): array {
        return DB::transaction(function () use ($application, $classId, $invitedBy, $residency) {
            if ($application->student_id) {
                $student = Student::query()->findOrFail($application->student_id);

                return [
                    'student' => $student,
                    'enrollment' => $student->currentEnrollment(),
                    'warnings' => ['This application was already admitted.'],
                    'invoices' => ['created' => 0, 'already_existed' => 0, 'skipped' => 0],
                ];
            }

            $classId ??= $application->requested_class_id;
            if (! $classId) {
                throw ValidationException::withMessages([
                    'class_id' => 'Choose a class before admitting this learner.',
                ]);
            }

            $year = $this->academic->year();
            if (! $year) {
                throw ValidationException::withMessages([
                    'academic_year' => 'Create and set a current academic year before admitting learners.',
                ]);
            }

            $student = Student::create([
                'school_id' => $application->school_id,
                'full_name' => $application->applicant_name,
                'class_id' => $classId,
                'status' => 'active',
                'residency' => Residency::normalize($residency),
            ]);

            $enrollment = $this->enrollStudent($student, (int) $classId, (int) $year->id, $residency);

            $application->update([
                'status' => 'enrolled',
                'student_id' => $student->id,
                'admitted_at' => now(),
            ]);

            $warnings = [];
            $this->linkGuardianFromApplication($student, $application, $invitedBy, $warnings);

            $invoices = $this->billing->assignDefaultStructures(
                $student->fresh(),
                (int) $classId,
                $this->academic->term($year)?->id,
            );

            return compact('student', 'enrollment', 'warnings', 'invoices');
        });
    }

    public function enrollStudent(Student $student, int $classId, ?int $academicYearId = null, ?string $residency = null): Enrollment
    {
        $year = $academicYearId
            ? AcademicYear::query()
                ->where('school_id', $student->school_id)
                ->whereKey($academicYearId)
                ->first()
            : $this->academic->year();

        if (! $year) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'No academic year is available for enrollment.',
            ]);
        }

        if ($residency !== null) {
            $student->forceFill(['residency' => Residency::normalize($residency)])->save();
        }

        $enrollment = $this->placement->place($student, $classId, $year, 'active');
        $this->billing->assignDefaultStructures(
            $student->fresh(),
            $classId,
            $this->academic->term($year)?->id,
        );

        return $enrollment;
    }

    public function transferStudent(Student $student, int $toClassId, ?int $academicYearId = null): Enrollment
    {
        return $this->enrollStudent($student, $toClassId, $academicYearId);
    }

    public function promoteStudent(Student $student, int $toClassId, int $fromYearId, int $toYearId): Enrollment
    {
        $old = Enrollment::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $fromYearId)
            ->where('status', 'active')
            ->first();

        if ($old) {
            $this->placement->complete($old);
        }

        $toYear = AcademicYear::query()->findOrFail($toYearId);

        return $this->placement->place($student, $toClassId, $toYear, 'active');
    }

    public function graduateStudent(Student $student): Student
    {
        return DB::transaction(function () use ($student) {
            $active = $student->enrollments()->where('status', 'active')->get();
            foreach ($active as $enrollment) {
                $enrollment->update([
                    'status' => 'graduated',
                    'exited_on' => now()->toDateString(),
                ]);
            }
            $student->update(['status' => 'graduated', 'class_id' => null]);

            return $student->fresh();
        });
    }

    public function withdrawStudent(Student $student, string $as = 'transferred'): Student
    {
        return DB::transaction(function () use ($student, $as) {
            $status = $as === 'inactive' ? 'inactive' : 'transferred';
            $active = $student->enrollments()->where('status', 'active')->get();
            foreach ($active as $enrollment) {
                $enrollment->update([
                    'status' => 'transferred',
                    'exited_on' => now()->toDateString(),
                ]);
            }
            $student->update(['status' => $status]);

            return $student->fresh();
        });
    }

    /** @param list<string> $warnings */
    private function linkGuardianFromApplication(
        Student $student,
        AdmissionApplication $application,
        ?int $invitedBy,
        array &$warnings,
    ): void {
        $email = trim((string) $application->guardian_email);
        if ($email === '') {
            if (filled($application->guardian_name) || filled($application->guardian_phone)) {
                $warnings[] = 'Guardian details were saved on the application but no email was provided, so no parent invite was sent.';
            }

            return;
        }

        try {
            $this->guardians->inviteNew(
                $student,
                $application->guardian_name ?: 'Parent',
                $email,
                $application->guardian_phone,
                'guardian',
                true,
                $invitedBy,
            );
        } catch (ValidationException $e) {
            try {
                $this->guardians->attachExisting(
                    $student,
                    $email,
                    'guardian',
                    true,
                    $invitedBy,
                );
            } catch (ValidationException $attach) {
                $first = collect($e->errors())->flatten()->first();
                $warnings[] = is_string($first) && $first !== ''
                    ? $first
                    : 'Guardian could not be invited automatically. Link them from the student record.';
            }
        }
    }
}
