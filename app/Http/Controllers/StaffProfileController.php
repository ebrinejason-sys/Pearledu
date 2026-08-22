<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\StaffSalary;
use App\Models\StaffSalaryPayment;
use App\Models\StaffTimePunch;
use App\Models\User;
use App\Services\Hr\StaffBadgeService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffProfileController extends Controller
{
    public function show(Request $request, User $user, TenantContext $ctx, StaffBadgeService $badges): View
    {
        $school = $ctx->school();
        abort_unless($school && $request->user(), 404);
        abort_unless($this->isSchoolStaff($school->id, $user), 404);

        $perms = $request->user()->permissionsForSchool($school->id);
        $roles = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with(['role', 'schoolClass'])
            ->get();

        return view('app.staff.show', [
            'school' => $school,
            'staff' => $user,
            'roles' => $roles,
            'badge' => $badges->issue($school, $user),
            'punches' => in_array('staff.attendance.view', $perms, true)
                ? StaffTimePunch::query()->where('school_id', $school->id)->where('user_id', $user->id)->orderByDesc('punched_at')->limit(20)->get()
                : collect(),
            'salary' => in_array('hr.payroll.view', $perms, true)
                ? StaffSalary::query()->where('school_id', $school->id)->where('user_id', $user->id)->first()
                : null,
            'payments' => in_array('hr.payroll.view', $perms, true)
                ? StaffSalaryPayment::query()->where('school_id', $school->id)->where('user_id', $user->id)->orderByDesc('paid_on')->limit(20)->get()
                : collect(),
            'canPrintId' => in_array('staff.id.print', $perms, true),
            'canViewPayroll' => in_array('hr.payroll.view', $perms, true),
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
