<?php
namespace App\Http\Controllers\Platform;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolInvitation;
use App\Services\Provisioning\StaffInvitationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $school = $this->school($request);

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

        $roles = Role::query()
            ->whereIn('key', StaffInvitationService::INVITABLE_ROLES)
            ->orderBy('label')
            ->get();

        return view('platform.staff.index', compact('school', 'members', 'openInvites', 'roles'));
    }

    public function store(Request $request, StaffInvitationService $inviter)
    {
        $school = $this->school($request);

        $data = $request->validate([
            'full_name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:30',
            'role_key' => ['required', 'string', Rule::in(StaffInvitationService::INVITABLE_ROLES)],
        ]);

        try {
            $inviter->invite($school, $data, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        return back()->with('status', 'Invitation emailed to '.$data['email'].'.');
    }

    private function school(Request $request): School
    {
        return School::findOrFail($request->session()->get('platform.entered_school_id'));
    }
}