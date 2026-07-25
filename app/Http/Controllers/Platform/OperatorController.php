<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Platform\PlatformStaffService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class OperatorController extends Controller
{
    public function __construct(private PlatformStaffService $staff) {}

    public function index(Request $request)
    {
        $actor = $request->user();
        $actorRole = $this->staff->resolvedRoleKey($actor);

        $operators = User::query()
            ->where('is_platform', true)
            ->orderBy('full_name')
            ->get()
            ->map(function (User $user) {
                $user->platform_role = $this->staff->resolvedRoleKey($user);

                return $user;
            });

        $misconfigured = $operators->filter(fn (User $u) => $u->platform_role === null)->values();

        return view('platform.operators.index', [
            'operators' => $operators,
            'roles' => PlatformStaffService::roleLabels(),
            'assignableRoles' => $this->staff->assignableRoles($actor),
            'actor' => $actor,
            'actorRole' => $actorRole,
            'staff' => $this->staff,
            'misconfigured' => $misconfigured,
        ]);
    }

    public function create(Request $request)
    {
        $assignable = $this->staff->assignableRoles($request->user());
        abort_if($assignable === [], 403, 'Your role cannot create PearlEdu staff.');

        return view('platform.operators.create', [
            'roles' => $assignable,
        ]);
    }

    public function store(Request $request)
    {
        $assignable = array_keys($this->staff->assignableRoles($request->user()));
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_key' => ['required', Rule::in($assignable)],
            'password' => ['nullable', 'string', 'min:10'],
        ]);

        try {
            $result = $this->staff->create($data, $request->user());
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        $status = 'Created PearlEdu staff: '.$result['user']->full_name.' ('.$data['role_key'].').';
        if ($result['emailed']) {
            $status .= ' Login details were emailed to '.$result['user']->email.'.';
        } else {
            $status .= ' Account created, but the welcome email could not be sent. Ask the user to use password reset.';
        }

        return redirect()->route('platform.operators.index')->with('status', $status);
    }

    public function edit(Request $request, User $operator)
    {
        abort_unless($operator->is_platform, 404);
        try {
            $this->staff->assertCanManage($request->user(), $operator);
        } catch (RuntimeException $e) {
            abort(403, $e->getMessage());
        }

        return view('platform.operators.edit', [
            'operator' => $operator,
            'roleKey' => $this->staff->resolvedRoleKey($operator),
            'roles' => $this->staff->assignableRoles($request->user()),
        ]);
    }

    public function update(Request $request, User $operator)
    {
        abort_unless($operator->is_platform, 404);

        $assignable = array_keys($this->staff->assignableRoles($request->user()));
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_key' => ['required', Rule::in($assignable)],
            'status' => ['required', Rule::in(['active', 'disabled'])],
        ]);

        try {
            $this->staff->update($operator, $data, $request->user());
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()->route('platform.operators.index')->with('status', 'Staff member updated.');
    }

    public function destroy(Request $request, User $operator)
    {
        abort_unless($operator->is_platform, 404);

        try {
            $this->staff->delete($operator, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return redirect()->route('platform.operators.index')
            ->with('status', 'Removed PearlEdu staff account.');
    }

    public function resetPassword(Request $request, User $operator)
    {
        abort_unless($operator->is_platform, 404);

        try {
            $result = $this->staff->resetPassword($operator, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['password' => $e->getMessage()]);
        }

        if ($result['emailed']) {
            return back()->with('status', 'New temporary password emailed to '.$operator->email.'.');
        }

        return back()->with(
            'status',
            'Password reset, but the email could not be sent. Ask the user to use password reset.'
        );
    }
}
