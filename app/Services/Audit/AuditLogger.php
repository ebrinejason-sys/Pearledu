<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  User|null  $actor  Explicit security actor (e.g. impersonation operator before Auth::login switch).
     */
    public function record(string $action, ?Model $entity = null, array $metadata = [], ?User $actor = null): void
    {
        $ctx = app(TenantContext::class);
        // Platform-global events (e.g. login on /login) have no school scope; RLS still
        // requires app.is_platform for writes with a null school_id.
        $elevated = false;
        if (! $ctx->isPlatform() && $ctx->schoolId() === null) {
            $ctx->forPlatform();
            $elevated = true;
        }
        try {
            AuditLog::create([
                'school_id' => $ctx->schoolId(),
                'actor_id' => $actor?->id ?? Auth::id(),
                'action' => $action,
                'entity_type' => $entity ? class_basename($entity) : null,
                'entity_id' => $entity?->getKey(),
                'metadata' => $metadata,
                'ip_address' => Request::ip(),
                'created_at' => now(),
            ]);
        } finally {
            if ($elevated) {
                $ctx->clear();
            }
        }
    }
}
