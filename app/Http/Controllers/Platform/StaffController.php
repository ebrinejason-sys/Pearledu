<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\SchoolClass;
use App\Models\SchoolInvitation;
use App\Models\Subject;
use App\Models\User;
use App\Services\Authorization\InvitePolicy;
use App\Services\Provisioning\StaffInvitationService;
use App\Services\Provisioning\StaffRoleService;
use App\Services\Tenancy\EnteredSchoolGuard;
use App\Support\Gender;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StaffController extends Controller
{
    public function __construct(private EnteredSchoolGuard $entered) {}

    public function index(Request $request, InvitePolicy $policy)
    {
        $school = $this->entered->school($request);

        $members = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->with(['user', 'role'])
            ->get()
            ->groupBy('user_id')
            ->map(fn ($assignments) => [
                'user' => $assignments->first()->user,
                'roles' => $assignments->pluck('role.label')->filter()->unique()->values()->all(),
                'role_keys' => $assignments->pluck('role.key')->filter()->unique()->values()->all(),
            ])
            ->filter(fn ($m) => $m['user'] !== null)
            ->sortBy(fn ($m) => $m['user']->full_name ?? '')
            ->values();

        $openInvites = SchoolInvitation::query()
            ->where('school_id', $school->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with('user')
            ->orderByDesc('id')
            ->get();

        $roleKeys = $policy->rolesInvitableBy($request->user(), $school->id, true);
        $roles = Role::query()->whereIn('key', $roleKeys)->orderBy('label')->get();
        $classes = SchoolClass::query()->where('school_id', $school->id)->orderBy('name')->orderBy('stream')->get();
        $subjects = Subject::query()->where('school_id', $school->id)->orderBy('name')->get();
        $canImitate = $request->user()->hasPlatformPermission('platform.users.impersonate');

        return view('platform.staff.index', compact('school', 'members', 'openInvites', 'roles', 'classes', 'subjects', 'canImitate'));
    }

    public function store(Request $request, StaffInvitationService $inviter, InvitePolicy $policy)
    {
        $school = $this->entered->school($request);
        $allowed = $policy->rolesInvitableBy($request->user(), $school->id, true);

        $needsIdentity = collect($request->input('role_keys', []))->intersect(array_merge(Role::STAFF, [Role::PARENT]))->isNotEmpty();
        $data = $request->validate([
            'full_name' => 'required|string|max:120',
            'email' => 'nullable|email|max:190|required_without:phone',
            'phone' => 'nullable|string|max:30|required_without:email',
            'gender' => [$needsIdentity ? 'required' : 'nullable', Rule::in(Gender::keys())],
            'nin' => [$needsIdentity ? 'required' : 'nullable', 'string', 'min:10', 'max:20'],
            'role_keys' => 'required|array|min:1',
            'role_keys.*' => ['string', Rule::in($allowed)],
            'teaching_assignments' => 'nullable|array',
            'teaching_assignments.*.subject_id' => 'nullable|integer',
            'teaching_assignments.*.class_ids' => 'nullable|array',
            'teaching_assignments.*.class_ids.*' => 'integer',
            'teaching_assignments.*.periods_per_week' => 'nullable|integer|min:1|max:20',
        ]);
        if (in_array('subject_teacher', $data['role_keys'], true)) {
            $data['teaching_assignments'] = $data['teaching_assignments'] ?? [];
        }

        try {
            $result = $inviter->invite($school, $data, $request->user(), true);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'email' => 'Invitation could not be completed: '.$e->getMessage(),
            ]);
        }

        $delivery = $result['delivery'] ?? ['email' => false, 'sms' => false, 'warnings' => []];
        $via = collect([
            ! empty($delivery['email']) ? 'email' : null,
            ! empty($delivery['sms']) ? 'SMS' : null,
        ])->filter()->implode(' and ');

        $status = $via !== ''
            ? 'Invitation sent via '.$via.' to '.$result['user']->full_name.'.'
            : 'Invitation created for '.$result['user']->full_name.'.';
        if (! empty($delivery['warnings'])) {
            $status .= ' Note: '.implode(' ', $delivery['warnings']);
        }

        return back()->with('status', $status);
    }

    public function updateRoles(Request $request, User $user, StaffRoleService $roles, InvitePolicy $policy)
    {
        $school = $this->entered->school($request);
        $allowed = $policy->rolesInvitableBy($request->user(), $school->id, true);

        $data = $request->validate([
            'role_keys' => 'required|array|min:1',
            'role_keys.*' => ['string', Rule::in($allowed)],
            'teaching_assignments' => 'nullable|array',
            'teaching_assignments.*.subject_id' => 'nullable|integer',
            'teaching_assignments.*.class_ids' => 'nullable|array',
            'teaching_assignments.*.class_ids.*' => 'integer',
            'teaching_assignments.*.periods_per_week' => 'nullable|integer|min:1|max:20',
        ]);

        try {
            $roles->sync(
                $school,
                $user,
                $data['role_keys'],
                $request->user(),
                true,
                null,
                $data['teaching_assignments'] ?? [],
            );
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return back()->with('status', 'Roles updated for '.$user->full_name.'.');
    }

    public function revoke(Request $request, User $user, StaffRoleService $roles)
    {
        $school = $this->entered->school($request);
        $roles->revoke($school, $user, $request->user());

        return back()->with('status', 'School access revoked for '.$user->full_name.'.');
    }
}
