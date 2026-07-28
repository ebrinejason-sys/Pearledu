<?php
namespace App\Http\Middleware;
use App\Models\School;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Platform school-data routes require an entered school scope. */
class EnsurePlatformSchoolEntered
{
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->session()->get('platform.entered_school_id');
        // Permission checks run under platform RLS (EnsurePlatformOperator).
        $school = $id ? School::query()->find($id) : null;
        $canEnterNonActive = $request->user()
            ?->hasPlatformPermission('platform.schools.enter_suspended') ?? false;

        $isUsable = $school && (
            $school->status === 'active'
            || ($school->status === 'suspended' && $canEnterNonActive)
        ) && $school->status !== 'deletion_scheduled';

        if (! $isUsable) {
            $request->session()->forget('platform.entered_school_id');

            return redirect()
                ->route('platform.schools.index')
                ->withErrors([
                    'school' => $school
                        ? 'This school is no longer active and cannot be entered.'
                        : 'Enter a school first to manage its students, classes, and staff.',
                ]);
        }

        $request->attributes->set('entered_school_id', (int) $id);
        // Narrow RLS only for workspace data entry (students/classes/staff).
        $this->context->forPlatformInSchool((int) $id);

        return $next($request);
    }
}
