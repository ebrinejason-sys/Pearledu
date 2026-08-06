<?php

namespace App\Jobs\Concerns;

use App\Jobs\Middleware\RestoreTenantContext;
use App\Services\Tenancy\TenantContext;

/**
 * Capture the current tenant at dispatch and restore it when the job runs.
 * Every queued job that touches school-scoped data must use this trait.
 */
trait TenantAware
{
    public ?int $tenantSchoolId = null;

    public bool $tenantIsPlatform = false;

    protected function captureTenantContext(): void
    {
        $ctx = app(TenantContext::class);
        $this->tenantSchoolId = $ctx->schoolId();
        $this->tenantIsPlatform = $ctx->isPlatform();
    }

    /** @return list<object> */
    public function middleware(): array
    {
        if ($this->tenantSchoolId === null && ! $this->tenantIsPlatform) {
            $this->captureTenantContext();
        }

        return [
            new RestoreTenantContext($this->tenantSchoolId, $this->tenantIsPlatform),
        ];
    }
}
