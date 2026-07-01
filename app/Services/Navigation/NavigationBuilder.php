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

    /** @return array{zone: string, items: list<array{label: string, route: string|null, url: string|null, active: bool, highlight?: bool}>, user: array, impersonation: array|null, school: array|null} */
    public function build(?User $user): array
    {
        if (! $user) {
            return ['zone' => 'guest', 'items' => [], 'user' => [], 'impersonation' => null, 'school' => null];
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
            $items = [
                $this->item('Dashboard', 'platform.dashboard'),
                $this->item('Schools', 'platform.schools.index', active: request()->routeIs('platform.schools.*') && ! request()->routeIs('platform.schools.create')),
                $this->item('SMS & credits', 'platform.sms.index', active: request()->routeIs('platform.sms.*')),
                $this->item('Onboard school', 'platform.schools.create', highlight: true),
            ];
            $zone = 'platform';
        } else {
            $items = array_values(array_filter([
                $this->item('Home', 'app.home'),
                $this->has($permissions, 'sms.send') ? $this->item('Send SMS', 'app.sms') : null,
                $this->item('Account', 'account.settings'),
                $isPlatformOperator ? $this->item('Platform console', 'platform.dashboard') : null,
            ]));
            $zone = 'school';
        }

        return [
            'zone' => $zone,
            'items' => $items,
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

    private function item(string $label, string $routeName, bool $active = false, bool $highlight = false): array
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
        ];
    }

    private function has(array $permissions, string $perm): bool
    {
        return in_array($perm, $permissions, true);
    }
}
