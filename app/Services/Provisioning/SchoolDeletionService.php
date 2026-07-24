<?php

namespace App\Services\Provisioning;

use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * Permanently remove a school tenant and cascaded school-owned rows.
 * Also soft-deletes orphaned non-platform users left with no memberships.
 */
class SchoolDeletionService
{
    public function __construct(
        private TenantContext $context,
        private AuditLogger $audit,
    ) {}

    /** @return array{tenant_id:int,name:string,slug:string,users_removed:int} */
    public function delete(School $school): array
    {
        return DB::transaction(function () use ($school) {
            $this->context->forPlatform();

            $payload = [
                'tenant_id' => $school->tenantId(),
                'name' => $school->name,
                'slug' => $school->slug,
                'users_removed' => 0,
            ];

            $memberIds = RoleAssignment::query()
                ->where('school_id', $school->id)
                ->pluck('user_id')
                ->unique()
                ->all();

            $schoolId = $school->id;
            $school->delete();

            $removed = 0;
            foreach ($memberIds as $userId) {
                $user = User::withTrashed()->find($userId);
                if (! $user || $user->is_platform) {
                    continue;
                }

                $stillLinked = RoleAssignment::query()->where('user_id', $user->id)->exists()
                    || $user->guardianships()->exists();

                if ($stillLinked) {
                    continue;
                }

                if (! $user->trashed()) {
                    $user->delete();
                    $removed++;
                }
            }

            $payload['users_removed'] = $removed;
            $this->audit->record('school.deleted', null, $payload + ['school_id' => $schoolId]);

            return $payload;
        });
    }
}
