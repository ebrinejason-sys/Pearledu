<?php

namespace App\Http\Middleware;

use App\Services\Audit\AuditLogger;
use App\Services\Platform\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impersonation is read-only by default. Mutating requests are blocked unless
 * elevated write mode is active. Stop-impersonation is always allowed.
 */
class BlockImpersonationWrites
{
    public function __construct(
        private ImpersonationService $impersonation,
        private AuditLogger $audit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->impersonation->isActive()) {
            return $next($request);
        }

        if ($request->routeIs('impersonation.stop')) {
            return $next($request);
        }

        if ($request->isMethodSafe()) {
            return $next($request);
        }

        if ($this->impersonation->allowsWrites()) {
            return $next($request);
        }

        $this->audit->record('user.impersonation.write_blocked', $request->user(), [
            'operator_id' => $this->impersonation->operatorId(),
            'method' => $request->method(),
            'path' => $request->path(),
            'reason' => $this->impersonation->reason(),
        ], actor: $this->impersonation->operator());

        abort(403, 'Imitation sessions are read-only. End imitation or request elevated write access.');
    }
}
