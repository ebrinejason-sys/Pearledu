<?php

namespace App\Services\Navigation;

use App\Models\School;
use App\Models\User;
use App\Services\Academics\CurrentAcademicContext;
use App\Services\Platform\ImpersonationService;
use App\Services\Platform\PlatformStaffService;
use App\Services\Schools\SchoolModuleRegistry;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\Route;

class NavigationBuilder
{
    public function __construct(
        private TenantContext $context,
        private ImpersonationService $impersonation,
        private SchoolModuleRegistry $modules,
        private CurrentAcademicContext $academic,
    ) {}

    /**
     * @return array{
     *   zone: string,
     *   sections: list<array{key: string, label: string, items: list<array>}>,
     *   account: array|null,
     *   user: array, impersonation: array|null, school: array|null
     * }
     */
    public function build(?User $user): array
    {
        if (! $user) {
            return ['zone' => 'guest', 'sections' => [], 'account' => null, 'user' => [], 'impersonation' => null, 'school' => null];
        }

        $school = $this->context->school();
        $schoolId = $this->context->schoolId() ?? $this->impersonation->schoolId();
        $onPlatform = request()->routeIs('platform.*');
        $isPlatformOperator = $user->isPlatformOperator() && ! $this->impersonation->isActive();

        // Console keeps platform RLS; still surface the entered school in chrome/nav.
        if (! $school && $isPlatformOperator && $onPlatform) {
            $enteredId = session('platform.entered_school_id');
            if ($enteredId) {
                $school = School::query()->find($enteredId);
                $schoolId = $school?->id;
            }
        }

        $permissions = $schoolId ? $user->permissionsForSchool($schoolId) : [];

        $roleLabels = $schoolId
            ? $user->activeAssignments()->where('school_id', $schoolId)->with('role')->get()->pluck('role.label')->unique()->values()->all()
            : [];

        if ($isPlatformOperator && $roleLabels === []) {
            $platformRole = $user->platformRoleKey();
            $roleLabels = $platformRole
                ? [PlatformStaffService::roleLabels()[$platformRole] ?? $platformRole]
                : ['Misconfigured account'];
        }

        $impersonation = null;
        if ($this->impersonation->isActive()) {
            $operator = $this->impersonation->operator();
            $impersonation = [
                'operator_name' => $operator?->full_name ?? 'Platform admin',
                'target_name' => $user->full_name,
                'school_name' => $school?->name,
                'reason' => $this->impersonation->reason(),
                'read_only' => ! $this->impersonation->allowsWrites(),
            ];
        }

        if ($isPlatformOperator && $onPlatform) {
            $sections = $this->platformSections($user);
            $zone = 'platform';
        } else {
            $sections = $this->schoolSections($permissions, $isPlatformOperator, $school);
            $zone = 'school';
        }

        $sections = $this->withoutDeadLinks($sections);

        return [
            'zone' => $zone,
            'sections' => array_values(array_filter($sections, fn ($s) => count($s['items']) > 0)),
            'account' => $this->item('Account settings', 'account.settings', icon: 'account'),
            'user' => [
                'name' => $user->full_name,
                'email' => $user->email ?? $user->phone,
                'initial' => strtoupper(substr($user->full_name, 0, 1)),
                'avatar_url' => $user->avatarUrl(),
                'roles' => $roleLabels,
                'is_platform' => $isPlatformOperator,
            ],
            'impersonation' => $impersonation,
            'school' => $school ? [
                'id' => $school->id,
                'name' => $school->name,
                'slug' => $school->slug,
                'term_label' => $this->termLabel($zone === 'school'),
                'year_label' => $this->yearLabel($zone === 'school'),
            ] : null,
        ];
    }

