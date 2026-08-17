<?php

namespace App\Services\Dashboard;

use App\Models\AssessmentMarksheet;
use App\Models\AssessmentPeriod;
use App\Models\AttendanceRecord;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Mark;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\Authorization\AssignedClassResolver;
use Illuminate\Support\Collection;

/**
 * Composes role workspaces from permission + assignment union. One account, many responsibilities.
 */
class RoleWorkspaceService
{
    public function __construct(private AssignedClassResolver $assigned) {}

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>
     */
    public function build(School $school, User $user, array $permissions): array
    {
        $roleKeys = $user->activeAssignments()
            ->where('school_id', $school->id)
            ->with('role')
            ->get()
            ->pluck('role.key')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $hour = (int) now(config('app.timezone'))->format('G');
        $hello = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $isDirector = in_array(Role::DIRECTOR, $roleKeys, true);

        return [
            'roleKeys' => $roleKeys,
            'greeting' => $hello.', '.$user->full_name,
            'teacher' => $this->teacherWorkspace($school, $user, $permissions),
            'homeroom' => $this->homeroomWorkspace($school, $user, $permissions),
            'bursar' => $this->bursarWorkspace($school, $permissions),
            'academicLead' => $this->academicWorkspace($school, $permissions),
            'operationsLead' => $isDirector ? null : $this->operationsWorkspace($school, $permissions),
            'governance' => $isDirector ? $this->governanceWorkspace($school, $permissions) : null,
        ];
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>|null
     */
    private function teacherWorkspace(School $school, User $user, array $permissions): ?array
    {
        if (! $this->has($permissions, 'assessment.enter')) {
            return null;
        }

        $assignments = $this->assigned->teachingAssignments($user, (int) $school->id);
        if ($assignments->isEmpty() && $this->assigned->isSchoolWide($user, (int) $school->id)) {
            return null;
        }

        $assignments->loadMissing(['schoolClass', 'subject']);

        $today = (int) now(config('app.timezone'))->isoWeekday();
        $lessons = TimetableSlot::query()
            ->where('school_id', $school->id)
            ->where('teacher_id', $user->id)
            ->where('day_of_week', $today)
            ->with(['period', 'schoolClass', 'subject', 'room'])
            ->get()
            ->sortBy(fn (TimetableSlot $slot) => $slot->period?->sequence ?? $slot->period_id)
            ->values();

        $classes = $assignments->groupBy('class_id')->map(function (Collection $rows) {
            $first = $rows->first();

            return [
                'class_id' => (int) $first?->class_id,
                'class' => $first?->schoolClass?->displayName() ?? 'Class '.$first?->class_id,
                'subjects' => $rows->map(fn ($row) => $row->subject?->name)->filter()->unique()->values()->all(),
            ];
        })->values()->all();

        return [
            'lessons' => $lessons,
            'classes' => $classes,
            'load' => $assignments->count(),
        ];
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>|null
     */
    private function homeroomWorkspace(School $school, User $user, array $permissions): ?array
    {
        if (! $this->has($permissions, 'class.view')) {
            return null;
        }

        $classIds = $this->assigned->classTeacherClassIds($user, (int) $school->id);
        if ($classIds === []) {
            return null;
        }

        $classId = $classIds[0];
        $students = Student::query()
            ->where('school_id', $school->id)
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->with(['schoolClass', 'guardianships.guardian'])
            ->orderBy('full_name')
            ->get();

        $today = now(config('app.timezone'))->toDateString();
        $counts = AttendanceRecord::query()
            ->where('school_id', $school->id)
            ->where('class_id', $classId)
            ->whereDate('attended_on', $today)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $feesCleared = 0;
        $feeTotal = $students->count();
        if ($feeTotal > 0) {
            $owing = FeeInvoice::query()
                ->where('school_id', $school->id)
                ->whereIn('student_id', $students->pluck('id'))
                ->whereIn('status', ['open', 'partial'])
                ->where('balance', '>', 0)
                ->distinct()
                ->count('student_id');
            $feesCleared = max(0, $feeTotal - $owing);
        }

        $className = $students->first()?->schoolClass?->displayName()
            ?? SchoolClass::query()->find($classId)?->displayName()
            ?? 'My class';

        return [
            'class_id' => $classId,
            'class_name' => $className,
            'students' => $students->count(),
            'present' => (int) ($counts['present'] ?? 0),
            'absent' => (int) ($counts['absent'] ?? 0),
            'late' => (int) ($counts['late'] ?? 0),
            'fees_cleared' => $feesCleared,
            'fees_total' => $feeTotal,
            'roster' => $students->take(12),
        ];
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>|null
     */
    private function bursarWorkspace(School $school, array $permissions): ?array
    {
        if (! $this->has($permissions, 'finance.manage')) {
            return null;
        }

        return $this->financeSnapshot($school);
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>|null
     */
    private function academicWorkspace(School $school, array $permissions): ?array
    {
        if (! $this->hasAny($permissions, ['assessment.manage', 'curriculum.manage'])) {
            return null;
        }

        $period = AssessmentPeriod::query()
            ->where('school_id', $school->id)
            ->orderByDesc('id')
            ->first();

        $counts = collect();
        if ($period) {
            $counts = AssessmentMarksheet::query()
                ->where('school_id', $school->id)
                ->where('assessment_period_id', $period->id)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');
        }

        $draft = (int) ($counts['draft'] ?? 0);
        $submitted = (int) ($counts['submitted'] ?? 0);
        $verified = (int) ($counts['verified'] ?? 0);
        $total = max(1, $draft + $submitted + $verified);

        return [
            'period' => $period?->name,
            'draft' => $draft,
            'submitted' => $submitted,
            'verified' => $verified,
            'submitted_pct' => (int) round((($submitted + $verified) / $total) * 100),
            'verified_pct' => (int) round(($verified / $total) * 100),
        ];
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>|null
     */
    private function operationsWorkspace(School $school, array $permissions): ?array
    {
        if (! $this->hasAny($permissions, ['staff.manage', 'attendance.manage'])) {
            return null;
        }
        if ($this->has($permissions, 'finance.manage')) {
            return null;
        }

        return $this->schoolKpis($school, $permissions);
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>|null
     */
    private function governanceWorkspace(School $school, array $permissions): ?array
    {
        if (! $this->has($permissions, 'reports.view') && ! $this->has($permissions, 'finance.view')) {
            return null;
        }

        $kpis = $this->schoolKpis($school, $permissions);
        $kpis['mode'] = 'governance';

        return $kpis;
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>
     */
    private function schoolKpis(School $school, array $permissions): array
    {
        $students = Student::query()->where('school_id', $school->id)->where('status', 'active')->count();
        $staff = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->toBase()
            ->distinct()
            ->count('user_id');

        $today = AttendanceRecord::query()
            ->where('school_id', $school->id)
            ->whereDate('attended_on', now(config('app.timezone'))->toDateString())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $marked = (int) $today->sum();
        $present = (int) ($today['present'] ?? 0) + (int) ($today['late'] ?? 0);
        $attendancePct = $marked > 0 ? (int) round(($present / $marked) * 100) : null;

        $finance = $this->hasAny($permissions, ['finance.view', 'finance.manage'])
            ? $this->financeSnapshot($school)
            : null;

        $mean = $this->has($permissions, 'assessment.view')
            ? Mark::query()
                ->where('school_id', $school->id)
                ->whereHas('period', fn ($q) => $q->whereIn('status', ['published', 'locked']))
                ->avg('score')
            : null;

        return [
            'students' => $students,
            'staff' => $staff,
            'attendance_pct' => $attendancePct,
            'academic_mean' => $mean !== null ? round((float) $mean, 1) : null,
            'finance' => $finance,
        ];
    }

    /** @return array<string, mixed> */
    private function financeSnapshot(School $school): array
    {
        $outstanding = (float) FeeInvoice::query()
            ->where('school_id', $school->id)
            ->whereIn('status', ['open', 'partial'])
            ->sum('balance');
        $expected = (float) FeeInvoice::query()
            ->where('school_id', $school->id)
            ->where('status', '!=', 'void')
            ->sum('amount');
        $collected = (float) FeePayment::query()
            ->where('school_id', $school->id)
            ->where('status', 'confirmed')
            ->sum('amount');
        $pending = FeePayment::query()
            ->where('school_id', $school->id)
            ->where('status', 'pending')
            ->count();
        $rate = $expected > 0 ? (int) round(($collected / $expected) * 100) : 0;

        return [
            'expected' => $expected,
            'collected' => $collected,
            'outstanding' => $outstanding,
            'rate' => $rate,
            'pending' => $pending,
        ];
    }

    /** @param list<string> $permissions */
    private function has(array $permissions, string $perm): bool
    {
        return in_array($perm, $permissions, true);
    }

    /** @param list<string> $permissions @param list<string> $perms */
    private function hasAny(array $permissions, array $perms): bool
    {
        foreach ($perms as $perm) {
            if ($this->has($permissions, $perm)) {
                return true;
            }
        }

        return false;
    }
}
