<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\RoleWorkspaceService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;

class TeachingWorkspaceController extends Controller
{
    public function teaching(Request $request, TenantContext $context, RoleWorkspaceService $workspaces)
    {
        $school = $context->school();
        abort_unless($school, 404);
        $user = $request->user();
        abort_unless($user, 403);

        $permissions = $user->permissionsForSchool($school->id);
        abort_unless(in_array('assessment.enter', $permissions, true) || in_array('lms.manage', $permissions, true), 403);

        $board = $workspaces->build($school, $user, $permissions);

        return view('app.teaching.my-teaching', [
            'school' => $school,
            'workspace' => $board['teacher'],
            'greeting' => $board['greeting'],
            'permissions' => $permissions,
        ]);
    }

    public function homeroom(Request $request, TenantContext $context, RoleWorkspaceService $workspaces)
    {
        $school = $context->school();
        abort_unless($school, 404);
        $user = $request->user();
        abort_unless($user, 403);

        $permissions = $user->permissionsForSchool($school->id);
        abort_unless(in_array('class.view', $permissions, true), 403);

        $board = $workspaces->build($school, $user, $permissions);

        return view('app.teaching.my-class', [
            'school' => $school,
            'homeroom' => $board['homeroom'],
            'greeting' => $board['greeting'],
        ]);
    }
}
