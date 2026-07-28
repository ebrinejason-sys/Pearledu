<?php

namespace App\Http\Middleware;

use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformOperator
{
    public function __construct(
        private TenantContext $context,
        private AuditLogger $audit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $u = $request->user();
        abort_unless($u && $u->isPlatformOperator(), 403);

        // Fail closed: platform flag without an active platform role is a misconfigured account.
        // Never assign roles here (GET-safe).
        if (! $u->platformRoleKey()) {
            if (! $request->session()->get('platform.misconfigured_logged')) {
                $this->context->forPlatform();
                $this->audit->record('platform.account.misconfigured', $u, [
                    'path' => $request->path(),
                    'reason' => 'is_platform without active platform role assignment',
                ], actor: $u);
                $request->session()->put('platform.misconfigured_logged', true);
            }
            abort(403, 'This PearlEdu account is misconfigured. Ask a Platform Admin to assign a role.');
        }

        if ($u->status !== 'active') {
            abort(403, 'This PearlEdu account is disabled.');
        }

        // Operator console always uses platform RLS so permission checks and
        // cross-school bindings work. Workspace routes re-pin via platform.school.
        $this->context->forPlatform();

        return $next($request);
    }
}
