<?php

namespace App\Http\Middleware;

use App\Services\Audit\AuditLogger;
use App\Services\Auth\IdleSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Sign out abandoned sessions even when a remember-me cookie is present. */
class EnforceIdleSession
{
    public function __construct(
        private IdleSessionService $idle,
        private AuditLogger $audit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($this->idle->isExpired($user)) {
            $this->audit->record('auth.idle_timeout', $user, [
                'minutes' => $this->idle->lifetimeMinutes(),
            ], actor: $user);
            $this->idle->expire($request, $user);

            $message = $this->idle->expiryMessage();
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 401);
            }

            return redirect()->route('login')->with('status', $message);
        }

        $this->idle->touch($user);

        return $next($request);
    }
}