    /** @return list<array{key: string, label: string, items: list<array>}> */
    private function schoolSections(array $permissions, bool $isPlatformOperator, ?School $school = null): array
    {
        $canAssess = $this->hasAny($permissions, ['assessment.enter', 'assessment.manage', 'assessment.view']);
        $on = fn (string $module) => $school ? $this->modules->enabled($school, $module) : true;
        $portalHome = $this->hasAny($permissions, [
            'child.results.view', 'self.results.view', 'child.fees.view', 'self.fees.view',
            'child.attendance.view', 'self.attendance.view',
            'fees.pay', 'self.timetable.view', 'announcements.view',
        ]);

        return [
            [
                'key' => 'home',
                'label' => 'Home',
                'items' => array_values(array_filter([
                    $this->item('Dashboard', 'app.home', icon: 'home'),
                    $portalHome ? $this->item('My portal', 'app.portal.home', icon: 'home', active: request()->routeIs('app.portal.*')) : null,
                ])),
            ],
            [
                'key' => 'school_data',
                'label' => 'Manage school data',
                'items' => array_values(array_filter([
                    $this->has($permissions, 'school.manage')
                        ? $this->item('My institution', 'app.settings.school', icon: 'platform', active: request()->routeIs('app.settings.*'))
                        : null,
                    $this->nest('Learners', 'students', [
                        $on('learners') && $this->hasAny($permissions, ['learners.manage', 'learners.view'])
                            ? $this->item('View Learners', 'app.students.index', icon: 'students', active: request()->routeIs('app.students.*'))
                            : null,
                        $on('admissions') && $this->has($permissions, 'admissions.manage')
                            ? $this->item('Admissions', 'app.admissions.index', icon: 'admissions', active: request()->routeIs('app.admissions.*'))
                            : null,
                        $this->hasAny($permissions, ['learners.manage', 'enrollment.manage'])
                            ? $this->item('Enrollments', 'app.enrollments.index', icon: 'enrollments', active: request()->routeIs('app.enrollments.*'))
                            : null,
                        $this->has($permissions, 'promotions.approve')
                            ? $this->item('Promotions', 'app.promotions.index', icon: 'promotions', active: request()->routeIs('app.promotions.*'))
                            : null,
                    ]),
                    $this->nest('Human Resource', 'staff', [
                        $this->hasAny($permissions, ['staff.manage', 'staff.invite.teacher', 'staff.view'])
                            ? $this->item('Staff', 'app.staff.index', icon: 'staff', active: request()->routeIs('app.staff.index') || request()->routeIs('app.staff.show') || request()->routeIs('app.staff.id'))
                            : null,
                        $this->hasAny($permissions, ['staff.attendance.view', 'staff.attendance.mark'])
                            ? $this->item('Staff clock', 'app.staff.clock', icon: 'attendance', active: request()->routeIs('app.staff.clock*'))
                            : null,
                        $this->hasAny($permissions, ['hr.payroll.view', 'hr.payroll.manage'])
                            ? $this->item('Salaries', 'app.staff.payroll', icon: 'hr', active: request()->routeIs('app.staff.payroll*'))
                            : null,
                    ]),
                    $this->nest('Finance', 'fees', [
                        $on('fees') && $this->hasAny($permissions, ['finance.manage', 'finance.view'])
                            ? $this->item('Fee types', 'app.fees.index', icon: 'fees', active: request()->routeIs('app.fees.index') || request()->routeIs('app.fees.structures*'))
                            : null,
                        $on('fees') && $this->hasAny($permissions, ['finance.manage', 'finance.view'])
                            ? $this->item('Invoices', 'app.fees.invoices', icon: 'fees', active: request()->routeIs('app.fees.invoices') || request()->routeIs('app.fees.overdue'))
                            : null,
                        $on('fees') && $this->hasAny($permissions, ['finance.manage', 'finance.view'])
                            ? $this->item('Cleared', 'app.fees.cleared', icon: 'fees', active: request()->routeIs('app.fees.cleared'))
                            : null,
                        $on('fees') && $this->hasAny($permissions, ['finance.reconcile', 'finance.manage'])
                            ? $this->item('Reconciliation', 'app.fees.invoices', icon: 'fees', active: request()->routeIs('app.fees.invoices') && request()->query('status') === 'demanded')
                            : null,
                    ]),
                    $this->has($permissions, 'school.manage')
                        ? $this->item('Setup wizard', 'app.setup.index', icon: 'add', active: request()->routeIs('app.setup.*'))
                        : null,
                    $this->hasAny($permissions, ['school.manage', 'curriculum.manage'])
                        ? $this->item('Classes & streams', 'app.classes.index', icon: 'classes', active: request()->routeIs('app.classes.*') && ! request()->routeIs('app.classes.overview'))
                        : null,
                    $this->hasAny($permissions, ['school.manage', 'curriculum.manage'])
                        ? $this->item('Subjects', 'app.subjects.index', icon: 'subjects', active: request()->routeIs('app.subjects.*'))
                        : null,
                    $this->hasAny($permissions, ['school.manage', 'curriculum.manage'])
                        ? $this->item('Academic years', 'app.years.index', icon: 'years', active: request()->routeIs('app.years.*'))
                        : null,
                    $this->hasAny($permissions, ['school.manage', 'curriculum.manage', 'timetable.manage'])
                        ? $this->item('Teaching assignments', 'app.teaching.index', icon: 'teaching', active: request()->routeIs('app.teaching.index') || request()->routeIs('app.teaching.store') || request()->routeIs('app.teaching.destroy'))
                        : null,
                ])),
            ],
            [
                'key' => 'academics',
                'label' => 'Academics',
                'items' => array_values(array_filter([
                    $this->hasAny($permissions, ['assessment.enter', 'lms.manage'])
                        ? $this->item('My classes', 'app.teaching.mine', icon: 'teaching', active: request()->routeIs('app.teaching.mine'))
                        : null,
                    $this->has($permissions, 'class.view')
                        ? $this->item('My Class', 'app.teaching.homeroom', icon: 'classes', active: request()->routeIs('app.teaching.homeroom'))
                        : null,
                    $on('attendance') && $this->hasAny($permissions, ['attendance.mark', 'attendance.manage', 'attendance.view'])
                        ? $this->item('Attendance', 'app.attendance.index', icon: 'attendance', active: request()->routeIs('app.attendance.*'))
                        : null,
                    $on('assessment') && $this->has($permissions, 'assessment.manage')
                        ? $this->item('Assessment', 'app.assessment.marks', icon: 'assessment', active: request()->routeIs('app.assessment.marks*') || request()->routeIs('app.assessment.marksheets.*'))
                        : null,
                    $on('timetable') && $this->has($permissions, 'timetable.manage')
                        ? $this->item('Timetable', 'app.timetable.index', icon: 'timetable', active: request()->routeIs('app.timetable.*'))
                        : null,
                    $on('assessment') && $this->has($permissions, 'assessment.manage')
                        ? $this->item('Assessment periods', 'app.assessment.index', icon: 'assessment', active: request()->routeIs('app.assessment.index') || request()->routeIs('app.assessment.periods.*'))
                        : null,
                ])),
            ],
            [
                'key' => 'communications',
                'label' => 'Communication',
                'items' => array_values(array_filter([
                    $on('announcements') && $this->has($permissions, 'announcements.manage')
                        ? $this->item('Announcements', 'app.announcements.index', icon: 'announcements', active: request()->routeIs('app.announcements.*'))
                        : null,
                    $this->has($permissions, 'staff.messages')
                        ? $this->item('Staff messages', 'app.staff.messages.index', icon: 'announcements', active: request()->routeIs('app.staff.messages.*'))
                        : null,
                    $on('sms') && $this->has($permissions, 'sms.send')
                        ? $this->item('SMS', 'app.sms', icon: 'sms')
                        : null,
                    $this->has($permissions, 'announcements.view') && ! $this->has($permissions, 'announcements.manage')
                        ? $this->item('Announcements', 'app.portal.announcements', icon: 'announcements', active: request()->routeIs('app.portal.announcements'))
                        : null,
                ])),
            ],
            [
                'key' => 'services',
                'label' => 'Services',
                'items' => array_values(array_filter([
                    $on('lms') && $this->hasAny($permissions, ['lms.manage', 'lms.view'])
                        ? $this->item('LMS', 'app.lms.index', icon: 'lms', active: request()->routeIs('app.lms.*'))
                        : null,
                    $on('cbt') && $this->has($permissions, 'cbt.manage')
                        ? $this->item('CBT', 'app.cbt.index', icon: 'cbt', active: request()->routeIs('app.cbt.*'))
                        : null,
                    $on('cbt') && $this->has($permissions, 'cbt.take') && ! $this->has($permissions, 'cbt.manage')
                        ? $this->item('My exams', 'app.cbt.index', icon: 'cbt', active: request()->routeIs('app.cbt.*'))
                        : null,
                    $on('library') && $this->has($permissions, 'library.manage')
                        ? $this->item('Library', 'app.library.index', icon: 'library', active: request()->routeIs('app.library.*'))
                        : null,
                    $on('inventory') && $this->has($permissions, 'inventory.manage')
                        ? $this->item('Inventory', 'app.inventory.index', icon: 'inventory', active: request()->routeIs('app.inventory.*'))
                        : null,
                    $on('transport') && $this->has($permissions, 'transport.manage')
                        ? $this->item('Transport', 'app.transport.index', icon: 'transport', active: request()->routeIs('app.transport.*'))
                        : null,
                    $on('hostel') && $this->has($permissions, 'hostel.manage')
                        ? $this->item('Hostel', 'app.hostel.index', icon: 'hostel', active: request()->routeIs('app.hostel.*'))
                        : null,
                    $on('hr') && $this->hasAny($permissions, ['hr.manage', 'hr.view'])
                        ? $this->item('HR', 'app.hr.index', icon: 'hr', active: request()->routeIs('app.hr.*'))
                        : null,
                    $on('clinic') && $this->has($permissions, 'clinic.manage')
                        ? $this->item('Clinic', 'app.clinic.index', icon: 'clinic', active: request()->routeIs('app.clinic.*'))
                        : null,
                ])),
            ],
            [
                'key' => 'reports',
                'label' => 'Reports',
                'items' => array_values(array_filter([
                    $this->has($permissions, 'reports.view')
                        ? $this->item('Class overview', 'app.classes.overview', icon: 'classes', active: request()->routeIs('app.classes.overview'))
                        : null,
                    $on('assessment') && $canAssess
                        ? $this->item('Academic reports', 'app.assessment.reports', icon: 'broadsheet', active: request()->routeIs('app.assessment.broadsheet') || request()->routeIs('app.assessment.reports'))
                        : null,
                    $on('fees') && $this->hasAny($permissions, ['finance.report.view', 'finance.view', 'finance.manage'])
                        ? $this->item('Financial reports', 'app.fees.index', icon: 'fees', active: request()->query('status') === 'cleared')
                        : null,
                    $on('emis') && $this->has($permissions, 'emis.manage')
                        ? $this->item('EMIS', 'app.emis.export', icon: 'emis')
                        : null,
                    $isPlatformOperator ? $this->item('Platform console', 'platform.dashboard', icon: 'platform') : null,
                ])),
            ],
            [
                'key' => 'help',
                'label' => 'Help-Center',
                'items' => array_values(array_filter([
                    $this->hasAny($permissions, ['helpdesk.create', 'helpdesk.view_own', 'helpdesk.manage'])
                        ? $this->item('Helpdesk', 'app.helpdesk.index', icon: 'helpdesk', active: request()->routeIs('app.helpdesk.*'))
                        : null,
                ])),
            ],
        ];
    }

