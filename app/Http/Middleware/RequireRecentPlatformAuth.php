<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Require recent password confirmation for destructive platform actions.
 * Session key: platform.recent_auth_at (unix timestamp).
 */
class RequireRecentPlatformAuth
{
    public const SESSION_KEY = 'platform.recent_auth_at';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user && $user->isPlatformOperator(), 403);

        $confirmedAt = (int) $request->session()->get(self::SESSION_KEY, 0);
        $minutes = (int) config('permissions.platform_recent_auth_minutes', 15);
        $fresh = $confirmedAt > 0 && (time() - $confirmedAt) <= ($minutes * 60);

        if (! $fresh) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()
                ->route('platform.auth.confirm')
                ->with('status', 'Confirm your password to continue with this sensitive action.');
        }

        return $next($request);
    }

    public static function markConfirmed(Request $request): void
    {
        $request->session()->put(self::SESSION_KEY, time());
    }
}
