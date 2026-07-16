<?php
namespace App\Http\Controllers\Platform;
use App\Http\Controllers\Controller;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolInvitation;
use App\Models\Student;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function show(Request $request)
    {
        $school = School::findOrFail($request->session()->get('platform.entered_school_id'));

        $stats = [
            'students' => Student::query()->count(),
            'classes' => SchoolClass::query()->count(),
            'staff' => RoleAssignment::query()
                ->where('school_id', $school->id)
                ->where('is_active', true)
                ->pluck('user_id')
                ->unique()
                ->count(),
            'open_invites' => SchoolInvitation::query()
                ->where('school_id', $school->id)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->count(),
            'sms_balance' => $school->smsBalance(),
            'provisioning' => $school->provisioningState(),
        ];

        return view('platform.workspace.show', compact('school', 'stats'));
    }
}
