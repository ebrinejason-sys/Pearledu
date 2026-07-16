<?php
namespace App\Http\Controllers\Platform;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolInvitation;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller {
    public function index() {
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

        return view('platform.dashboard', compact('stats', 'schools'));
    }
}
