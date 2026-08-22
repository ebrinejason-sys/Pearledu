<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\StaffTimePunch;
use App\Models\User;
use App\Services\Hr\StaffClockService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class StaffClockController extends Controller
{
    public function scan(Request $request, TenantContext $ctx): View
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        $today = StaffTimePunch::query()
            ->where('school_id', $school->id)
            ->whereDate('punched_at', now(config('app.timezone'))->toDateString())
            ->with('user')
            ->orderByDesc('punched_at')
            ->limit(40)
            ->get();

        return view('app.staff.clock', [
            'school' => $school,
            'punches' => $today,
            'canMark' => $this->canMark($request, $school->id),
        ]);
    }

    public function punch(Request $request, TenantContext $ctx, StaffClockService $clock): RedirectResponse
    {
        $school = $ctx->school();
        abort_unless($school && $request->user(), 404);
        abort_unless($this->canMark($request, $school->id), 403);

        $data = $request->validate([
            'code' => 'required|string|max:40',
        ]);

        try {
            $punch = $clock->punchByCode($school, $data['code'], $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        $verb = $punch->direction === StaffTimePunch::IN ? 'in' : 'out';
        $when = $punch->punched_at->timezone(config('app.timezone'))->format('H:i');
        $name = $punch->user instanceof User ? $punch->user->full_name : 'Staff';

        return back()->with('status', $name.' clocked '.$verb.' at '.$when);
    }

    public function history(Request $request, TenantContext $ctx): View
    {
        $school = $ctx->school();
        abort_unless($school, 404);

        $userId = $request->integer('user_id') ?: null;
        $punches = StaffTimePunch::query()
            ->where('school_id', $school->id)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->with(['user', 'recorder'])
            ->orderByDesc('punched_at')
            ->limit(200)
            ->get();

        $staff = User::query()
            ->whereIn(
                'id',
                RoleAssignment::query()
                    ->where('school_id', $school->id)
                    ->where('is_active', true)
                    ->whereHas('role', fn ($q) => $q->whereIn('key', Role::STAFF))
                    ->pluck('user_id')
            )
            ->orderBy('full_name')
            ->get();

        return view('app.staff.clock-history', compact('school', 'punches', 'staff', 'userId'));
    }

    private function canMark(Request $request, int $schoolId): bool
    {
        $user = $request->user();

        return $user && in_array('staff.attendance.mark', $user->permissionsForSchool($schoolId), true);
    }
}
