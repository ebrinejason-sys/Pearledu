<?php
namespace App\Http\Controllers\Platform;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\OnboardSchoolRequest;
use App\Models\School;
use App\Models\SchoolInvitation;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\InvitationMailer;
use App\Services\Provisioning\SchoolProvisioner;
use Illuminate\Http\Request;
use RuntimeException;

class SchoolController extends Controller {
    public function __construct(private AuditLogger $audit) {}

    public function index() {
        $schools = School::withCount('students')->orderBy('name')->get();
        return view('platform.schools.index', compact('schools'));
    }

    public function create() {
        return view('platform.schools.create', ['themes'=>config('themes.themes')]);
    }

    public function store(OnboardSchoolRequest $request, SchoolProvisioner $provisioner, InvitationMailer $mailer) {
        $result = $provisioner->onboard(
            school: $request->only(['name','district','emis_number','theme']),
            levels: $request->input('levels'),
            admin:  $request->input('admin'),
            operatorId: $request->user()->id,
        );

        $invitation = SchoolInvitation::query()
            ->where('school_id', $result['school']->id)
            ->where('user_id', $result['admin']->id)
            ->latest('id')
            ->first();

        $status = "Onboarded. Subdomain: ".$result['school']->subdomainUrl();
        if ($invitation && ! empty($result['admin']->email)) {
            try {
                $mailer->send($invitation, $result['invite_token'], $result['school']);
                $status .= ' Invitation emailed to '.$result['admin']->email.'.';
            } catch (RuntimeException $e) {
                $status .= ' Invitation created, but email could not be sent: '.$e->getMessage();
            }
        } elseif ($invitation) {
            $status .= ' Invitation created — deliver the activation link out-of-band (no email on file).';
        }

        return redirect()->route('platform.schools.show', $result['school'])
            ->with('status', $status);
    }

    public function show(School $school) {
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

    public function update(Request $request, School $school) {
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'district' => 'nullable|string|max:120',
            'emis_number' => 'nullable|string|max:60',
            'theme' => 'required|string|in:'.implode(',', array_keys(config('themes.themes', []))),
            'status' => 'required|in:pending,active,suspended,archived',
        ]);

        $school->update($data);
        $this->audit->record('school.updated', $school, $data);

        return back()->with('status', 'School details saved.');
    }

    public function enter(Request $request, School $school) {
        $request->session()->put('platform.entered_school_id', $school->id);
        $this->audit->record('school.entered', $school, ['slug'=>$school->slug]);
        return redirect()->route('platform.workspace')
            ->with('status', 'Working in '.$school->name.'. You can enter students, classes, and staff for this school.');
    }

    public function leave(Request $request) {
        $request->session()->forget('platform.entered_school_id');
        return redirect()->route('platform.schools.index')
            ->with('status', 'Left school workspace.');
    }
}
