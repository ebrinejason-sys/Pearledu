<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Require recent password confirmation for destructive platform actions.
 * Session key: platform.recent_auth_at (unix timestamp).
 *
 * POST/PUT/PATCH/DELETE intents are stashed and resumed after confirm
 * (Laravel's url.intended alone would turn them into a broken GET).
 */
class RequireRecentPlatformAuth
{
    public const SESSION_KEY = 'platform.recent_auth_at';

    public const PENDING_KEY = 'platform.pending_sensitive';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user && $user->isPlatformOperator(), 403);

        $confirmedAt = (int) $request->session()->get(self::SESSION_KEY, 0);
        $minutes = (int) config('permissions.platform_recent_auth_minutes', 15);
        $fresh = $confirmedAt > 0 && (time() - $confirmedAt) <= ($minutes * 60);

        if (! $fresh) {
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                $uri = $request->getRequestUri();
                // Only resume admin console actions — never arbitrary URLs.
                if (str_starts_with($uri, '/admin/') || str_starts_with($uri, '/platform/') || str_starts_with($uri, '/console/')) {
                    $request->session()->put(self::PENDING_KEY, [
                        'uri' => $uri,
                        'method' => $request->method(),
                        'input' => $request->except(['_token', 'password', 'current_password']),
                    ]);
                }
            }

            $request->session()->put(
                'url.intended',
                $request->headers->get('referer') ?: route('platform.dashboard')
            );

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
