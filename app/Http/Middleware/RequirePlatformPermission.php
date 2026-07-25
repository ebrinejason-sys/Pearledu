<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Route guard: `platform.permission:platform.schools.view` or OR-list. */
class RequirePlatformPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        abort_unless($user && $user->isPlatformOperator(), 403);
        abort_unless($permissions !== [], 403);

        $ok = false;
        foreach ($permissions as $permission) {
            if ($user->hasPlatformPermission($permission)) {
                $ok = true;
                break;
            }
        }
        abort_unless($ok, 403);

        return $next($request);
    }
}
