<?php
namespace App\Services\Navigation;
use App\Models\User;
use App\Services\Platform\ImpersonationService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\Route;

class NavigationBuilder
{
    public function __construct(
        private TenantContext $context,
        private ImpersonationService $impersonation,
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
        $permissions = $schoolId ? $user->permissionsForSchool($schoolId) : [];
        $onPlatform = request()->routeIs('platform.*');
        $isPlatformOperator = $user->isPlatformOperator() && ! $this->impersonation->isActive();

        $roleLabels = $schoolId
            ? $user->activeAssignments()->where('school_id', $schoolId)->with('role')->get()->pluck('role.label')->unique()->values()->all()
            : [];

        $impersonation = null;
        if ($this->impersonation->isActive()) {
            $operator = $this->impersonation->operator();
            $impersonation = [
                'operator_name' => $operator?->full_name ?? 'Platform admin',
                'target_name' => $user->full_name,
                'school_name' => $school?->name,
            ];
        }

        if ($isPlatformOperator && $onPlatform) {
            $sections = $this->platformSections();
            $zone = 'platform';
        } else {
            $sections = $this->schoolSections($permissions, $isPlatformOperator);
            $zone = 'school';
        }

        return [
            'zone' => $zone,
            'sections' => array_values(array_filter($sections, fn ($s) => count($s['items']) > 0)),
            'account' => $this->item('Account settings', 'account.settings', icon: 'account'),
            'user' => [
                'name' => $user->full_name,
                'email' => $user->email ?? $user->phone,
                'initial' => strtoupper(substr($user->full_name, 0, 1)),
                'roles' => $roleLabels,
                'is_platform' => $isPlatformOperator,
            ],
            'impersonation' => $impersonation,
            'school' => $school ? ['id' => $school->id, 'name' => $school->name, 'slug' => $school->slug] : null,
        ];
    }

