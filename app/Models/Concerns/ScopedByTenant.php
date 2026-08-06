<?php

namespace App\Models\Concerns;

/**
 * Eloquent half of tenant isolation for tables that may have null school_id
 * (platform role assignments, global audit rows). Does NOT auto-fill school_id.
 * Matches RLS: unconstrained when platform-scoped; school_id filter otherwise.
 */
trait ScopedByTenant
{
    public static function bootScopedByTenant(): void
    {
        static::addGlobalScope(new TenantScope);
    }
}
