<?php
namespace App\Http\Controllers\Platform;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolInvitation;
use App\Models\SmsMessage;
use App\Models\Student;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller {
    public function index(Request $request, TenantContext $context) {
        $enteredId = $request->session()->get('platform.entered_school_id');
        $enteredSchool = $enteredId ? School::find($enteredId) : null;

        // Org-wide totals need platform RLS, even if the operator has entered a school.
        $context->forPlatform();

        $stats = [
            'schools'         => School::count(),
            'active'          => School::where('status', 'active')->count(),
            'learners'        => DB::table('students')->whereNull('deleted_at')->count(),
            'operators'       => User::where('is_platform', true)->count(),
            'pending_invites' => SchoolInvitation::query()
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->count(),
            'sms_sent'        => SmsMessage::where('status', 'sent')->count(),
            'staff_invites'   => SchoolInvitation::query()
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->where('role_key', '!=', 'parent')
                ->count(),
        ];

        $schools = School::query()
            ->withCount([
                'students' => fn ($q) => $q->whereNull('deleted_at'),
                'invitations as pending_invites_count' => fn ($q) => $q
                    ->whereNull('accepted_at')
                    ->where('expires_at', '>', now()),
            ])
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $workspaceStats = null;
        if ($enteredSchool) {
            $context->forPlatformInSchool((int) $enteredSchool->id);
            $workspaceStats = [
                'students' => Student::query()->count(),
                'classes' => SchoolClass::query()->count(),
                'open_invites' => SchoolInvitation::query()
                    ->where('school_id', $enteredSchool->id)
                    ->whereNull('accepted_at')
                    ->where('expires_at', '>', now())
                    ->count(),
            ];
        }

        return view('platform.dashboard', compact('stats', 'schools', 'enteredSchool', 'workspaceStats'));
    }
}
