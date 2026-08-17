<?php

namespace App\Services\Dashboard;

use App\Models\AdmissionApplication;
use App\Models\AttendanceRecord;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class SchoolDashboardService
{
    /**
     * @param  list<string>  $permissions
     * @return array{
     *   stats: list<array{label:string,value:string,hint:?string,tone:string}>,
     *   classChart: list<array{label:string,count:int,pct:float}>,
     *   feeChart: list<array{label:string,amount:float,pct:float}>,
     *   shortcuts: list<array{label:string,url:string,desc:string,icon:string}>,
     *   permissionLabels: list<string>
     * }
     */
    public function build(School $school, array $permissions, ?User $user = null): array
    {
        $schoolId = (int) $school->id;

        $students = Student::query()
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->count();

        $staff = RoleAssignment::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->toBase()
            ->distinct()
            ->count('user_id');

        $openFees = (float) FeeInvoice::query()
            ->where('school_id', $schoolId)
            ->whereIn('status', ['open', 'partial'])
            ->sum('balance');

        $demandedCount = FeeInvoice::query()
            ->where('school_id', $schoolId)
            ->whereIn('status', ['open', 'partial'])
            ->where('balance', '>', 0)
            ->count();

        $clearedCount = FeeInvoice::query()
            ->where('school_id', $schoolId)
            ->where(function ($q) {
                $q->where('status', 'paid')
                    ->orWhere(fn ($x) => $x->where('balance', '<=', 0)->where('status', '!=', 'void'));
            })
            ->count();

        $pendingPayments = FeePayment::query()
            ->where('school_id', $schoolId)
            ->where('status', 'pending')
            ->count();

        $pendingAdmissions = AdmissionApplication::query()
            ->where('school_id', $schoolId)
            ->where('status', 'pending')
            ->count();

        $todayAttendance = AttendanceRecord::query()
            ->where('school_id', $schoolId)
            ->whereDate('attended_on', now()->toDateString())
            ->count();

        $smsBalance = $school->smsBalance();

        $myLoad = 0;
        if ($user && $this->hasAny($permissions, ['assessment.enter', 'timetable.manage'])) {
            $myLoad = TeachingAssignment::query()
                ->where('school_id', $schoolId)
                ->where('user_id', $user->id)
                ->effective()
                ->count();
        }

        $stats = array_values(array_filter([
            $this->hasAny($permissions, ['learners.manage', 'learners.view'])
                ? ['label' => 'Active students', 'value' => number_format($students), 'hint' => 'On roll', 'tone' => 'brand']
                : null,
            $this->has($permissions, 'staff.manage')
                ? ['label' => 'Staff accounts', 'value' => number_format($staff), 'hint' => 'Active roles', 'tone' => 'brand']
                : null,
            $this->has($permissions, 'finance.manage')
                ? ['label' => 'Demanded', 'value' => number_format($demandedCount), 'hint' => 'Invoices with balance', 'tone' => $demandedCount > 0 ? 'warning' : 'brand']
                : null,
            $this->has($permissions, 'finance.manage')
                ? ['label' => 'Cleared', 'value' => number_format($clearedCount), 'hint' => 'Paid / zero balance', 'tone' => 'brand']
                : null,
            $this->hasAny($permissions, ['finance.manage', 'finance.view'])
                ? ['label' => 'Fees outstanding', 'value' => 'UGX '.number_format($openFees, 0), 'hint' => 'Open invoices', 'tone' => 'accent']
                : null,
            $this->hasAny($permissions, ['finance.manage', 'finance.view'])
                ? ['label' => 'Pending payments', 'value' => number_format($pendingPayments), 'hint' => 'Awaiting confirm', 'tone' => $pendingPayments > 0 ? 'warning' : 'brand']
                : null,
            $this->has($permissions, 'admissions.manage')
                ? ['label' => 'Admissions queue', 'value' => number_format($pendingAdmissions), 'hint' => 'Pending review', 'tone' => $pendingAdmissions > 0 ? 'warning' : 'brand']
                : null,
            $this->hasAny($permissions, ['attendance.mark', 'attendance.manage', 'attendance.view'])
                ? ['label' => 'Attendance today', 'value' => number_format($todayAttendance), 'hint' => 'Records marked', 'tone' => 'brand']
                : null,
            $this->has($permissions, 'sms.send') && $this->hasAny($permissions, ['finance.manage', 'school.manage', 'staff.manage'])
                ? ['label' => 'SMS credits', 'value' => number_format($smsBalance), 'hint' => 'Available balance', 'tone' => $smsBalance < 20 ? 'warning' : 'brand']
                : null,
            $myLoad > 0
                ? ['label' => 'My teaching load', 'value' => number_format($myLoad), 'hint' => 'Active assignments', 'tone' => 'brand']
                : null,
        ]));

        if ($stats === []) {
            $stats[] = ['label' => 'Welcome', 'value' => $school->name, 'hint' => 'Your school workspace', 'tone' => 'brand'];
        }

        return [
            'stats' => $stats,
            'classChart' => $this->hasAny($permissions, ['learners.manage', 'school.manage', 'finance.manage'])
                ? $this->classEnrollmentChart($schoolId)
                : [],
            'feeChart' => $this->has($permissions, 'finance.manage')
                ? $this->feeCollectionChart($schoolId)
                : [],
            'shortcuts' => $this->shortcuts($permissions, $school),
            'permissionLabels' => $this->permissionLabels($permissions),
        ];
    }

    /**
     * @return list<array{label:string,count:int,pct:float}>
     */
    private function classEnrollmentChart(int $schoolId): array
    {
        $classes = SchoolClass::query()
            ->where('school_id', $schoolId)
            ->withCount(['students as active_students_count' => function ($q) {
                $q->where('status', 'active');
            }])
            ->orderByDesc('active_students_count')
            ->limit(8)
            ->get();

        $max = max(1, (int) $classes->max('active_students_count'));

        return $classes->map(function ($class) use ($max) {
            $count = (int) $class->active_students_count;

            return [
                'label' => (string) $class->name,
                'count' => $count,
                'pct' => round(($count / $max) * 100, 1),
            ];
        })->all();
    }

    /**
     * @return list<array{label:string,amount:float,pct:float}>
     */
    private function feeCollectionChart(int $schoolId): array
    {
        $rows = [];
        $amounts = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();
            $amount = (float) FeePayment::query()
                ->where('school_id', $schoolId)
                ->where('status', 'confirmed')
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount');
            $rows[] = [
                'label' => $month->format('M'),
                'amount' => $amount,
            ];
            $amounts[] = $amount;
        }

        $max = max(1.0, ...$amounts);

        return array_map(static function (array $row) use ($max) {
            $row['pct'] = round(($row['amount'] / $max) * 100, 1);

            return $row;
        }, $rows);
    }

    /**
     * @param  list<string>  $permissions
     * @return list<array{label:string,url:string,desc:string,icon:string}>
     */
    private function shortcuts(array $permissions, School $school): array
    {
        $catalog = [
            ['perm' => 'learners.manage', 'route' => 'app.students.index', 'label' => 'Students', 'desc' => 'Records & guardians', 'icon' => 'students'],
            ['perm' => 'learners.view', 'route' => 'app.students.index', 'label' => 'Students', 'desc' => 'Learner profiles', 'icon' => 'students'],
            ['perm' => 'learners.manage', 'route' => 'app.enrollments.index', 'label' => 'Enrollments', 'desc' => 'Class placement', 'icon' => 'enrollments'],
            ['perm' => 'admissions.manage', 'route' => 'app.admissions.index', 'label' => 'Admissions', 'desc' => 'Applications queue', 'icon' => 'admissions'],
            ['perm' => 'finance.manage', 'route' => 'app.fees.index', 'params' => ['status' => 'demanded'], 'label' => 'Demanded fees', 'desc' => 'Students still owing', 'icon' => 'fees'],
            ['perm' => 'finance.manage', 'route' => 'app.fees.index', 'params' => ['status' => 'cleared'], 'label' => 'Cleared fees', 'desc' => 'Paid in full', 'icon' => 'fees'],
            ['perm' => 'finance.manage', 'route' => 'app.fees.index', 'label' => 'Fees desk', 'desc' => 'Invoices & payments', 'icon' => 'fees'],
            ['perm' => 'finance.view', 'route' => 'app.fees.index', 'label' => 'Fees', 'desc' => 'Fee statements', 'icon' => 'fees'],
            ['perm' => 'attendance.mark', 'route' => 'app.attendance.index', 'label' => 'Attendance', 'desc' => 'Daily register', 'icon' => 'attendance'],
            ['perm' => 'attendance.manage', 'route' => 'app.attendance.index', 'label' => 'Attendance', 'desc' => 'Daily register', 'icon' => 'attendance'],
            ['perm' => 'assessment.enter', 'route' => 'app.assessment.marks', 'label' => 'Enter marks', 'desc' => 'Assessment entry', 'icon' => 'assessment'],
            ['perm' => 'assessment.manage', 'route' => 'app.assessment.index', 'label' => 'Assessment', 'desc' => 'Periods & reports', 'icon' => 'assessment'],
            ['perm' => 'timetable.manage', 'route' => 'app.timetable.index', 'label' => 'Timetable', 'desc' => 'Days, periods, generate', 'icon' => 'timetable'],
            ['perm' => 'timetable.manage', 'route' => 'app.teaching.index', 'label' => 'Teaching load', 'desc' => 'Who teaches what', 'icon' => 'teaching'],
            ['perm' => 'staff.manage', 'route' => 'app.staff.index', 'label' => 'Staff', 'desc' => 'Roles & invites', 'icon' => 'staff'],
            ['perm' => 'sms.send', 'route' => 'app.sms', 'label' => 'Send SMS', 'desc' => 'Parent messages', 'icon' => 'sms'],
            ['perm' => 'announcements.manage', 'route' => 'app.announcements.index', 'label' => 'Announcements', 'desc' => 'School notices', 'icon' => 'announcements'],
            ['perm' => 'school.manage', 'route' => 'app.settings.school', 'label' => 'School identity', 'desc' => 'Theme & features', 'icon' => 'platform'],
            ['perm' => 'school.manage', 'route' => 'app.years.index', 'label' => 'Academic years', 'desc' => 'Terms & calendar', 'icon' => 'years'],
            ['perm' => 'curriculum.manage', 'route' => 'app.years.index', 'label' => 'Academic years', 'desc' => 'Terms & calendar', 'icon' => 'years'],
            ['perm' => 'lms.manage', 'route' => 'app.lms.index', 'label' => 'LMS', 'desc' => 'Materials & tasks', 'icon' => 'lms'],
            ['perm' => 'cbt.manage', 'route' => 'app.cbt.index', 'label' => 'CBT', 'desc' => 'Online exams', 'icon' => 'cbt'],
            ['perm' => 'library.manage', 'route' => 'app.library.index', 'label' => 'Library', 'desc' => 'Books & loans', 'icon' => 'library'],
            ['perm' => 'helpdesk.create', 'route' => 'app.helpdesk.index', 'label' => 'Helpdesk', 'desc' => 'Support tickets', 'icon' => 'helpdesk'],
        ];

        if ($school->emisEnabled()) {
            $catalog[] = ['perm' => 'emis.manage', 'route' => 'app.emis.export', 'label' => 'EMIS export', 'desc' => 'MoES student CSV', 'icon' => 'emis'];
        }

        $out = [];
        $seen = [];
        foreach ($catalog as $item) {
            if (! $this->has($permissions, $item['perm'])) {
                continue;
            }
            if (! Route::has($item['route'])) {
                continue;
            }
            $key = $item['route'].'|'.json_encode($item['params'] ?? []);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = [
                'label' => $item['label'],
                'url' => route($item['route'], $item['params'] ?? []),
                'desc' => $item['desc'],
                'icon' => $item['icon'],
            ];
        }

        return $out;
    }

    /**
     * @param  list<string>  $permissions
     * @return list<string>
     */
    private function permissionLabels(array $permissions): array
    {
        $map = [
            'school.manage' => 'School setup',
            'curriculum.manage' => 'Curriculum',
            'staff.manage' => 'Staff',
            'learners.manage' => 'Learners',
            'learners.view' => 'Learners (view)',
            'finance.manage' => 'Finance',
            'finance.view' => 'Finance (view)',
            'attendance.mark' => 'Attendance',
            'attendance.manage' => 'Attendance (school-wide)',
            'assessment.enter' => 'Enter marks',
            'assessment.manage' => 'Assessment',
            'assessment.view' => 'Assessment (view)',
            'timetable.manage' => 'Timetable',
            'admissions.manage' => 'Admissions',
            'announcements.manage' => 'Announcements',
            'sms.send' => 'Send SMS',
            'sms.manage' => 'SMS settings',
            'lms.manage' => 'LMS',
            'cbt.manage' => 'CBT',
            'library.manage' => 'Library',
            'inventory.manage' => 'Inventory',
            'transport.manage' => 'Transport',
            'hostel.manage' => 'Hostel',
            'hr.manage' => 'HR',
            'hr.view' => 'HR (view)',
            'clinic.manage' => 'Clinic',
            'helpdesk.manage' => 'Helpdesk',
            'helpdesk.create' => 'Helpdesk tickets',
            'emis.manage' => 'EMIS',
            'promotions.approve' => 'Promotions',
            'accounts.manage' => 'Accounts',
            'reports.view' => 'Reports',
        ];

        $labels = [];
        foreach ($permissions as $perm) {
            $labels[] = $map[$perm] ?? str_replace(['.', '_'], [' · ', ' '], $perm);
        }
        sort($labels);

        return $labels;
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
