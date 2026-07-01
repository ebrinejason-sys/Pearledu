<?php
namespace App\Http\Middleware;
use App\Models\School;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Resolve tenant from Host; pin RLS context. Fail-closed for unknown hosts. */
class ResolveTenant {
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response {
        $base = config('tenancy.base_domain');
        $host = $request->getHost();

        if (in_array($host, config('tenancy.landing_hosts'), true)) {
            $request->attributes->set('is_landing', true);
            $this->context->clear();
            return $next($request);
        }

        $label = $this->label($host, $base);
        if ($label === null || in_array($label, config('tenancy.platform_subdomains'), true)) {
            $this->context->clear();                     // platform host
            return $next($request);
        }

        $school = School::where('slug', $label)->where('status', 'active')->first()
            ?? optional(School::query()->whereIn('id', \App\Models\SchoolDomain::query()
                    ->where('domain', $host)->whereNotNull('verified_at')->pluck('school_id'))->first());

        $school ? $this->context->forSchool($school->id) : $this->context->clear();
        return $next($request);
    }

    private function label(string $host, string $base): ?string {
        if ($host === $base || ! str_ends_with($host, '.'.$base)) return null;
        return substr($host, 0, -1 * (strlen($base) + 1));
    }
}
