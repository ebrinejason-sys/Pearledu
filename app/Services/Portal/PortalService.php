<?php

namespace App\Services\Portal;

use App\Models\Announcement;
use App\Models\Enrollment;
use App\Models\FeeInvoice;
use App\Models\Mark;
use App\Models\Student;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/** Resolve linked learners and portal data for parents and students. */
class PortalService
{
    public function __construct(private TenantContext $context) {}

    /** @return Collection<int, Student> */
    public function learnersFor(User $user): Collection
    {
        $schoolId = $this->context->schoolId();
        abort_unless($schoolId, 404);

        $permissions = $user->permissionsForSchool($schoolId);

        if (in_array('child.results.view', $permissions, true)
            || in_array('child.fees.view', $permissions, true)
            || in_array('fees.pay', $permissions, true)) {
            return Student::query()
                ->where('school_id', $schoolId)
                ->whereIn('id', $user->guardianships()->pluck('student_id'))
                ->with('schoolClass')
                ->orderBy('full_name')
                ->get();
        }

        if (in_array('self.results.view', $permissions, true)
            || in_array('self.timetable.view', $permissions, true)
            || in_array('lms.view', $permissions, true)
            || in_array('cbt.take', $permissions, true)) {
            return Student::query()
                ->where('school_id', $schoolId)
                ->where('user_id', $user->id)
                ->with('schoolClass')
                ->orderBy('full_name')
                ->get();
        }

        return collect();
    }

    public function resolveStudent(User $user, ?int $studentId): Student
    {
        $learners = $this->learnersFor($user);
        abort_if($learners->isEmpty(), 403, 'No linked learner found for this account.');

        if ($studentId) {
            $student = $learners->firstWhere('id', $studentId);
            abort_unless($student, 403, 'You cannot view this learner.');

            return $student;
        }

        return $learners->first();
    }

    /** @return Collection<int, Mark> */
    public function results(Student $student): Collection
    {
        return Mark::query()
            ->where('student_id', $student->id)
            ->whereHas('period', fn ($q) => $q->whereIn('status', ['published', 'locked']))
            ->with(['subject', 'period'])
            ->orderByDesc('id')
            ->limit(200)
            ->get();
    }

    /** @return Collection<int, FeeInvoice> */
    public function invoices(Student $student): Collection
    {
        return FeeInvoice::query()
            ->where('student_id', $student->id)
            ->with(['structure', 'payments'])
            ->orderByDesc('id')
            ->get();
    }

    /** @return Collection<int, TimetableSlot> */
    public function timetable(Student $student): Collection
    {
        $classId = $student->class_id
            ?? Enrollment::query()
                ->where('student_id', $student->id)
                ->where('status', 'active')
                ->value('class_id');

        if (! $classId) {
            return collect();
        }

        return TimetableSlot::query()
            ->where('class_id', $classId)
            ->with(['period', 'subject', 'teacher', 'room'])
            ->orderBy('day_of_week')
            ->orderBy('period_id')
            ->get();
    }

    /** @return Collection<int, Announcement> */
    public function announcements(Student $student, User $user): Collection
    {
        $schoolId = (int) $student->school_id;
        $isParent = $user->guardianships()->where('student_id', $student->id)->exists();

        // Canonical + legacy aliases (school→all, guardians→parents).
        $audiences = ['all', 'school'];
        if ($isParent) {
            array_push($audiences, 'parents', 'guardians');
        } else {
            $audiences[] = 'students';
        }

        return Announcement::query()
            ->where('school_id', $schoolId)
            ->where(function ($q) use ($student, $audiences) {
                $q->whereIn('audience', $audiences)
                    ->orWhere(function ($q2) use ($student) {
                        $q2->where('audience', 'class')->where('class_id', $student->class_id);
                    });
            })
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    public function assertCanPay(User $user, FeeInvoice $invoice): void
    {
        $schoolId = $this->context->schoolId();
        abort_unless($schoolId && (int) $invoice->school_id === (int) $schoolId, 404);

        $permissions = $user->permissionsForSchool($schoolId);
        if (! in_array('fees.pay', $permissions, true)) {
            throw ValidationException::withMessages(['payment' => 'You are not allowed to pay fees.']);
        }

        $allowedIds = $this->learnersFor($user)->pluck('id')->all();
        if (! in_array((int) $invoice->student_id, $allowedIds, true)) {
            throw ValidationException::withMessages(['payment' => 'This invoice is not for your linked learner.']);
        }
    }
}
