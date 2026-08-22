<?php

namespace App\Services\Dashboard;

use App\Models\AssessmentMarksheet;
use App\Models\AssessmentPeriod;
use App\Models\AttendanceRecord;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\HelpdeskTicket;
use App\Models\LmsAssignment;
use App\Models\Mark;
use App\Models\PromotionBatch;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StaffTimePunch;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\Academics\TeachingLoadService;
use App\Services\Authorization\AssignedClassResolver;
use App\Services\People\GenderStatsService;
use App\Services\Schools\SchoolSetupService;
use Illuminate\Support\Facades\Route;

/**
 * Composes role workspaces from permission + assignment union. One account, many responsibilities.
 */
class RoleWorkspaceService
{
    public function __construct(
        private AssignedClassResolver $assigned,
        private GenderStatsService $gender,
        private TeachingLoadService $load,
        private SchoolSetupService $setup,
    ) {}

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

        $homeroom = $this->homeroomWorkspace($school, $user, $permissions);
        $teacher = $this->teacherWorkspace($school, $user, $permissions);
        $bursar = $this->bursarWorkspace($school, $permissions);
        $academicLead = $this->academicWorkspace($school, $permissions, $roleKeys);
        $operationsLead = $isDirector ? null : $this->operationsWorkspace($school, $permissions, $roleKeys);
        $governance = $isDirector ? $this->governanceWorkspace($school, $permissions) : null;
        $hygiene = $this->hygieneWorkspace($school, $permissions);

        $workspaces = [
            'homeroom' => $homeroom,
            'teacher' => $teacher,
            'academicLead' => $academicLead,
            'bursar' => $bursar,
            'operationsLead' => $operationsLead,
            'governance' => $governance,
            'hygiene' => $hygiene,
        ];

        $primary = $this->primaryKey($roleKeys, $workspaces);

