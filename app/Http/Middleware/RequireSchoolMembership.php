<?php
namespace App\Http\Middleware;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Route guard: the authenticated user must have an active RoleAssignment at the resolved tenant. */
class RequireSchoolMembership {
    public function __construct(private TenantContext $context) {}
    public function handle(Request $request, Closure $next): Response {
        $schoolId = $this->context->schoolId();
        abort_if($schoolId === null, 404);
        abort_unless(
            $request->user()->activeAssignments()->where('school_id', $schoolId)->exists(),
            403
        );
        return $next($request);
    }
}
