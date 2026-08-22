<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\ActionCenterService;
use App\Services\Dashboard\RoleWorkspaceService;
use App\Services\Dashboard\SchoolDashboardService;
use App\Services\Portal\PortalService;
use App\Services\Schools\SchoolSetupService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\Auth;

class AppHomeController extends Controller
{
    public function index(
        TenantContext $context,
        PortalService $portal,
        SchoolDashboardService $dashboard,
        ActionCenterService $actions,
        SchoolSetupService $setup,
        RoleWorkspaceService $workspaces,
    ) {
        $user = Auth::user();
        $schoolId = $context->schoolId();
        $permissions = $schoolId && $user ? $user->permissionsForSchool($schoolId) : [];

        $portalPerms = [
            'child.results.view', 'self.results.view', 'child.fees.view', 'self.fees.view',
            'child.attendance.view', 'self.attendance.view',
            'fees.pay', 'self.timetable.view', 'announcements.view',
        ];
        $staffHeavy = collect($permissions)->contains(fn ($p) => in_array($p, [
            'learners.manage', 'learners.view', 'finance.manage', 'finance.view', 'school.manage',
            'curriculum.manage', 'staff.manage', 'staff.invite.teacher',
            'assessment.enter', 'assessment.manage', 'assessment.view', 'class.view',
        ], true));

        if (! $staffHeavy && collect($portalPerms)->intersect($permissions)->isNotEmpty() && $portal->learnersFor($user)->isNotEmpty()) {
            return redirect()->route('app.portal.home');
        }

        $school = $context->school();
        $board = $school ? $dashboard->build($school, $permissions, $user) : null;
        $workspace = ($school && $user) ? $workspaces->build($school, $user, $permissions) : null;

        return view('app.home', [
            'school' => $school,
            'permissions' => $permissions,
            'workspace' => $workspace,
            'stats' => $board['stats'] ?? [],
            'classChart' => $board['classChart'] ?? [],
            'feeChart' => $board['feeChart'] ?? [],
            'shortcuts' => $board['shortcuts'] ?? [],
            'permissionLabels' => $board['permissionLabels'] ?? [],
            'actionItems' => ($school && $user) ? $actions->items($school, $user, $permissions) : [],
            'setupPercent' => $school ? $setup->completionPercentage($school) : 100,
            'setupNext' => $school ? $setup->nextStep($school) : null,
            'setupComplete' => $school ? $setup->isComplete($school) : true,
            'emis' => (! empty($workspace['governance']) || in_array('director', $workspace['roleKeys'] ?? [], true))
                ? ($workspace['governance']['emis'] ?? $board['emis'] ?? null)
                : null,
            'showSchoolCharts' => in_array('reports.view', $permissions, true)
                || in_array('finance.view', $permissions, true)
                || in_array('director', $workspace['roleKeys'] ?? [], true),
            'showShortcuts' => in_array($workspace['primary'] ?? 'none', ['hygiene', 'academicLead', 'bursar', 'operationsLead', 'governance'], true),
        ]);
    }
}
