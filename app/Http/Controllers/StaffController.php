<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\SchoolInvitation;
use App\Services\Authorization\InvitePolicy;
use App\Services\Provisioning\StaffInvitationService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class StaffController extends Controller
{
    public function index(Request $request, TenantContext $context, InvitePolicy $policy)
    {
        $school = $context->school();
        abort_unless($school, 404);

        $members = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->with(['user', 'role'])
            ->get()
            ->groupBy('user_id')
            ->map(fn ($assignments) => [
                'user' => $assignments->first()->user,
                'roles' => $assignments->pluck('role.label')->unique()->values()->all(),
            ])
            ->sortBy(fn ($m) => $m['user']->full_name ?? '')
            ->values();

        $openInvites = SchoolInvitation::query()
            ->where('school_id', $school->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with('user')
            ->orderByDesc('id')
            ->get();

        $roleKeys = $policy->rolesInvitableBy($request->user(), $school->id, false);
        $roles = Role::query()->whereIn('key', $roleKeys)->orderBy('label')->get();

        return view('app.staff.index', compact('school', 'members', 'openInvites', 'roles'));
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
        ]);

        try {
            $result = $inviter->invite($school, $data, $request->user(), false);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        $via = collect([
            $data['email'] ?? null ? 'email' : null,
            $data['phone'] ?? null ? 'SMS' : null,
        ])->filter()->implode(' and ');

        return back()->with('status', 'Invitation sent via '.$via.' to '.$result['user']->full_name.'.');
    }
}
