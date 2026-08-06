<?php

use App\Http\Middleware\BlockImpersonationWrites;
use App\Http\Middleware\EnsurePlatformOperator;
use App\Http\Middleware\EnsurePlatformSchoolEntered;
use App\Http\Middleware\EnsureTwoFactorPending;
use App\Http\Middleware\PinAuthenticatedTenant;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\RequirePlatformPermission;
use App\Http\Middleware\RequireRecentPlatformAuth;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            require __DIR__.'/../routes/auth.php';
            require __DIR__.'/../routes/platform.php';
            require __DIR__.'/../routes/app.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // cPanel / reverse-proxy TLS termination — required for HTTPS detection + secure cookies.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->web(prepend: [ResolveTenant::class]);
        // After StartSession + auth: pin school from auth/session on the shared pearledu host.
        $middleware->web(append: [
            PinAuthenticatedTenant::class,
            BlockImpersonationWrites::class,
        ]);
        // Critical: pin school RLS before implicit model binding. Otherwise pearledu.* stays
        // platform-scoped during SubstituteBindings and school users can IDOR other tenants.
        $middleware->prependToPriorityList(
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            PinAuthenticatedTenant::class,
        );
        $middleware->alias([
            'platform' => EnsurePlatformOperator::class,
            'platform.school' => EnsurePlatformSchoolEntered::class,
            'platform.permission' => RequirePlatformPermission::class,
            'platform.recent_auth' => RequireRecentPlatformAuth::class,
            'permission' => RequirePermission::class,
            '2fa.pending' => EnsureTwoFactorPending::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {})
    ->create();
