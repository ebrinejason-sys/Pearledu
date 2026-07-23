<?php
namespace App\Http\Middleware;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Route guard: `permission:sms.send` or OR-list `permission:a,b`. */
class RequirePermission {
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next, string ...$permissions): Response {
        $u = $request->user();
        $schoolId = $this->context->schoolId();
        abort_unless($u && $schoolId && $permissions !== [], 403);

        $have = $u->permissionsForSchool($schoolId);
        $ok = false;
        foreach ($permissions as $permission) {
            if (in_array($permission, $have, true)) {
                $ok = true;
                break;
            }
        }
        abort_unless($ok, 403);

        return $next($request);
    }
}