        return [
            'roleKeys' => $roleKeys,
            'primary' => $primary,
            'greeting' => $hello.', '.$user->full_name,
            'mantra' => $this->mantra($primary, $roleKeys),
            'crest_url' => $school->logoUrl(),
            'teacher' => $teacher,
            'homeroom' => $homeroom,
            'bursar' => $bursar,
            'academicLead' => $academicLead,
            'operationsLead' => $operationsLead,
            'governance' => $governance,
            'hygiene' => $hygiene,
        ];
    }

    /**
     * @param  list<string>  $roleKeys
     * @param  array<string, mixed>  $workspaces
     */
    private function primaryKey(array $roleKeys, array $workspaces): string
    {
        $order = [
            [Role::CLASS_TEACHER, 'homeroom'],
            [Role::SUBJECT_TEACHER, 'teacher'],
            [Role::DIRECTOR_OF_STUDIES, 'academicLead'],
            [Role::BURSAR, 'bursar'],
            [Role::DEPUTY_HEAD_TEACHER, 'operationsLead'],
            [Role::HEAD_TEACHER, 'operationsLead'],
            [Role::DIRECTOR, 'governance'],
            [Role::SCHOOL_ADMIN, 'hygiene'],
        ];

        foreach ($order as [$role, $key]) {
            if (in_array($role, $roleKeys, true) && ! empty($workspaces[$key])) {
                return $key;
            }
        }

        foreach (['homeroom', 'teacher', 'academicLead', 'bursar', 'operationsLead', 'governance', 'hygiene'] as $key) {
            if (! empty($workspaces[$key])) {
                return $key;
            }
        }

        return 'none';
    }

    /** @param  list<string>  $roleKeys */
    private function mantra(string $primary, array $roleKeys): string
    {
        return match ($primary) {
            'homeroom' => 'You are the primary point of contact for your group.',
            'teacher' => 'Deliver content and assess mastery for your assigned classes.',
            'academicLead' => 'Ensure what is taught is measured correctly.',
            'bursar' => 'Collect, reconcile, and keep the ledger clean.',
            'operationsLead' => in_array(Role::DEPUTY_HEAD_TEACHER, $roleKeys, true) && ! in_array(Role::HEAD_TEACHER, $roleKeys, true)
                ? 'Turn policy into today’s routines.'
                : 'Own culture, discipline, and outcomes — approve, don’t type the register.',
            'governance' => 'Set the destination; you don’t drive the bus.',
            'hygiene' => 'Keep the engine running and the data clean.',
            default => 'Your school workspace.',
        };
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
        $nowTime = now(config('app.timezone'))->format('H:i');
        $lessons = TimetableSlot::query()
            ->where('school_id', $school->id)
            ->where('teacher_id', $user->id)
            ->where('day_of_week', $today)
            ->with(['period', 'schoolClass', 'subject', 'room'])
            ->get()
            ->sortBy(fn (TimetableSlot $slot) => $slot->period?->sequence ?? $slot->period_id)
            ->values()
            ->map(function (TimetableSlot $slot) use ($nowTime) {
                $start = (string) ($slot->period?->starts_at ?? '');
                $end = (string) ($slot->period?->ends_at ?? '');
                $inWindow = $nowTime >= $start && $nowTime <= $end;
                $current = $start !== '' && $end !== '' && $inWindow;

                return [
                    'period' => $slot->period?->name ?? 'Period',
                    'starts_at' => $slot->period?->starts_at,
                    'ends_at' => $slot->period?->ends_at,
                    'subject' => $slot->subject?->name ?? 'Subject',
                    'class' => $slot->schoolClass instanceof SchoolClass ? $slot->schoolClass->displayName() : 'Class',
                    'class_id' => (int) $slot->class_id,
                    'subject_id' => (int) $slot->subject_id,
                    'room' => $slot->room?->name,
                    'current' => $current,
                ];
            })
            ->all();

        $period = AssessmentPeriod::query()
            ->where('school_id', $school->id)
            ->orderByDesc('id')
            ->first();

        $marksheets = $period
            ? AssessmentMarksheet::query()
                ->where('school_id', $school->id)
                ->where('assessment_period_id', $period->id)
                ->whereIn('class_id', $assignments->pluck('class_id')->all() ?: [0])
                ->get()
            : collect();

        $homeroomByClass = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->whereNotNull('class_id')
            ->whereHas('role', fn ($q) => $q->where('key', Role::CLASS_TEACHER))
            ->with('user')
            ->get()
            ->keyBy(fn (RoleAssignment $row) => (int) $row->class_id);

        $classes = [];
        foreach ($assignments->groupBy('class_id') as $rows) {
            $first = $rows->first();
            if (! $first instanceof TeachingAssignment) {
                continue;
            }
            $class = $first->schoolClass;
            $classId = (int) $first->class_id;
            $homeroom = $homeroomByClass->get($classId);
            $subjects = [];
            foreach ($rows as $row) {
                if (! $row instanceof TeachingAssignment) {
                    continue;
                }
                $sheet = $marksheets->first(fn (AssessmentMarksheet $s) => (int) $s->class_id === $classId
                    && (int) $s->subject_id === (int) $row->subject_id);
                $subjects[] = [
                    'id' => (int) $row->subject_id,
                    'name' => $row->subject?->name ?? 'Subject',
                    'status' => $sheet?->status ?? 'draft',
                    'revoked' => $sheet?->uploadRevoked() ?? false,
                ];
            }
            $classes[] = [
                'class_id' => $classId,
                'class' => $class instanceof SchoolClass ? $class->displayName() : 'Class '.$first->class_id,
                'subjects' => $subjects,
                'class_teacher_id' => $homeroom instanceof RoleAssignment ? (int) $homeroom->user_id : null,
                'class_teacher_name' => $homeroom instanceof RoleAssignment ? $homeroom->user?->full_name : null,
            ];
        }

        $lmsDue = LmsAssignment::query()
            ->where('school_id', $school->id)
            ->where('created_by', $user->id)
            ->whereNotNull('due_at')
            ->where('due_at', '>=', now())
            ->orderBy('due_at')
            ->limit(5)
            ->get(['id', 'title', 'due_at', 'class_id', 'subject_id']);

        return [
            'lessons' => $lessons,
            'classes' => $classes,
            'load' => $assignments->count(),
            'period' => $period?->name,
            'lms_due' => $lmsDue->map(fn (LmsAssignment $a) => [
                'title' => $a->title,
                'due' => $a->due_at?->timezone(config('app.timezone'))->format('d M H:i'),
            ])->all(),
            'can_message' => $this->has($permissions, 'staff.messages'),
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

        $present = (int) ($counts['present'] ?? 0);
        $absent = (int) ($counts['absent'] ?? 0);
        $late = (int) ($counts['late'] ?? 0);
        $excused = (int) ($counts['excused'] ?? 0);
        $marked = $present + $absent + $late + $excused;
        $unmarked = max(0, $students->count() - $marked);

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

        $firstLearner = $students->first();
        $classFromRoster = $firstLearner?->schoolClass;
        $classRecord = $classFromRoster instanceof SchoolClass
            ? $classFromRoster
            : SchoolClass::query()->find($classId);
        $className = $classRecord instanceof SchoolClass ? $classRecord->displayName() : 'My class';

        $periods = AssessmentPeriod::query()
            ->where('school_id', $school->id)
            ->orderByDesc('id')
            ->get();

        $subjects = TeachingAssignment::query()
            ->where('school_id', $school->id)
            ->where('class_id', $classId)
            ->forCurrentYear((int) $school->id)
            ->effective()
            ->with(['subject', 'teacher'])
            ->get()
            ->unique('subject_id')
            ->values();

        $marksheets = AssessmentMarksheet::query()
            ->where('school_id', $school->id)
            ->where('class_id', $classId)
            ->get();

        $sets = [];
        foreach ($periods as $period) {
            $rows = [];
            foreach ($subjects as $assignment) {
                $sheet = $marksheets->first(fn (AssessmentMarksheet $s) => (int) $s->assessment_period_id === (int) $period->id
                    && (int) $s->subject_id === (int) $assignment->subject_id);
                $rows[] = [
                    'subject_id' => (int) $assignment->subject_id,
                    'subject' => $assignment->subject?->name ?? 'Subject',
                    'teacher' => $assignment->teacher?->full_name,
                    'teacher_photo' => $assignment->teacher?->avatarUrl(),
                    'teacher_initial' => $assignment->teacher?->avatarInitial() ?? '?',
                    'status' => $sheet?->status ?? 'draft',
                    'revoked' => $sheet?->uploadRevoked() ?? false,
                    'can_revoke' => $period->entryDeadlinePassed() && ! ($sheet?->uploadRevoked() ?? false),
                ];
            }
            $sets[] = [
                'id' => (int) $period->id,
                'name' => $period->name,
                'kind' => $period->kindShort(),
                'deadline' => $period->entry_deadline?->format('d M Y'),
                'deadline_passed' => $period->entryDeadlinePassed(),
                'status' => $period->status,
                'subjects' => $rows,
            ];
        }

        $streams = $classRecord instanceof SchoolClass ? $classRecord->siblingStreams() : collect();
        $ringTotal = max(1, $students->count());

        $parents = $students
            ->map(function (Student $student) {
                $g = $student->guardianships->first()?->guardian;
                if (! $g instanceof User) {
                    return null;
                }

                return [
                    'student' => $student->full_name,
                    'name' => $g->full_name,
                    'phone' => $g->phone,
                    'photo' => $g->avatarUrl(),
                    'initial' => $g->avatarInitial(),
                ];
            })
            ->filter()
            ->unique('name')
            ->values()
            ->all();

        $openTickets = HelpdeskTicket::query()
            ->where('school_id', $school->id)
            ->where('status', '!=', 'closed')
            ->where(fn ($q) => $q->where('assigned_to', $user->id)->orWhere('user_id', $user->id))
            ->count();

        return [
            'class_id' => $classId,
            'class_name' => $className,
            'students' => $students->count(),
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'unmarked' => $unmarked,
            'ring' => [
                'present_pct' => (int) round(($present / $ringTotal) * 100),
                'absent_pct' => (int) round(($absent / $ringTotal) * 100),
                'late_pct' => (int) round(($late / $ringTotal) * 100),
                'unmarked_pct' => (int) round(($unmarked / $ringTotal) * 100),
            ],
            'fees_cleared' => $feesCleared,
            'fees_total' => $feeTotal,
            'roster' => $students,
            'gender' => $this->gender->countStudents($school, $classId),
            'exam_sets' => $sets,
            'streams' => $streams,
            'parents' => $parents,
            'open_tickets' => $openTickets,
            'report_url' => Route::has('app.assessment.reports') ? route('app.assessment.reports') : null,
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
     * @param  list<string>  $roleKeys
     * @return array<string, mixed>|null
     */
    private function academicWorkspace(School $school, array $permissions, array $roleKeys): ?array
    {
        if (! $this->hasAny($permissions, ['assessment.manage', 'curriculum.manage'])) {
            return null;
        }

        $period = AssessmentPeriod::query()
            ->where('school_id', $school->id)
            ->orderByDesc('id')
            ->first();

        $counts = collect();
        $lateDrafts = [];
        if ($period) {
            $counts = AssessmentMarksheet::query()
                ->where('school_id', $school->id)
                ->where('assessment_period_id', $period->id)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            if ($period->entryDeadlinePassed()) {
                $lateDrafts = $this->lateDraftTeachers($school, $period);
            }
        }

        $draft = (int) ($counts['draft'] ?? 0);
        $submitted = (int) ($counts['submitted'] ?? 0);
        $verified = (int) ($counts['verified'] ?? 0);
        $published = ($period && in_array($period->status, ['published', 'locked'], true)) ? $verified : 0;
        $total = max(1, $draft + $submitted + $verified);

        $occupancy = null;
        $bands = [];
        if (in_array(Role::DIRECTOR_OF_STUDIES, $roleKeys, true) || $this->has($permissions, 'assessment.manage')) {
            $occupancy = $this->occupancyPreview($school);
            $bands = $this->gradeBands($school);
        }

        return [
            'period' => $period?->name,
            'period_status' => $period?->status,
            'deadline' => $period?->entry_deadline?->format('d M Y'),
            'deadline_passed' => $period?->entryDeadlinePassed() ?? false,
            'draft' => $draft,
            'submitted' => $submitted,
            'verified' => $verified,
            'published' => $published,
            'funnel_total' => $draft + $submitted + $verified,
            'submitted_pct' => (int) round((($submitted + $verified) / $total) * 100),
            'verified_pct' => (int) round(($verified / $total) * 100),
            'occupancy' => $occupancy,
            'grade_bands' => $bands,
            'late_drafts' => $lateDrafts,
        ];
    }

    /**
     * @param  list<string>  $permissions
     * @param  list<string>  $roleKeys
     * @return array<string, mixed>|null
     */
    private function operationsWorkspace(School $school, array $permissions, array $roleKeys): ?array
    {
        if (! $this->hasAny($permissions, ['staff.manage', 'attendance.manage'])) {
            return null;
        }
        if ($this->has($permissions, 'finance.manage') && ! in_array(Role::HEAD_TEACHER, $roleKeys, true)
            && ! in_array(Role::DEPUTY_HEAD_TEACHER, $roleKeys, true)) {
            return null;
        }

        $isDeputy = in_array(Role::DEPUTY_HEAD_TEACHER, $roleKeys, true);
        $isHead = in_array(Role::HEAD_TEACHER, $roleKeys, true);
        $mode = ($isHead && ! $isDeputy)
            ? 'approvals'
            : (($isDeputy && ! $isHead) ? 'logistics' : ($isHead ? 'approvals' : 'ops'));

        $kpis = $this->schoolKpis($school, $permissions);
        $kpis['mode'] = $mode;
        $kpis['promotions_pending'] = $this->has($permissions, 'promotions.approve')
            ? $this->pendingPromotions($school)
            : [];
        $kpis['helpdesk_open'] = $this->has($permissions, 'helpdesk.manage')
            ? $this->openHelpdesk($school)
            : [];
        $kpis['attendance_gaps'] = $this->attendanceGaps($school);
        $kpis['clock'] = $isDeputy || $mode === 'logistics' ? $this->staffClockToday($school) : null;
        $kpis['uncovered'] = $isDeputy || $mode === 'logistics' ? $this->uncoveredSlots($school) : [];
        $kpis['heatmap'] = $isDeputy || $mode === 'logistics' ? $this->absenceHeatmap($school) : [];
        $kpis['promotions_url'] = ($this->has($permissions, 'promotions.approve') && Route::has('app.promotions.index'))
            ? route('app.promotions.index')
            : null;
        $kpis['helpdesk_url'] = Route::has('app.helpdesk.index') ? route('app.helpdesk.index') : null;
        $kpis['timetable_url'] = Route::has('app.timetable.index') ? route('app.timetable.index') : null;
        $kpis['clock_url'] = Route::has('app.staff.clock') ? route('app.staff.clock') : null;

        return $kpis;
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
        $kpis['exceptions'] = $this->directorExceptions($school, $permissions);
        $kpis['clock_summary'] = $this->staffClockToday($school);
        $kpis['emis'] = $this->gender->emisOverview($school);

        return $kpis;
    }

    /**
     * @param  list<string>  $permissions
     * @return array<string, mixed>|null
     */
    private function hygieneWorkspace(School $school, array $permissions): ?array
    {
        if (! $this->has($permissions, 'school.manage')) {
            return null;
        }

        $hygiene = $this->setup->hygiene($school);
        $hygiene['setup_url'] = Route::has('app.setup.index') ? route('app.setup.index') : null;
        $hygiene['staff_url'] = Route::has('app.staff.index') ? route('app.staff.index') : null;

        return $hygiene;
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
            'gender' => $this->gender->forSchool($school),
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

    /**
     * @return list<array{id:int,from:?string,to:?string,items:int}>
     */
    private function pendingPromotions(School $school): array
    {
        return PromotionBatch::query()
            ->where('school_id', $school->id)
            ->whereNull('committed_at')
            ->where('status', '!=', 'committed')
            ->withCount('items')
            ->with(['fromYear', 'toYear'])
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (PromotionBatch $batch) => [
                'id' => (int) $batch->id,
                'from' => $batch->fromYear?->name,
                'to' => $batch->toYear?->name,
                'items' => (int) $batch->items_count,
                'commit_url' => Route::has('app.promotions.commit') ? route('app.promotions.commit', $batch) : null,
            ])
            ->all();
    }

    /**
     * @return list<array{id:int,subject:string,from:?string,priority:string}>
     */
    private function openHelpdesk(School $school): array
    {
        return HelpdeskTicket::query()
            ->where('school_id', $school->id)
            ->where('status', '!=', 'closed')
            ->with('user')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (HelpdeskTicket $ticket) => [
                'id' => (int) $ticket->id,
                'subject' => $ticket->subject,
                'from' => $ticket->user?->full_name,
                'priority' => $ticket->priority ?: 'normal',
                'category' => $ticket->category,
            ])
            ->all();
    }

    /**
     * @return list<array{class:string,class_id:int,missing:bool,pct:?int}>
     */
    private function attendanceGaps(School $school): array
    {
        $today = now(config('app.timezone'))->toDateString();
        $classes = SchoolClass::query()->where('school_id', $school->id)->orderBy('name')->get();
        $markedIds = AttendanceRecord::query()
            ->where('school_id', $school->id)
            ->whereDate('attended_on', $today)
            ->distinct()
            ->pluck('class_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $out = [];
        foreach ($classes as $class) {
            if (in_array((int) $class->id, $markedIds, true)) {
                continue;
            }
            $out[] = [
                'class' => $class->displayName(),
                'class_id' => (int) $class->id,
                'missing' => true,
                'pct' => null,
            ];
        }

        return array_slice($out, 0, 12);
    }

    /** @return array{in:int,out:int,staff:int,people:list<array{name:string,photo:?string,initial:string,direction:string,at:string}>} */
    private function staffClockToday(School $school): array
    {
        $today = now(config('app.timezone'))->toDateString();
        $punches = StaffTimePunch::query()
            ->where('school_id', $school->id)
            ->whereDate('punched_at', $today)
            ->with('user')
            ->orderByDesc('punched_at')
            ->get();

        $lastByUser = [];
        foreach ($punches as $punch) {
            $uid = (int) $punch->user_id;
            if (! isset($lastByUser[$uid])) {
                $lastByUser[$uid] = $punch;
            }
        }

        $in = 0;
        $out = 0;
        $people = [];
        foreach ($lastByUser as $punch) {
            if ($punch->direction === StaffTimePunch::IN) {
                $in++;
            } else {
                $out++;
            }
            $user = $punch->user;
            $people[] = [
                'name' => $user instanceof User ? $user->full_name : 'Staff',
                'photo' => $user instanceof User ? $user->avatarUrl() : null,
                'initial' => $user instanceof User ? $user->avatarInitial() : '?',
                'direction' => $punch->direction,
                'at' => $punch->punched_at?->timezone(config('app.timezone'))->format('H:i') ?? '',
            ];
        }

        $staff = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->whereIn('key', Role::STAFF))
            ->toBase()
            ->distinct()
            ->count('user_id');

        return [
            'in' => $in,
            'out' => $out,
            'staff' => $staff,
            'people' => array_slice($people, 0, 12),
        ];
    }

    /**
     * @return list<array{class:string,period:?string,subject:?string}>
     */
    private function uncoveredSlots(School $school): array
    {
        $today = (int) now(config('app.timezone'))->isoWeekday();

        return TimetableSlot::query()
            ->where('school_id', $school->id)
            ->where('day_of_week', $today)
            ->whereNull('teacher_id')
            ->with(['period', 'schoolClass', 'subject'])
            ->get()
            ->sortBy(fn (TimetableSlot $slot) => $slot->period?->sequence ?? $slot->period_id)
            ->map(fn (TimetableSlot $slot) => [
                'class' => $slot->schoolClass instanceof SchoolClass ? $slot->schoolClass->displayName() : 'Class',
                'period' => $slot->period?->name,
                'subject' => $slot->subject?->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{class:string,class_id:int,pct:?int,tone:string,marked:int}>
     */
    private function absenceHeatmap(School $school): array
    {
        $today = now(config('app.timezone'))->toDateString();
        $classes = SchoolClass::query()->where('school_id', $school->id)->orderBy('name')->get();
        $rows = AttendanceRecord::query()
            ->where('school_id', $school->id)
            ->whereDate('attended_on', $today)
            ->selectRaw('class_id, status, count(*) as total')
            ->groupBy('class_id', 'status')
            ->get()
            ->groupBy('class_id');

        $out = [];
        foreach ($classes as $class) {
            $byStatus = $rows->get($class->id, collect());
            $present = 0;
            $marked = 0;
            foreach ($byStatus as $row) {
                $n = (int) $row->total;
                $marked += $n;
                if (in_array($row->status, ['present', 'late'], true)) {
                    $present += $n;
                }
            }
            $pct = $marked > 0 ? (int) round(($present / $marked) * 100) : null;
            $tone = $pct === null ? 'muted' : ($pct >= 90 ? 'success' : ($pct >= 70 ? 'warning' : 'danger'));
            $out[] = [
                'class' => $class->displayName(),
                'class_id' => (int) $class->id,
                'pct' => $pct,
                'tone' => $tone,
                'marked' => $marked,
            ];
        }

        return $out;
    }

    /**
     * @param  list<string>  $permissions
     * @return list<array{type:string,priority:string,title:string,description:string,action_url:?string,owner:string}>
     */
    private function directorExceptions(School $school, array $permissions): array
    {
        $items = [];
        $zeroDays = $this->classesWithZeroAttendanceStreak($school);
        if ($zeroDays > 0) {
            $items[] = [
                'type' => 'attendance',
                'priority' => 'danger',
                'title' => $zeroDays === 1 ? '1 class at 0% attendance' : $zeroDays.' classes at 0% attendance',
                'description' => 'Three consecutive marked days with nobody present. Head Teacher follows up.',
                'action_url' => Route::has('app.attendance.index') ? route('app.attendance.index') : null,
                'owner' => 'Head Teacher',
            ];
        }

        if ($this->has($permissions, 'finance.view')) {
            $pending = FeePayment::query()->where('school_id', $school->id)->where('status', 'pending')->count();
            if ($pending >= 10) {
                $items[] = [
                    'type' => 'fees',
                    'priority' => 'warning',
                    'title' => $pending.' fee submissions still pending',
                    'description' => 'Unusually large bursar queue. View only — fee writes stay with the bursar.',
                    'action_url' => Route::has('app.fees.index') ? route('app.fees.index').'#payments' : null,
                    'owner' => 'Bursar',
                ];
            }
        }

        if ($this->has($permissions, 'assessment.view')) {
            $stuck = AssessmentMarksheet::query()
                ->where('school_id', $school->id)
                ->where('status', 'draft')
                ->whereHas('period', fn ($q) => $q->whereNotNull('entry_deadline')->whereDate('entry_deadline', '<', now()->toDateString()))
                ->count();
            if ($stuck > 0) {
                $items[] = [
                    'type' => 'marks',
                    'priority' => 'warning',
                    'title' => $stuck === 1 ? '1 marksheet still draft after deadline' : $stuck.' marksheets still draft after deadline',
                    'description' => 'Director of Studies follows the draft → submit → verify flow. You cannot enter marks.',
                    'action_url' => Route::has('app.assessment.reports') ? route('app.assessment.reports') : null,
                    'owner' => 'Director of Studies',
                ];
            }
        }

        return $items;
    }

    private function classesWithZeroAttendanceStreak(School $school): int
    {
        $from = now(config('app.timezone'))->subDays(14)->toDateString();
        $rows = AttendanceRecord::query()
            ->where('school_id', $school->id)
            ->whereDate('attended_on', '>=', $from)
            ->selectRaw('class_id, attended_on::date as day, sum(case when status in (\'present\',\'late\') then 1 else 0 end) as present, count(*) as marked')
            ->groupByRaw('class_id, attended_on::date')
            ->orderByDesc('day')
            ->get()
            ->groupBy('class_id');

        $hit = 0;
        foreach ($rows as $days) {
            $lastThree = $days->unique('day')->take(3);
            if ($lastThree->count() < 3) {
                continue;
            }
            $allZero = $lastThree->every(fn ($row) => (int) $row->present === 0 && (int) $row->marked > 0);
            if ($allZero) {
                $hit++;
            }
        }

        return $hit;
    }

    /** @return array<string, mixed> */
    private function occupancyPreview(School $school): array
    {
        $occ = $this->load->occupancy($school);
        $classes = $occ['classes']->take(8);
        $subjects = $occ['subjects']->take(8);
        $cells = [];
        foreach ($subjects as $subject) {
            $row = [];
            foreach ($classes as $class) {
                $entries = $occ['cells'][(int) $subject->id][(int) $class->id] ?? [];
                $row[] = [
                    'count' => count($entries),
                    'collision' => count(array_unique(array_column($entries, 'user_id'))) > 1,
                    'label' => $entries[0]['teacher'] ?? '',
                ];
            }
            $cells[] = [
                'subject' => $subject->name,
                'row' => $row,
            ];
        }

        return [
            'classes' => $classes->map(fn (SchoolClass $c) => $c->displayName())->values()->all(),
            'cells' => $cells,
            'collisions' => $occ['collisions'],
            'teachers' => count($occ['teacherCards']),
            'url' => Route::has('app.teaching.index') ? route('app.teaching.index') : null,
        ];
    }

    /**
     * Published grade-band distribution per subject. No pupil names.
     *
     * @return list<array{subject:string,bands:array<string,int>,total:int}>
     */
    private function gradeBands(School $school): array
    {
        $rows = Mark::query()
            ->where('school_id', $school->id)
            ->whereHas('period', fn ($q) => $q->whereIn('status', ['published', 'locked']))
            ->selectRaw('subject_id,
                sum(case when score < 40 then 1 else 0 end) as u,
                sum(case when score >= 40 and score < 50 then 1 else 0 end) as d,
                sum(case when score >= 50 and score < 60 then 1 else 0 end) as c,
                sum(case when score >= 60 and score < 70 then 1 else 0 end) as b,
                sum(case when score >= 70 then 1 else 0 end) as a,
                count(*) as total')
            ->groupBy('subject_id')
            ->limit(8)
            ->get();

        $names = Subject::query()
            ->where('school_id', $school->id)
            ->whereIn('id', $rows->pluck('subject_id')->all() ?: [0])
            ->pluck('name', 'id');

        return $rows->map(function ($row) use ($names) {
            $total = max(1, (int) $row->total);

            return [
                'subject' => $names[(int) $row->subject_id] ?? 'Subject',
                'total' => (int) $row->total,
                'bands' => [
                    'U' => (int) $row->u,
                    'D' => (int) $row->d,
                    'C' => (int) $row->c,
                    'B' => (int) $row->b,
                    'A' => (int) $row->a,
                ],
                'pct' => [
                    'U' => (int) round(((int) $row->u / $total) * 100),
                    'D' => (int) round(((int) $row->d / $total) * 100),
                    'C' => (int) round(((int) $row->c / $total) * 100),
                    'B' => (int) round(((int) $row->b / $total) * 100),
                    'A' => (int) round(((int) $row->a / $total) * 100),
                ],
            ];
        })->all();
    }

    /**
     * @return list<array{name:string,photo:?string,initial:string,subject:string,class:string}>
     */
    private function lateDraftTeachers(School $school, AssessmentPeriod $period): array
    {
        $sheets = AssessmentMarksheet::query()
            ->where('school_id', $school->id)
            ->where('assessment_period_id', $period->id)
            ->where('status', 'draft')
            ->with(['subject', 'schoolClass'])
            ->limit(12)
            ->get();

        if ($sheets->isEmpty()) {
            return [];
        }

        $assignments = TeachingAssignment::query()
            ->where('school_id', $school->id)
            ->forCurrentYear((int) $school->id)
            ->effective()
            ->whereIn('class_id', $sheets->pluck('class_id')->all())
            ->with('teacher')
            ->get();

        $out = [];
        foreach ($sheets as $sheet) {
            $match = $assignments->first(fn (TeachingAssignment $a) => (int) $a->class_id === (int) $sheet->class_id
                && (int) $a->subject_id === (int) $sheet->subject_id);
            $teacher = $match?->teacher;
            $out[] = [
                'name' => $teacher instanceof User ? $teacher->full_name : 'Unassigned',
                'photo' => $teacher instanceof User ? $teacher->avatarUrl() : null,
                'initial' => $teacher instanceof User ? $teacher->avatarInitial() : '?',
                'subject' => $sheet->subject?->name ?? 'Subject',
                'class' => $sheet->schoolClass instanceof SchoolClass ? $sheet->schoolClass->displayName() : 'Class',
            ];
        }

        return $out;
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
