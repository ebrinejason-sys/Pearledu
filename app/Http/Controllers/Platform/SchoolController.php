<?php
namespace App\Http\Controllers\Platform;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\OnboardSchoolRequest;
use App\Models\School;
use App\Services\Audit\AuditLogger;
use App\Services\Provisioning\SchoolProvisioner;
use Illuminate\Http\Request;

class SchoolController extends Controller {
    public function __construct(private AuditLogger $audit) {}

    public function index() {
        $schools = School::withCount('students')->orderBy('name')->get();
        return view('platform.schools.index', compact('schools'));
    }

    public function create() {
        return view('platform.schools.create', ['themes'=>config('themes.themes')]);
    }

    public function store(OnboardSchoolRequest $request, SchoolProvisioner $provisioner) {
        $result = $provisioner->onboard(
            school: $request->only(['name','district','emis_number','theme']),
            levels: $request->input('levels'),
            admin:  $request->input('admin'),
            operatorId: $request->user()->id,
        );
        // Deliver $result['invite_token'] to the contact person via mail/SMS (queued).
        return redirect()->route('platform.schools.show', $result['school'])
            ->with('status', "Onboarded. Subdomain: ".$result['school']->subdomainUrl());
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
        return view('platform.schools.show', compact('school', 'members'));
    }

    public function enter(Request $request, School $school) {
        $request->session()->put('platform.entered_school_id', $school->id);
        $this->audit->record('school.entered', $school, ['slug'=>$school->slug]);
        return redirect()->route('platform.schools.show', $school);
    }

    public function leave(Request $request) {
        $request->session()->forget('platform.entered_school_id');
        return redirect()->route('platform.schools.index');
    }
}
