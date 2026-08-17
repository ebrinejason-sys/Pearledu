<?php

namespace App\Services\Dashboard;

use App\Models\AdmissionApplication;
use App\Models\AssessmentPeriod;
use App\Models\AttendanceRecord;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\User;
use App\Services\Schools\SchoolModuleRegistry;
use Illuminate\Support\Facades\Route;

class ActionCenterService
{
    public function __construct(private SchoolModuleRegistry $modules) {}

    /**
     * @param  list<string>  $permissions
     * @return list<array{type:string,priority:string,title:string,description:string,action_url:?string}>
     */
    public function items(School $school, User $user, array $permissions): array
    {
        $items = [];
        $schoolId = (int) $school->id;

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

        if ($this->can($permissions, 'finance.manage') && $this->modules->enabled($school, 'fees')) {
            $pendingPay = FeePayment::query()->where('school_id', $schoolId)->where('status', 'pending')->count();
            if ($pendingPay > 0) {
                $items[] = $this->item(
                    'payments',
                    'high',
                    $pendingPay === 1 ? '1 payment needs verification' : "{$pendingPay} payments need verification",
                    'Confirm or reject pending receipts.',
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
                    Route::has('app.fees.index') ? route('app.fees.index') : null,
                );
            }
        }

        if ($this->canAny($permissions, ['assessment.manage', 'assessment.enter']) && $this->modules->enabled($school, 'assessment')) {
            $openPeriods = AssessmentPeriod::query()
                ->where('school_id', $schoolId)
                ->whereIn('status', ['draft', 'mark_entry_open'])
                ->count();
            if ($openPeriods > 0 && $this->can($permissions, 'assessment.enter')) {
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
                    'high',
                    $unpublished === 1 ? '1 period is ready to publish' : "{$unpublished} periods are ready to publish",
                    'Parents only see results after you publish.',
                    Route::has('app.assessment.index') ? route('app.assessment.index') : null,
                );
            }
        }

        if ($this->canAny($permissions, ['attendance.mark', 'attendance.manage']) && $this->modules->enabled($school, 'attendance')) {
            $classes = SchoolClass::query()->where('school_id', $schoolId)->count();
            $marked = AttendanceRecord::query()
                ->where('school_id', $schoolId)
                ->whereDate('attended_on', now()->toDateString())
                ->distinct()
                ->count('class_id');
            $missing = max(0, $classes - $marked);
            if ($missing > 0 && $classes > 0) {
                $items[] = $this->item(
                    'attendance',
                    'medium',
                    $missing === 1 ? '1 class has no attendance today' : "{$missing} classes have no attendance today",
                    'Mark the register so absences can notify parents.',
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