    /** @return list<array{key: string, label: string, items: list<array>}> */
    private function schoolSections(array $permissions, bool $isPlatformOperator): array
    {
        $canAssess = $this->hasAny($permissions, ['assessment.enter', 'assessment.manage', 'assessment.view']);

        return [
            [
                'key' => 'general',
                'label' => 'General',
                'items' => array_values(array_filter([
                    $this->item('Home', 'app.home', icon: 'home'),
                    $this->has($permissions, 'school.manage')
                        ? $this->item('School identity', 'app.settings.school', icon: 'platform', active: request()->routeIs('app.settings.*'))
                        : null,
                    $this->hasAny($permissions, [
                        'child.results.view', 'self.results.view', 'child.fees.view',
                        'fees.pay', 'self.timetable.view', 'announcements.view',
                    ]) ? $this->item('My portal', 'app.portal.home', icon: 'home', active: request()->routeIs('app.portal.*')) : null,
                    $this->has($permissions, 'staff.manage')
                        ? $this->item('Staff', 'app.staff.index', icon: 'staff', active: request()->routeIs('app.staff.*'))
                        : null,
                    $this->item('Helpdesk', 'app.helpdesk.index', icon: 'helpdesk', active: request()->routeIs('app.helpdesk.*')),
                ])),
            ],
            [
                'key' => 'family',
                'label' => 'Family & learning',
                'items' => array_values(array_filter([
                    $this->hasAny($permissions, ['child.results.view', 'self.results.view'])
                        ? $this->item('Results', 'app.portal.results', icon: 'assessment', active: request()->routeIs('app.portal.results'))
                        : null,
                    $this->hasAny($permissions, ['child.fees.view', 'fees.pay'])
                        ? $this->item('My fees', 'app.portal.fees', icon: 'fees', active: request()->routeIs('app.portal.fees*'))
                        : null,
                    $this->has($permissions, 'self.timetable.view')
                        ? $this->item('My timetable', 'app.portal.timetable', icon: 'timetable', active: request()->routeIs('app.portal.timetable'))
                        : null,
                    $this->has($permissions, 'announcements.view')
                        ? $this->item('Announcements', 'app.portal.announcements', icon: 'announcements', active: request()->routeIs('app.portal.announcements'))
                        : null,
                    $this->has($permissions, 'lms.view')
                        ? $this->item('LMS', 'app.lms.index', icon: 'lms', active: request()->routeIs('app.lms.*'))
                        : null,
                    $this->has($permissions, 'cbt.take')
                        ? $this->item('CBT exams', 'app.cbt.index', icon: 'cbt', active: request()->routeIs('app.cbt.*'))
                        : null,
                ])),
            ],
            [
                'key' => 'learners',
                'label' => 'Learners',
                'items' => array_values(array_filter([
                    $this->has($permissions, 'learners.manage')
                        ? $this->item('Students', 'app.students.index', icon: 'students', active: request()->routeIs('app.students.*'))
                        : null,
                    $this->has($permissions, 'learners.manage')
                        ? $this->item('Enrollments', 'app.enrollments.index', icon: 'enrollments', active: request()->routeIs('app.enrollments.*'))
                        : null,
                    $this->has($permissions, 'admissions.manage')
                        ? $this->item('Admissions', 'app.admissions.index', icon: 'admissions', active: request()->routeIs('app.admissions.*'))
                        : null,
                    $this->has($permissions, 'emis.manage')
                        ? $this->item('EMIS export', 'app.emis.export', icon: 'emis')
                        : null,
                ])),
            ],
            [
                'key' => 'academics',
                'label' => 'Academics',
                'items' => array_values(array_filter([
                    $this->has($permissions, 'school.manage')
                        ? $this->item('Academic years', 'app.years.index', icon: 'years', active: request()->routeIs('app.years.*'))
                        : null,
                    $this->has($permissions, 'school.manage')
                        ? $this->item('Subjects', 'app.subjects.index', icon: 'subjects', active: request()->routeIs('app.subjects.*'))
                        : null,
                    $this->has($permissions, 'school.manage')
                        ? $this->item('Teaching', 'app.teaching.index', icon: 'teaching', active: request()->routeIs('app.teaching.*'))
                        : null,
                    $this->has($permissions, 'attendance.mark')
                        ? $this->item('Attendance', 'app.attendance.index', icon: 'attendance', active: request()->routeIs('app.attendance.*'))
                        : null,
                    $canAssess
                        ? $this->item('Assessment', 'app.assessment.index', icon: 'assessment', active: request()->routeIs('app.assessment.index') || request()->routeIs('app.assessment.periods.*') || request()->routeIs('app.assessment.marks*'))
                        : null,
                    $canAssess
                        ? $this->item('Broadsheet', 'app.assessment.broadsheet', icon: 'broadsheet', active: request()->routeIs('app.assessment.broadsheet') || request()->routeIs('app.assessment.reports'))
                        : null,
                    $this->has($permissions, 'promotions.approve')
                        ? $this->item('Promotions', 'app.promotions.index', icon: 'promotions', active: request()->routeIs('app.promotions.*'))
                        : null,
                    $this->has($permissions, 'timetable.manage')
                        ? $this->item('Timetable', 'app.timetable.index', icon: 'timetable', active: request()->routeIs('app.timetable.*'))
                        : null,
                ])),
            ],
            [
                'key' => 'finance',
                'label' => 'Finance',
                'items' => array_values(array_filter([
                    $this->has($permissions, 'finance.manage')
                        ? $this->item('Fees', 'app.fees.index', icon: 'fees', active: request()->routeIs('app.fees.*'))
                        : null,
                ])),
            ],
            [
                'key' => 'learning',
                'label' => 'Learning',
                'items' => array_values(array_filter([
                    $this->has($permissions, 'lms.manage')
                        ? $this->item('LMS', 'app.lms.index', icon: 'lms', active: request()->routeIs('app.lms.*'))
                        : null,
                    $this->has($permissions, 'cbt.manage')
                        ? $this->item('CBT', 'app.cbt.index', icon: 'cbt', active: request()->routeIs('app.cbt.*'))
                        : null,
                    $this->has($permissions, 'library.manage')
                        ? $this->item('Library', 'app.library.index', icon: 'library', active: request()->routeIs('app.library.*'))
                        : null,
                ])),
            ],
            [
                'key' => 'operations',
                'label' => 'Operations',
                'items' => array_values(array_filter([
                    $this->has($permissions, 'inventory.manage')
                        ? $this->item('Inventory', 'app.inventory.index', icon: 'inventory', active: request()->routeIs('app.inventory.*'))
                        : null,
                    $this->has($permissions, 'transport.manage')
                        ? $this->item('Transport', 'app.transport.index', icon: 'transport', active: request()->routeIs('app.transport.*'))
                        : null,
                    $this->has($permissions, 'hostel.manage')
                        ? $this->item('Hostel', 'app.hostel.index', icon: 'hostel', active: request()->routeIs('app.hostel.*'))
                        : null,
                    $this->has($permissions, 'hr.manage')
                        ? $this->item('HR', 'app.hr.index', icon: 'hr', active: request()->routeIs('app.hr.*'))
                        : null,
                    $this->has($permissions, 'clinic.manage')
                        ? $this->item('Clinic', 'app.clinic.index', icon: 'clinic', active: request()->routeIs('app.clinic.*'))
                        : null,
                ])),
            ],
            [
                'key' => 'communications',
                'label' => 'Communications',
                'items' => array_values(array_filter([
                    $this->has($permissions, 'sms.send') ? $this->item('Send SMS', 'app.sms', icon: 'sms') : null,
                    $this->has($permissions, 'announcements.manage')
                        ? $this->item('Manage announcements', 'app.announcements.index', icon: 'announcements', active: request()->routeIs('app.announcements.*'))
                        : null,
                ])),
            ],
            [
                'key' => 'platform',
                'label' => 'Platform',
                'items' => array_values(array_filter([
                    $isPlatformOperator ? $this->item('Platform console', 'platform.dashboard', icon: 'platform') : null,
                ])),
            ],
        ];
    }