    private function termLabel(bool $forSchoolZone): ?string
    {
        if (! $forSchoolZone) {
            return null;
        }

        $term = $this->academic->term();
        $year = $this->academic->year();
        if (! $term && ! $year) {
            return null;
        }

        return trim(($term?->name ?? '').' '.($year?->name ?? ''));
    }

    private function yearLabel(bool $forSchoolZone): ?string
    {
        if (! $forSchoolZone) {
            return null;
        }

        $name = $this->academic->year()?->name;
        if (! filled($name)) {
            return null;
        }

        return 'Academic year '.$name;
    }

    /** @return list<array{key: string, label: string, items: list<array>}> */
    private function platformSections(User $user): array
    {
        $entered = (bool) session('platform.entered_school_id');

        return [
            [
                'key' => 'general',
                'label' => 'General',
                'items' => array_values(array_filter([
                    $user->hasPlatformPermission('platform.dashboard.view')
                        ? $this->item('Dashboard', 'platform.dashboard', icon: 'dashboard') : null,
                    $entered && $user->hasPlatformPermission('platform.schools.enter')
                        ? $this->item('School workspace', 'platform.workspace', icon: 'workspace') : null,
                    $entered && $user->hasPlatformPermission('platform.schools.update')
                        ? $this->item('EMIS & SchoolPay', 'platform.workspace.settings', icon: 'platform') : null,
                ])),
            ],
            [
                'key' => 'schools',
                'label' => 'Schools',
                'items' => array_values(array_filter([
                    $user->hasPlatformPermission('platform.schools.view')
                        ? $this->item('Schools', 'platform.schools.index', icon: 'schools', active: request()->routeIs('platform.schools.*') && ! request()->routeIs('platform.schools.create') && ! request()->routeIs('platform.schools.walkthrough')) : null,
                    $user->hasPlatformPermission('platform.schools.create')
                        ? $this->item('Onboard school', 'platform.schools.create', icon: 'add', highlight: true) : null,
                    $user->hasPlatformPermission('platform.schools.create')
                        ? $this->item('Demonstration school', 'platform.schools.walkthrough', icon: 'add') : null,
                ])),
            ],
            [
                'key' => 'school_data',
                'label' => $entered ? 'School data entry' : 'School data entry',
                'items' => array_values(array_filter([
                    $entered && $user->hasPlatformPermission('platform.schools.enter')
                        ? $this->item('Students', 'platform.students.index', icon: 'students', active: request()->routeIs('platform.students.*')) : null,
                    $entered && $user->hasPlatformPermission('platform.schools.enter')
                        ? $this->item('Classes', 'platform.classes.index', icon: 'classes', active: request()->routeIs('platform.classes.*')) : null,
                    $entered && $user->hasPlatformPermission('platform.schools.enter')
                        ? $this->item('Staff', 'platform.staff.index', icon: 'staff', active: request()->routeIs('platform.staff.*')) : null,
                    ! $entered && $user->hasPlatformPermission('platform.schools.enter')
                        ? $this->item('Enter a school…', 'platform.schools.index', icon: 'workspace') : null,
                ])),
            ],
            [
                'key' => 'operations',
                'label' => 'Operations',
                'items' => array_values(array_filter([
                    $user->hasPlatformPermission('platform.support.view')
                        ? $this->item('Support inbox', 'platform.support.index', icon: 'helpdesk', active: request()->routeIs('platform.support.*')) : null,
                    $user->hasPlatformPermission('platform.staff.view')
                        ? $this->item('PearlEdu staff', 'platform.operators.index', icon: 'staff', active: request()->routeIs('platform.operators.*')) : null,
                    $user->hasPlatformPermission('platform.invitations.manage')
                        ? $this->item('Invitations', 'platform.invitations.index', icon: 'invites', active: request()->routeIs('platform.invitations.*')) : null,
                    $user->hasPlatformPermission('platform.audit.view')
                        ? $this->item('Audit trail', 'platform.audit.index', icon: 'assessment', active: request()->routeIs('platform.audit.*')) : null,
                    $user->hasPlatformPermission('platform.system.view')
                        ? $this->item('System overview', 'platform.system.index', icon: 'platform', active: request()->routeIs('platform.system.*')) : null,
                ])),
            ],
            [
                'key' => 'communications',
                'label' => 'Communications',
                'items' => array_values(array_filter([
                    $user->hasPlatformPermission('platform.sms.view')
                        ? $this->item('SMS & credits', 'platform.sms.index', icon: 'sms', active: request()->routeIs('platform.sms.*')) : null,
                ])),
            ],
            [
                'key' => 'marketing',
                'label' => 'Marketing',
                'items' => array_values(array_filter([
                    $user->hasPlatformPermission('platform.pricing.view')
                        ? $this->item('Pricing', 'platform.pricing.index', icon: 'pricing', active: request()->routeIs('platform.pricing.*')) : null,
                ])),
            ],
        ];
    }

