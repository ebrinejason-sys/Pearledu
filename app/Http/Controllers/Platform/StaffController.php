<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\SchoolInvitation;
use App\Services\Authorization\InvitePolicy;
use App\Services\Provisioning\StaffInvitationService;
use App\Services\Tenancy\EnteredSchoolGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        $roleKeys = $policy->rolesInvitableBy($request->user(), $school->id, true);
        $roles = Role::query()->whereIn('key', $roleKeys)->orderBy('label')->get();

        return view('platform.staff.index', compact('school', 'members', 'openInvites', 'roles'));
    }

    public function store(Request $request, StaffInvitationService $inviter, InvitePolicy $policy)
    {
        $school = $this->entered->school($request);
        $allowed = $policy->rolesInvitableBy($request->user(), $school->id, true);

        $data = $request->validate([
            'full_name' => 'required|string|max:120',
            'email' => 'nullable|email|max:190|required_without:phone',
            'phone' => 'nullable|string|max:30|required_without:email',
            'role_keys' => 'required|array|min:1',
            'role_keys.*' => ['string', Rule::in($allowed)],
        ]);

        try {
            $result = $inviter->invite($school, $data, $request->user(), true);
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
