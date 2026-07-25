<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\OnboardSchoolRequest;
use App\Models\School;
use App\Models\SchoolInvitation;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\InvitationMailer;
use App\Services\Provisioning\SchoolDeletionService;
use App\Services\Provisioning\SchoolProvisioner;
use App\Support\UgandaDistricts;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class SchoolController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request)
    {
        $query = School::query()->withCount('students');
        $search = trim($request->string('q')->toString());

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhere('emis_number', 'like', '%'.$search.'%');
            });
        }

        $status = $request->string('status')->toString();
        if (in_array($status, ['pending', 'active', 'suspended', 'archived', 'deletion_scheduled'], true)) {
            $query->where('status', $status);
        }

        $schools = $query->orderBy('name')->paginate(25)->withQueryString();

        return view('platform.schools.index', compact('schools'));
    }

    public function create()
    {
        return view('platform.schools.create', ['themes' => config('themes.themes')]);
    }

    public function store(OnboardSchoolRequest $request, SchoolProvisioner $provisioner, InvitationMailer $mailer)
    {
        $result = $provisioner->onboard(
            school: $request->only(['name', 'district', 'emis_number', 'theme']),
            levels: $request->input('levels'),
            admin: $request->input('admin'),
            operatorId: $request->user()->id,
        );

        $invitation = SchoolInvitation::query()
            ->where('school_id', $result['school']->id)
            ->where('user_id', $result['admin']->id)
            ->latest('id')
            ->first();

        $school = $result['school'];
        $status = 'Onboarded tenant #'.$school->tenantId()
            .'. Users sign in at '.$school->portalUrl().'/login — data is isolated to this school.';
        if ($invitation && ! empty($result['admin']->email)) {
            try {
                $mailer->send($invitation, $result['invite_token'], $school);
                $status .= ' Invitation emailed to '.$result['admin']->email.'.';
            } catch (RuntimeException $e) {
                report($e);
                $status .= ' Invitation created, but the email could not be sent. Ask the user to use password reset.';
            }
        } elseif ($invitation) {
            $status .= ' Invitation created — deliver the activation link out-of-band (no email on file).';
        }

        return redirect()->route('platform.schools.show', $school)
            ->with('status', $status);
    }

    public function show(School $school)
    {
        $school->load('offerings');
        $members = \App\Models\RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->with(['user', 'role'])
            ->get()
            ->groupBy('user_id')
            ->map(fn ($assignments) => [
                'user' => $assignments->first()->user,
                'roles' => $assignments->pluck('role.label')->unique()->values()->all(),
            ])
            ->sortBy(fn ($m) => $m['user']->full_name)
            ->values();
        $openInvites = SchoolInvitation::query()
            ->where('school_id', $school->id)
            ->whereNull('accepted_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('platform.schools.show', [
            'school' => $school,
            'members' => $members,
            'openInvites' => $openInvites,
            'themes' => config('themes.themes'),
        ]);
    }

    public function update(Request $request, School $school)
    {
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'district' => ['required', 'string', Rule::in(UgandaDistricts::optionsAllowing($school->district))],
            'emis_number' => [
                'nullable',
                'string',
                'max:60',
                Rule::unique('schools', 'emis_number')->ignore($school->id),
            ],
            'theme' => 'required|string|in:'.implode(',', array_keys(config('themes.themes', []))),
            'status' => 'required|in:pending,active,suspended,archived',
        ], [
            'district.in' => 'Choose a district from the Uganda list.',
        ]);

        if ($school->status === 'deletion_scheduled') {
            return back()->withErrors([
                'status' => 'This school is scheduled for deletion. Restore it before editing status.',
            ]);
        }

        $beforeStatus = $school->status;
        $school->update($data);

        if ($beforeStatus !== $data['status']) {
            $this->audit->record('school.status_changed', $school, [
                'from' => $beforeStatus,
                'to' => $data['status'],
                'tenant_id' => $school->tenantId(),
            ], actor: $request->user());

            // Drop workspace scope when school leaves a usable state.
            if (! in_array($data['status'], ['active', 'suspended'], true)
                && (int) $request->session()->get('platform.entered_school_id') === (int) $school->id) {
                $request->session()->forget('platform.entered_school_id');
            }
        }

        $this->audit->record('school.updated', $school, $data, actor: $request->user());

        return back()->with('status', 'School details saved.');
    }

    public function destroy(Request $request, School $school, SchoolDeletionService $deleter)
    {
        $request->validate([
            'confirm_name' => ['required', 'string', Rule::in([$school->name])],
            'deletion_reason' => ['nullable', 'string', 'max:500'],
        ], [
            'confirm_name.in' => 'Type the school name exactly to confirm deletion.',
        ]);

        if ((int) $request->session()->get('platform.entered_school_id') === (int) $school->id) {
            $request->session()->forget('platform.entered_school_id');
        }

        $result = $deleter->schedule(
            $school,
            $request->user(),
            $request->input('deletion_reason')
        );

        return redirect()->route('platform.schools.index')
            ->with('status', 'Scheduled deletion of '.$result['name'].' (tenant #'.$result['tenant_id'].').'
                .' Data will be retained until '.$result['purge_after'].'. Restore from the school page before then if needed.');
    }

    public function restore(Request $request, School $school, SchoolDeletionService $deleter)
    {
        try {
            $deleter->restore($school, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['school' => $e->getMessage()]);
        }

        return back()->with('status', 'School restored from scheduled deletion (status set to suspended).');
    }

    public function enter(Request $request, School $school)
    {
        $canEnter = $school->status === 'active'
            || ($school->status === 'suspended'
                && $request->user()->hasPlatformPermission('platform.schools.enter_suspended'));

        if ($school->status === 'deletion_scheduled' || ! $canEnter) {
            return redirect()->route('platform.schools.index')
                ->withErrors(['school' => $school->status === 'deletion_scheduled'
                    ? 'This school is scheduled for deletion and cannot be entered.'
                    : 'Only active schools can be entered.']);
        }

        $request->session()->regenerate();
        $request->session()->put('platform.entered_school_id', $school->id);
        $this->audit->record('school.entered', $school, ['slug' => $school->slug, 'tenant_id' => $school->tenantId()]);

        return redirect()->route('platform.workspace')
            ->with('status', 'Working in '.$school->name.'. You can enter students, classes, and staff for this school.');
    }

    public function leave(Request $request)
    {
        $request->session()->forget('platform.entered_school_id');
        $request->session()->regenerate();

        return redirect()->route('platform.schools.index')
            ->with('status', 'Left school workspace.');
    }
}