    /** @return list<array{key: string, label: string, items: list<array>}> */
    private function platformSections(): array
    {
        $entered = (bool) session('platform.entered_school_id');

        return [
            [
                'key' => 'general',
                'label' => 'General',
                'items' => array_values(array_filter([
                    $this->item('Dashboard', 'platform.dashboard', icon: 'dashboard'),
                    $entered ? $this->item('School workspace', 'platform.workspace', icon: 'workspace') : null,
                ])),
            ],
            [
                'key' => 'schools',
                'label' => 'Schools',
                'items' => [
                    $this->item('Schools', 'platform.schools.index', icon: 'schools', active: request()->routeIs('platform.schools.*') && ! request()->routeIs('platform.schools.create')),
                    $this->item('Onboard school', 'platform.schools.create', icon: 'add', highlight: true),
                ],
            ],
            [
                'key' => 'school_data',
                'label' => $entered ? 'School data entry' : 'School data entry',
                'items' => array_values(array_filter([
                    $entered ? $this->item('Students', 'platform.students.index', icon: 'students', active: request()->routeIs('platform.students.*')) : null,
                    $entered ? $this->item('Classes', 'platform.classes.index', icon: 'classes', active: request()->routeIs('platform.classes.*')) : null,
                    $entered ? $this->item('Staff', 'platform.staff.index', icon: 'staff', active: request()->routeIs('platform.staff.*')) : null,
                    ! $entered ? $this->item('Enter a school…', 'platform.schools.index', icon: 'workspace') : null,
                ])),
            ],
            [
                'key' => 'operations',
                'label' => 'Operations',
                'items' => [
                    $this->item('Support inbox', 'platform.support.index', icon: 'helpdesk', active: request()->routeIs('platform.support.*')),
                    $this->item('PearlEdu staff', 'platform.operators.index', icon: 'staff', active: request()->routeIs('platform.operators.*')),
                    $this->item('Invitations', 'platform.invitations.index', icon: 'invites', active: request()->routeIs('platform.invitations.*')),
                ],
            ],
            [
                'key' => 'communications',
                'label' => 'Communications',
                'items' => [
                    $this->item('SMS & credits', 'platform.sms.index', icon: 'sms', active: request()->routeIs('platform.sms.*')),
                ],
            ],
            [
                'key' => 'marketing',
                'label' => 'Marketing',
                'items' => [
                    $this->item('Pricing', 'platform.pricing.index', icon: 'pricing', active: request()->routeIs('platform.pricing.*')),
                ],
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
