<?php
namespace App\Http\Middleware;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard: authenticated user must belong to the resolved school.
 * If Host has no tenant (e.g. pearledu.voxsign.co.ug after invite accept),
 * pin the user's primary school so /home is not a false 404.
 */
class RequireSchoolMembership {
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response {
        $user = $request->user();
        abort_unless($user, 403);

        $schoolId = $this->context->schoolId();

        if ($schoolId === null && ! $user->isPlatformOperator()) {
            $school = $user->primarySchool();
            if ($school) {
                $this->context->forSchool((int) $school->id);
                $schoolId = (int) $school->id;
            }
        }

        abort_if($schoolId === null, 404);
        abort_unless(
            $user->activeAssignments()->where('school_id', $schoolId)->exists(),
            403
        );

        return $next($request);
    }
}
