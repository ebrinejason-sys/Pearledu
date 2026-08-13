<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\SchoolClass;
use App\Models\SchoolInvitation;
use App\Models\User;
use App\Services\Authorization\InvitePolicy;
use App\Services\Provisioning\StaffInvitationService;
use App\Services\Provisioning\StaffRoleService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StaffController extends Controller
{
    public function index(Request $request, TenantContext $context, InvitePolicy $policy)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $roleKeys = $policy->rolesInvitableBy($request->user(), $school->id, false);
        $roles = Role::query()->whereIn('key', $roleKeys)->orderBy('label')->get();
        $classes = SchoolClass::query()->where('school_id', $school->id)->orderBy('name')->get();

        $members = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->with(['user', 'role', 'schoolClass'])
            ->get()
            ->groupBy('user_id')
            ->map(fn ($assignments) => [
                'user' => $assignments->first()->user,
                'roles' => $assignments->map(fn ($a) => [
                    'label' => $a->role?->label,
                    'class' => $a->schoolClass?->name,
                ])->unique(fn ($r) => $r['label'].'|'.$r['class'])->values()->all(),
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

        return view('app.staff.index', compact('school', 'members', 'openInvites', 'roles', 'classes'));
    }

    public function store(Request $request, TenantContext $context, StaffInvitationService $inviter, InvitePolicy $policy)
    {
        $school = $context->school();
        abort_unless($school, 404);
        $allowed = $policy->rolesInvitableBy($request->user(), $school->id, false);

        $data = $request->validate([
            'full_name' => 'required|string|max:120',
            'email' => 'nullable|email|max:190|required_without:phone',
            'phone' => 'nullable|string|max:30|required_without:email',
            'role_keys' => 'required|array|min:1',
            'role_keys.*' => ['string', Rule::in($allowed)],
            'class_id' => 'nullable|integer|exists:school_classes,id',
        ]);

        try {
            $result = $inviter->invite($school, $data, $request->user(), false);
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

    public function updateRoles(Request $request, User $user, TenantContext $context, StaffRoleService $roles, InvitePolicy $policy)
    {
        $school = $context->school();
        abort_unless($school, 404);
        $allowed = $policy->rolesInvitableBy($request->user(), $school->id, false);

        $data = $request->validate([
            'role_keys' => 'required|array|min:1',
            'role_keys.*' => ['string', Rule::in(array_values(array_unique(array_merge(
                $allowed,
                $user->activeAssignments()->where('school_id', $school->id)->with('role')->get()->pluck('role.key')->filter()->all()
            ))))],
        ]);

        $roles->sync($school, $user, $data['role_keys'], $request->user(), false);

        return back()->with('status', 'Roles updated for '.$user->full_name.'.');
    }

    public function revoke(Request $request, User $user, TenantContext $context, StaffRoleService $roles)
    {
        $school = $context->school();
        abort_unless($school, 404);
        $roles->revoke($school, $user, $request->user());

        return back()->with('status', 'School access revoked for '.$user->full_name.'.');
    }
}
