<?php

namespace App\Services\Dashboard;

use App\Models\AdmissionApplication;
use App\Models\AssessmentMarksheet;
use App\Models\AssessmentPeriod;
use App\Models\AttendanceRecord;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\HelpdeskTicket;
use App\Models\PromotionBatch;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\User;
use App\Services\Authorization\AssignedClassResolver;
use App\Services\Schools\SchoolModuleRegistry;
use App\Services\Schools\SchoolSetupService;
use Illuminate\Support\Facades\Route;

class ActionCenterService
{
    public function __construct(
        private SchoolModuleRegistry $modules,
        private SchoolSetupService $setup,
        private AssignedClassResolver $assigned,
    ) {}

    /**
     * @param  list<string>  $permissions
     * @return list<array{type:string,priority:string,title:string,description:string,action_url:?string}>
     */
    public function items(School $school, User $user, array $permissions): array
    {
        $items = [];
        $schoolId = (int) $school->id;
        $roleKeys = $user->activeAssignments()
            ->where('school_id', $schoolId)
            ->with('role')
            ->get()
            ->pluck('role.key')
            ->filter()
            ->unique()
            ->all();
        $isAdminOnlyOps = in_array(Role::SCHOOL_ADMIN, $roleKeys, true)
            && ! in_array(Role::BURSAR, $roleKeys, true)
            && ! in_array(Role::SUBJECT_TEACHER, $roleKeys, true)
            && ! in_array(Role::DIRECTOR_OF_STUDIES, $roleKeys, true);
        $isDirector = in_array(Role::DIRECTOR, $roleKeys, true)
            && ! in_array(Role::SCHOOL_ADMIN, $roleKeys, true);

        if ($this->can($permissions, 'admissions.manage') && $this->modules->enabled($school, 'admissions')) {
            $pending = AdmissionApplication::query()
                ->where('school_id', $schoolId)
                ->where('status', 'pending')
                ->count();
            if ($pending > 0) {
                $items[] = $this->item(
                    'admissions',
                    'high',
                    $pending === 1 ? '1 admission application waiting' : "{$pending} admission applications waiting",
                    'Review and admit learners in one step.',
                    Route::has('app.admissions.index') ? route('app.admissions.index') : null,
                );
            }
        }

        if (in_array(Role::SCHOOL_ADMIN, $roleKeys, true) && $this->can($permissions, 'school.manage')) {
            foreach ($this->setup->hygiene($school)['tiles'] as $tile) {
                if ((int) $tile['count'] <= 0) {
                    continue;
                }
                $items[] = $this->item(
                    'hygiene_'.$tile['key'],
                    $tile['tone'] === 'danger' ? 'high' : 'medium',
                    $tile['count'].' · '.$tile['label'],
                    $tile['hint'],
                    $tile['url'],
                );
            }
        }

        if ($isDirector) {
            foreach ($this->directorExceptionItems($school, $permissions) as $item) {
                $items[] = $item;
            }
        }

        if ($this->can($permissions, 'promotions.approve')) {
            $pendingPromo = PromotionBatch::query()
                ->where('school_id', $schoolId)
                ->whereNull('committed_at')
                ->where('status', '!=', 'committed')
                ->count();
            if ($pendingPromo > 0) {
                $items[] = $this->item(
                    'promotions',
                    'high',
                    $pendingPromo === 1 ? '1 promotion batch waiting to commit' : "{$pendingPromo} promotion batches waiting to commit",
                    'Head Teacher commits. Director of Studies prepares the batch.',
                    Route::has('app.promotions.index') ? route('app.promotions.index') : null,
                );
            }
        }

        if ($this->can($permissions, 'helpdesk.manage')) {
            $openTickets = HelpdeskTicket::query()
                ->where('school_id', $schoolId)
                ->where('status', '!=', 'closed')
                ->count();
            if ($openTickets > 0 && ! $isDirector) {
                $items[] = $this->item(
                    'helpdesk',
                    'medium',
                    $openTickets === 1 ? '1 open helpdesk ticket' : "{$openTickets} open helpdesk tickets",
                    'Escalations from class teachers and parents.',
                    Route::has('app.helpdesk.index') ? route('app.helpdesk.index') : null,
                );
            }
        }

        if ($this->can($permissions, 'finance.manage') && $this->modules->enabled($school, 'fees')) {
            $pendingPay = FeePayment::query()->where('school_id', $schoolId)->where('status', 'pending')->count();
            if ($pendingPay > 0) {
                $items[] = $this->item(
                    'payments',
                    $isAdminOnlyOps ? 'medium' : 'high',
                    $isAdminOnlyOps
                        ? ($pendingPay === 1 ? 'Bursar queue: 1 pending submission' : "Bursar queue: {$pendingPay} pending submissions")
                        : ($pendingPay === 1 ? '1 payment needs verification' : "{$pendingPay} payments need verification"),
                    $isAdminOnlyOps
                        ? 'Integrity alert — the bursar confirms or rejects. You remain break-glass.'
                        : 'Confirm or reject pending receipts.',
                    Route::has('app.fees.index') ? route('app.fees.index').'#payments' : null,
                );
            }

            $outstanding = (float) FeeInvoice::query()
                ->where('school_id', $schoolId)
                ->whereIn('status', ['open', 'partial'])
                ->sum('balance');
            $overdue = FeeInvoice::query()
                ->where('school_id', $schoolId)
                ->whereIn('status', ['open', 'partial'])
                ->whereNotNull('due_on')
                ->whereDate('due_on', '<', now()->toDateString())
                ->count();
            if ($outstanding > 0) {
                $items[] = $this->item(
                    'fees',
                    $overdue > 0 ? 'high' : 'medium',
                    'UGX '.number_format($outstanding, 0).' outstanding',
                    $overdue > 0 ? "{$overdue} invoices are overdue." : 'Open learner balances.',
                    Route::has('app.fees.index') ? route('app.fees.index', ['status' => 'demanded']) : null,
                );
            }

            $cleared = FeeInvoice::query()
                ->where('school_id', $schoolId)
                ->where(function ($q) {
                    $q->where('status', 'paid')
                        ->orWhere(fn ($x) => $x->where('balance', '<=', 0)->where('status', '!=', 'void'));
                })
                ->count();
            if ($cleared > 0 && ! $isAdminOnlyOps) {
                $items[] = $this->item(
                    'fees_cleared',
                    'low',
                    $cleared === 1 ? '1 learner has cleared fees' : "{$cleared} invoices cleared",
                    'Quick view of paid / zero-balance invoices.',
                    Route::has('app.fees.index') ? route('app.fees.index', ['status' => 'cleared']) : null,
                );
            }
        }

        if ($this->canAny($permissions, ['assessment.manage', 'assessment.enter']) && $this->modules->enabled($school, 'assessment')) {
            $openPeriods = AssessmentPeriod::query()
                ->where('school_id', $schoolId)
                ->whereIn('status', ['draft', 'mark_entry_open'])
                ->count();
            if ($openPeriods > 0 && $this->can($permissions, 'assessment.enter') && ! $isAdminOnlyOps) {
                $items[] = $this->item(
                    'marks',
                    'medium',
                    $openPeriods === 1 ? 'Mark entry is open' : "{$openPeriods} assessment periods need marks",
                    'Enter scores — grades are calculated for you.',
                    Route::has('app.assessment.marks') ? route('app.assessment.marks') : null,
                );
            }

            $unpublished = AssessmentPeriod::query()
                ->where('school_id', $schoolId)
                ->whereIn('status', ['mark_entry_closed', 'review'])
                ->count();
            if ($unpublished > 0 && $this->can($permissions, 'assessment.manage')) {
                $items[] = $this->item(
                    'publish',
                    $isAdminOnlyOps ? 'low' : 'high',
                    $unpublished === 1 ? '1 period is ready to publish' : "{$unpublished} periods are ready to publish",
                    $isAdminOnlyOps
                        ? 'DOS publishes. School admin remains academic break-glass.'
                        : 'Parents only see results after you publish.',
                    Route::has('app.assessment.index') ? route('app.assessment.index') : null,
                );
            }
        }

        if ($this->canAny($permissions, ['attendance.mark', 'attendance.manage']) && $this->modules->enabled($school, 'attendance') && ! $isDirector) {
            $allowed = $this->assigned->isSchoolWide($user, $schoolId)
                || $this->can($permissions, 'attendance.manage')
                ? null
                : $this->assigned->assignedClassIds($user, $schoolId);
            $classes = SchoolClass::query()
                ->where('school_id', $schoolId)
                ->when(is_array($allowed), fn ($q) => $q->whereIn('id', $allowed ?: [0]))
                ->count();
            $marked = AttendanceRecord::query()
                ->where('school_id', $schoolId)
                ->whereDate('attended_on', now()->toDateString())
                ->when(is_array($allowed), fn ($q) => $q->whereIn('class_id', $allowed ?: [0]))
                ->distinct()
                ->count('class_id');
            $missing = max(0, $classes - $marked);
            if ($missing > 0 && $classes > 0) {
                $items[] = $this->item(
                    'attendance',
                    'medium',
                    $missing === 1 ? '1 class has no attendance today' : "{$missing} classes have no attendance today",
                    $this->can($permissions, 'attendance.manage')
                        ? 'Deputy / class teachers mark the register.'
                        : 'Take today’s register for your assigned class.',
                    Route::has('app.attendance.index') ? route('app.attendance.index') : null,
                );
            }
        }

        $term = Term::query()
            ->where('school_id', $schoolId)
            ->whereDate('starts_on', '<=', now()->toDateString())
            ->whereDate('ends_on', '>=', now()->toDateString())
            ->orderBy('sequence')
            ->first();
        if ($term?->ends_on) {
            $days = now()->startOfDay()->diffInDays($term->ends_on->startOfDay(), false);
            if ($days >= 0 && $days <= 14) {
                $items[] = $this->item(
                    'term',
                    $days <= 3 ? 'high' : 'low',
                    $term->name.' ends in '.($days === 0 ? 'today' : $days.' day'.($days === 1 ? '' : 's')),
                    'Finish marks, attendance, and fee follow-up before close of term.',
                    Route::has('app.assessment.index') ? route('app.assessment.index') : null,
                );
            }
        }

        usort($items, fn ($a, $b) => $this->rank($a['priority']) <=> $this->rank($b['priority']));

        return $items;
    }

