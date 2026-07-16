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
            'sections' => array_values(array_filter($sections, fn($s) => count($s['items']) > 0)),
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
        return [
            [
                'key' => 'general',
                'label' => 'General',
                'items' => array_values(array_filter([
                    $this->item('Home', 'app.home', icon: 'home'),
                ])),
            ],
            [
                'key' => 'learners',
                'label' => 'Learners',
                'items' => array_values(array_filter([
                    $this->has($permissions, 'learners.manage')
                        ? $this->item('Students', 'app.students.index', icon: 'students', active: request()->routeIs('app.students.*'))
                        : null,
                ])),
            ],
            [
                'key' => 'communications',
                'label' => 'Communications',
                'items' => array_values(array_filter([
                    $this->has($permissions, 'sms.send') ? $this->item('Send SMS', 'app.sms', icon: 'sms') : null,
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
}
