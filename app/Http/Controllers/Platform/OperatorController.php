<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Platform\PlatformStaffService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class OperatorController extends Controller
{
    public function __construct(
        private PlatformStaffService $staff,
        private AuditLogger $audit,
    ) {}

    public function index()
    {
        $operators = User::query()
            ->where('is_platform', true)
            ->orderBy('full_name')
            ->get()
            ->map(function (User $user) {
                $user->platform_role = $this->staff->platformRoleKey($user);

                return $user;
            });

        return view('platform.operators.index', [
            'operators' => $operators,
            'roles' => PlatformStaffService::roleLabels(),
        ]);
    }

    public function create()
    {
        return view('platform.operators.create', [
            'roles' => PlatformStaffService::roleLabels(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_key' => ['required', Rule::in(PlatformStaffService::ROLE_KEYS)],
            'password' => ['nullable', 'string', 'min:10'],
        ]);

        try {
            $result = $this->staff->create($data, (int) $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        $status = 'Created PearlEdu staff: '.$result['user']->full_name.' ('.$data['role_key'].').';
        if ($result['temporary_password']) {
            $status .= ' Temporary password: '.$result['temporary_password'].' — share securely, then ask them to change it.';
        }

        return redirect()->route('platform.operators.index')->with('status', $status);
    }

    public function update(Request $request, User $operator)
    {
        abort_unless($operator->is_platform, 404);

        $data = $request->validate([
            'role_key' => ['required', Rule::in(PlatformStaffService::ROLE_KEYS)],
            'status' => ['required', Rule::in(['active', 'disabled'])],
        ]);

        try {
            $this->staff->updateRole($operator, $data['role_key'], (int) $request->user()->id);
            $this->staff->setStatus($operator, $data['status']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['role_key' => $e->getMessage()]);
        }

        return back()->with('status', 'Staff member updated.');
    }

    public function resetPassword(Request $request, User $operator)
    {
        abort_unless($operator->is_platform, 404);
        abort_if((int) $operator->id === (int) $request->user()->id, 422, 'Reset another account’s password, not your own, from here.');

        try {
            $temp = $this->staff->resetPassword($operator);
        } catch (RuntimeException $e) {
            return back()->withErrors(['password' => $e->getMessage()]);
        }

        return back()->with('status', 'New temporary password for '.$operator->full_name.': '.$temp);
    }
}
