<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\HelpdeskTicket;
use App\Models\School;
use App\Models\User;
use App\Services\Platform\PlatformStaffService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemController extends Controller
{
    public function index(TenantContext $context, PlatformStaffService $staff)
    {
        $context->forPlatform();

        $platformStaff = User::query()
            ->where('is_platform', true)
            ->orderBy('full_name')
            ->get()
            ->map(function (User $user) use ($staff) {
                $user->platform_role = $staff->resolvedRoleKey($user);

                return $user;
            });

        $stats = [
            'schools_active' => School::where('status', 'active')->count(),
            'schools_attention' => School::whereIn('status', ['suspended', 'archived'])->count(),
            'staff_active' => $platformStaff->where('status', 'active')->count(),
            'staff_disabled' => $platformStaff->where('status', 'disabled')->count(),
            'staff_without_2fa' => $platformStaff
                ->where('status', 'active')
                ->filter(fn (User $user) => ! $user->hasTwoFactorEnabled())
                ->count(),
            'staff_misconfigured' => $platformStaff->whereNull('platform_role')->count(),
            'open_tickets' => HelpdeskTicket::where('status', '!=', 'closed')->count(),
            'urgent_tickets' => HelpdeskTicket::where('status', '!=', 'closed')->where('priority', 'urgent')->count(),
            'active_sessions' => Schema::hasTable('sessions') ? DB::table('sessions')->whereNotNull('user_id')->count() : 0,
            'queued_jobs' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0,
            'failed_jobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
            'audit_24h' => AuditLog::where('created_at', '>=', now()->subDay())->count(),
        ];

        $recentSensitive = AuditLog::query()
            ->with(['actor:id,full_name,email', 'school:id,name'])
            ->whereIn('action', [
                'platform.staff.created',
                'platform.staff.updated',
                'platform.staff.deleted',
                'platform.staff.password_reset',
                'platform.staff.two_factor_reset',
                'platform.staff.force_logout',
                'school.deletion_scheduled',
                'school.deletion_restored',
                'user.impersonation.started',
                'user.impersonation.write_attempted',
            ])
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('platform.system.index', compact('stats', 'platformStaff', 'recentSensitive'));
    }
}