    /**
     * @param  list<string>  $permissions
     * @return list<array{type:string,priority:string,title:string,description:string,action_url:?string}>
     */
    private function directorExceptionItems(School $school, array $permissions): array
    {
        $items = [];
        $schoolId = (int) $school->id;

        $pending = FeePayment::query()->where('school_id', $schoolId)->where('status', 'pending')->count();
        if ($this->can($permissions, 'finance.view') && $pending >= 10) {
            $items[] = $this->item(
                'exception_fees',
                'medium',
                $pending.' fee submissions still pending',
                'Bursar confirms. You have finance view only.',
                Route::has('app.fees.index') ? route('app.fees.index') : null,
            );
        }

        $stuck = AssessmentMarksheet::query()
            ->where('school_id', $schoolId)
            ->where('status', 'draft')
            ->whereHas('period', fn ($q) => $q->whereNotNull('entry_deadline')->whereDate('entry_deadline', '<', now()->toDateString()))
            ->count();
        if ($this->can($permissions, 'assessment.view') && $stuck > 0) {
            $items[] = $this->item(
                'exception_marks',
                'medium',
                $stuck === 1 ? '1 marksheet stuck in draft after deadline' : "{$stuck} marksheets stuck in draft after deadline",
                'Director of Studies owns the marksheet workflow.',
                Route::has('app.assessment.reports') ? route('app.assessment.reports') : null,
            );
        }

        return $items;
    }

    /** @param list<string> $permissions */
    private function can(array $permissions, string $perm): bool
    {
        return in_array($perm, $permissions, true);
    }

    /** @param list<string> $permissions @param list<string> $perms */
    private function canAny(array $permissions, array $perms): bool
    {
        foreach ($perms as $perm) {
            if ($this->can($permissions, $perm)) {
                return true;
            }
        }

        return false;
    }

    private function rank(string $priority): int
    {
        return ['high' => 0, 'medium' => 1, 'low' => 2][$priority] ?? 3;
    }

    /** @return array{type:string,priority:string,title:string,description:string,action_url:?string} */
    private function item(string $type, string $priority, string $title, string $description, ?string $url): array
    {
        return compact('type', 'priority', 'title', 'description') + ['action_url' => $url];
    }
}
