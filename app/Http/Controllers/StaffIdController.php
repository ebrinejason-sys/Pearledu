<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Services\Hr\StaffBadgeService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffIdController extends Controller
{
    public function show(Request $request, User $user, TenantContext $ctx, StaffBadgeService $badges): View
    {
        $school = $ctx->school();
        abort_unless($school && $request->user(), 404);
        abort_unless($this->isSchoolStaff($school->id, $user), 404);

        $badge = $badges->issue($school, $user);
        $roles = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with('role')
            ->get()
            ->pluck('role.label')
            ->filter()
            ->unique()
            ->values();

        return view('app.staff.id-card', [
            'school' => $school,
            'staff' => $user,
            'badge' => $badge,
            'qr' => $badges->qrSvg($badge->code),
            'roles' => $roles,
        ]);
    }

    private function isSchoolStaff(int $schoolId, User $user): bool
    {
        return RoleAssignment::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->whereIn('key', Role::STAFF))
            ->exists();
    }
}
