<?php

namespace App\Jobs\Middleware;

use App\Services\Tenancy\TenantContext;
use Closure;

/** Re-pin RLS GUCs for the duration of a queued job. */
class RestoreTenantContext
{
    public function __construct(
        public ?int $schoolId,
        public bool $isPlatform = false,
    ) {}

    public function handle(object $job, Closure $next): mixed
    {
        $ctx = app(TenantContext::class);

        if ($this->isPlatform) {
            $ctx->forPlatform();
        } elseif ($this->schoolId) {
            $ctx->forSchool($this->schoolId);
        } else {
            $ctx->clear();
        }

        try {
            return $next($job);
        } finally {
            $ctx->clear();
        }
    }
}
