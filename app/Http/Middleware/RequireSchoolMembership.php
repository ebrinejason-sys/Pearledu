<?php
namespace App\Http\Middleware;
use App\Models\School;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard: authenticated user must belong to the resolved active school tenant.
 * On the shared pearledu host, pin the user's school from session / primary membership.
 */
class RequireSchoolMembership {
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response {
        $user = $request->user();
        abort_unless($user, 403);

        // Platform operators use /admin — never the school /home surface.
        if ($user->isPlatformOperator()) {
            return redirect()->route('platform.dashboard');
        }

        $schoolId = $this->context->schoolId();

        if ($schoolId === null) {
            $sessionId = session(TenantContext::SESSION_SCHOOL_ID);
            if ($sessionId
                && $user->activeAssignments()->where('school_id', (int) $sessionId)->exists()
                && School::where('id', (int) $sessionId)->where('status', 'active')->exists()) {
                $this->context->forSchool((int) $sessionId);
                $schoolId = (int) $sessionId;
            } elseif ($school = $user->primarySchool()) {
                session([TenantContext::SESSION_SCHOOL_ID => $school->tenantId()]);
                $this->context->forSchool($school->tenantId());
                $schoolId = $school->tenantId();
            }
        }

        if ($schoolId === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['identifier' => 'No active school is linked to this account. Contact PearlEdu support.']);
        }

        $schoolOk = School::where('id', $schoolId)->where('status', 'active')->exists();
        abort_unless($schoolOk, 403, 'This school is not active.');

        abort_unless(
            $user->activeAssignments()->where('school_id', $schoolId)->exists(),
            403
        );

        return $next($request);
    }
}
