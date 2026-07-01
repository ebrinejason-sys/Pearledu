<?php
namespace App\Services\Audit;
use App\Models\AuditLog;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
class AuditLogger {
    public function record(string $action, ?Model $entity = null, array $metadata = []): void {
        AuditLog::create([
            'school_id'   => app(TenantContext::class)->schoolId(),
            'actor_id'    => Auth::id(),
            'action'      => $action,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id'   => $entity?->getKey(),
            'metadata'    => $metadata,
            'ip_address'  => Request::ip(),
            'created_at'  => now(),
        ]);
    }
}
