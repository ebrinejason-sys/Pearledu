<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\HelpdeskTicket;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolInvitation;
use App\Models\SmsMessage;
use App\Models\Student;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request, TenantContext $context)
    {
        $enteredId = $request->session()->get('platform.entered_school_id');
        $enteredSchool = $enteredId ? School::find($enteredId) : null;

        $context->forPlatform();

        $stats = [
            'schools' => School::count(),
            'active' => School::where('status', 'active')->count(),
            'suspended' => School::whereIn('status', ['suspended', 'archived'])->count(),
            'learners' => DB::table('students')->whereNull('deleted_at')->count(),
            'operators' => User::where('is_platform', true)->where('status', 'active')->count(),
            'pending_invites' => SchoolInvitation::query()
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->count(),
            'sms_sent' => SmsMessage::where('status', 'sent')->count(),
            'staff_invites' => SchoolInvitation::query()
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->where('role_key', '!=', 'parent')
                ->count(),
            'tickets_open' => HelpdeskTicket::where('status', '!=', 'closed')->count(),
            'tickets_unassigned' => HelpdeskTicket::whereNull('assigned_to')->where('status', '!=', 'closed')->count(),
            'tickets_urgent' => HelpdeskTicket::where('status', '!=', 'closed')->where('priority', 'urgent')->count(),
        ];

        $schools = School::query()
            ->withCount([
                'students' => fn ($q) => $q->whereNull('deleted_at'),
                'invitations as pending_invites_count' => fn ($q) => $q
                    ->whereNull('accepted_at')
                    ->where('expires_at', '>', now()),
            ])
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $recentTickets = HelpdeskTicket::query()
            ->with(['school', 'user'])
            ->where('status', '!=', 'closed')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $workspaceStats = null;
        if ($enteredSchool) {
            $workspaceStats = [
                'students' => Student::query()->where('school_id', $enteredSchool->id)->count(),
                'classes' => SchoolClass::query()->where('school_id', $enteredSchool->id)->count(),
                'open_invites' => SchoolInvitation::query()
                    ->where('school_id', $enteredSchool->id)
                    ->whereNull('accepted_at')
                    ->where('expires_at', '>', now())
                    ->count(),
            ];
        }

        // Stay on platform RLS so dashboard nav/permission composers keep working.
        $context->forPlatform();

        return view('platform.dashboard', compact(
            'stats',
            'schools',
            'enteredSchool',
            'workspaceStats',
            'recentTickets',
        ));
    }
}
