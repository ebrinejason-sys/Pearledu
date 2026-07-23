<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Services\Platform\ImpersonationService;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * After session/auth are available, pin school RLS from the logged-in user
 * so pearledu.* can serve every school without a subdomain.
 */
class PinAuthenticatedTenant
{
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Operator console keeps platform RLS (entered-school middleware re-pins).
        if ($request->is('admin', 'admin/*', 'console', 'console/*', 'platform', 'platform/*')) {
            return $next($request);
        }

        if ($id = session(ImpersonationService::SESSION_SCHOOL)) {
            $this->context->forSchool((int) $id);

            return $next($request);
        }

        // Subdomain hosts already resolved a school in ResolveTenant.
        if ($this->context->hasSchool() && ! $this->context->isPlatform()) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user || $user->isPlatformOperator()) {
            return $next($request);
        }

        $sessionSchoolId = session(TenantContext::SESSION_SCHOOL_ID);
        if ($sessionSchoolId
            && $user->activeAssignments()->where('school_id', (int) $sessionSchoolId)->exists()
            && School::where('id', (int) $sessionSchoolId)->where('status', 'active')->exists()) {
            $this->context->forSchool((int) $sessionSchoolId);

            return $next($request);
        }

        if ($school = $user->primarySchool()) {
            session([TenantContext::SESSION_SCHOOL_ID => $school->tenantId()]);
            $this->context->forSchool($school->tenantId());
        }

        return $next($request);
    }
}
