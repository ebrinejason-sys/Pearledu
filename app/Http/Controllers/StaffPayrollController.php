<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\StaffSalary;
use App\Models\StaffSalaryPayment;
use App\Models\User;
use App\Services\Hr\StaffPayrollService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffPayrollController extends Controller
{
    public function index(Request $request, TenantContext $ctx): View
    {
        $school = $ctx->school();
        abort_unless($school && $request->user(), 404);

        $staff = User::query()
            ->whereIn(
                'id',
                RoleAssignment::query()
                    ->where('school_id', $school->id)
                    ->where('is_active', true)
                    ->whereHas('role', fn ($q) => $q->whereIn('key', Role::STAFF))
                    ->pluck('user_id')
            )
            ->orderBy('full_name')
            ->get();

        $salaries = StaffSalary::query()->where('school_id', $school->id)->get()->keyBy('user_id');
        $payments = StaffSalaryPayment::query()
            ->where('school_id', $school->id)
            ->with('user')
            ->orderByDesc('paid_on')
            ->limit(80)
            ->get();

        $canManage = in_array('hr.payroll.manage', $request->user()->permissionsForSchool($school->id), true);

        return view('app.staff.payroll', compact('school', 'staff', 'salaries', 'payments', 'canManage'));
    }

    public function storeSalary(Request $request, User $user, TenantContext $ctx, StaffPayrollService $payroll): RedirectResponse
    {
        $school = $ctx->school();
        abort_unless($school && $request->user(), 404);
        abort_unless($this->isSchoolStaff($school->id, $user), 404);

        $data = $request->validate([
            'amount' => 'required|integer|min:0',
            'effective_on' => 'required|date',
            'notes' => 'nullable|string|max:255',
        ]);

        $payroll->setSalary($school, $user, $data, $request->user());

        return back()->with('status', 'Salary saved for '.$user->full_name.'.');
    }

    public function storePayment(Request $request, User $user, TenantContext $ctx, StaffPayrollService $payroll): RedirectResponse
    {
        $school = $ctx->school();
        abort_unless($school && $request->user(), 404);
        abort_unless($this->isSchoolStaff($school->id, $user), 404);

        $data = $request->validate([
            'amount' => 'required|integer|min:1',
            'paid_on' => 'required|date',
            'method' => 'required|string|in:bank,cash,mobile_money',
            'reference' => 'nullable|string|max:80',
            'notes' => 'nullable|string|max:255',
        ]);

        $payroll->recordPayment($school, $user, $data, $request->user());

        return back()->with('status', 'Payment recorded for '.$user->full_name.'.');
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