    private function item(string $label, string $routeName, bool $active = false, bool $highlight = false, string $icon = 'dot'): array
    {
        if (! $active) {
            $active = request()->routeIs($routeName) || request()->routeIs($routeName.'.*');
        }

        return [
            'label' => $label,
            'route' => $routeName,
            'url' => Route::has($routeName) ? route($routeName) : null,
            'active' => $active,
            'highlight' => $highlight,
            'icon' => $icon,
        ];
    }

    /**
     * Collapsible group. Dropped when every child is hidden by permission or a dead route.
     *
     * @param  list<array<string, mixed>|null>  $children
     * @return array<string, mixed>|null
     */
    private function nest(string $label, string $icon, array $children): ?array
    {
        $kids = array_values(array_filter($children, static fn ($child) => is_array($child)));
        if ($kids === []) {
            return null;
        }

        $active = false;
        foreach ($kids as $child) {
            if (! empty($child['active'])) {
                $active = true;
                break;
            }
        }

        return [
            'label' => $label,
            'route' => '',
            'url' => null,
            'active' => $active,
            'highlight' => false,
            'icon' => $icon,
            'children' => $kids,
        ];
    }

    /**
     * Drop nav entries whose routes are missing so the sidebar never shows dead ends.
     * Nested groups stay when they still have a live child.
     *
     * @param  list<array{key: string, label: string, items: list<array>}>  $sections
     * @return list<array{key: string, label: string, items: list<array>}>
     */
    private function withoutDeadLinks(array $sections): array
    {
        return array_map(function (array $section) {
            $section['items'] = array_values(array_filter(
                array_map(fn ($item) => $this->pruneNavItem($item), $section['items'] ?? []),
                static fn ($item) => $item !== null
            ));

            return $section;
        }, $sections);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function pruneNavItem(mixed $item): ?array
    {
        if (! is_array($item)) {
            return null;
        }

        $children = [];
        foreach ($item['children'] ?? [] as $child) {
            $kept = $this->pruneNavItem($child);
            if ($kept !== null) {
                $children[] = $kept;
            }
        }

        if ($children !== []) {
            $item['children'] = $children;
            foreach ($children as $child) {
                if (! empty($child['active'])) {
                    $item['active'] = true;
                    break;
                }
            }

            return $item;
        }

        unset($item['children']);

        return empty($item['url']) ? null : $item;
    }

    private function has(array $permissions, string $perm): bool
    {
        return in_array($perm, $permissions, true);
    }

    /** @param list<string> $perms */
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
