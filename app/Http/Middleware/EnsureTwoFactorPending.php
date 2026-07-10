<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorPending
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('2fa_pending_user_id')) {
            return redirect('/login');
        }

        return $next($request);
    }
}
