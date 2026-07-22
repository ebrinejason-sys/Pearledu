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

        // PearlEdu marketing (/) and the platform console (/platform, /login) share this host.
        // Pin platform RLS here so route-model binding (e.g. /platform/schools/{school})
        // can see rows — SubstituteBindings runs before the `platform` middleware.
        if ($host === config('tenancy.pearledu_landing_host')) {
            $request->attributes->set('is_pearledu_landing', true);
            $this->context->forPlatform();
            return $next($request);
        }

        if ($id = session(\App\Services\Platform\ImpersonationService::SESSION_SCHOOL)) {
            $this->context->forSchool((int) $id);
            return $next($request);
        }

        if ($request->user()?->isPlatformOperator() && $request->routeIs('platform.*')) {
            $this->context->forPlatform();
            return $next($request);
        }

        $label = $this->label($host, $base);
        if ($label === null || in_array($label, config('tenancy.platform_subdomains'), true)) {
            // Platform console hosts must see all schools under RLS for bindings + indexes.
            $this->context->forPlatform();
            return $next($request);
        }

        // Resolve tenant slug with a brief platform pin (RLS otherwise hides schools).
        $this->context->forPlatform();
        $school = School::where('slug', $label)->where('status', 'active')->first()
            ?? School::query()->whereIn('id', \App\Models\SchoolDomain::query()
                ->where('domain', $host)->whereNotNull('verified_at')->pluck('school_id'))->first();

        if ($school?->id) {
            $this->context->forSchool((int) $school->id);
        } else {
            $this->context->clear();
        }

        return $next($request);
    }

    private function label(string $host, string $base): ?string {
        if ($host === $base || ! str_ends_with($host, '.'.$base)) return null;
        return substr($host, 0, -1 * (strlen($base) + 1));
    }
}
