<?php
namespace App\Http\Middleware;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Route guard: `permission:sms.send`. Checks the union of the user's active roles. */
class RequirePermission {
    public function __construct(private TenantContext $context) {}
    public function handle(Request $request, Closure $next, string $permission): Response {
        $u = $request->user();
        $schoolId = $this->context->schoolId();
        abort_unless($u && $schoolId && in_array($permission, $u->permissionsForSchool($schoolId), true), 403);
        return $next($request);
    }
}
