<?php
namespace App\Http\Controllers;
use App\Services\Portal\PortalService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\Auth;

class AppHomeController extends Controller {
    public function index(TenantContext $context, PortalService $portal) {
        $user = Auth::user();
        $schoolId = $context->schoolId();
        $permissions = $schoolId && $user ? $user->permissionsForSchool($schoolId) : [];

        $portalPerms = [
            'child.results.view', 'self.results.view', 'child.fees.view',
            'fees.pay', 'self.timetable.view', 'announcements.view',
        ];
        $staffHeavy = collect($permissions)->contains(fn ($p) =>
            in_array($p, ['learners.manage', 'finance.manage', 'school.manage', 'staff.manage', 'assessment.enter'], true)
        );

        if (! $staffHeavy && collect($portalPerms)->intersect($permissions)->isNotEmpty() && $portal->learnersFor($user)->isNotEmpty()) {
            return redirect()->route('app.portal.home');
        }

        return view('app.home', [
            'school' => $context->school(),
            'permissions' => $permissions,
        ]);
    }
}
