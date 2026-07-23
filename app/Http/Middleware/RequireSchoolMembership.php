<?php
namespace App\Http\Middleware;
use App\Models\School;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
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

        $schoolId = $this->context->schoolId();

        if ($schoolId === null && ! $user->isPlatformOperator()) {
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

        abort_if($schoolId === null, 404);

        $schoolOk = School::where('id', $schoolId)->where('status', 'active')->exists();
        abort_unless($schoolOk, 403, 'This school is not active.');

        abort_unless(
            $user->activeAssignments()->where('school_id', $schoolId)->exists(),
            403
        );

        return $next($request);
    }
}
