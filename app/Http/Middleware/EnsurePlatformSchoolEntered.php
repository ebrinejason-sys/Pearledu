<?php
namespace App\Http\Middleware;
use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Platform school-data routes require an entered school scope. */
class EnsurePlatformSchoolEntered
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->session()->get('platform.entered_school_id');
        if (! $id || ! School::query()->whereKey($id)->exists()) {
            $request->session()->forget('platform.entered_school_id');

            return redirect()
                ->route('platform.schools.index')
                ->with('status', 'Enter a school first to manage its students, classes, and staff.');
        }

        $request->attributes->set('entered_school_id', (int) $id);

        return $next($request);
    }
}
