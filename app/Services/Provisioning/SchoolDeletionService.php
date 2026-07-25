<?php

namespace App\Services\Provisioning;

use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Tenant lifecycle: schedule deletion → optional restore → permanent purge.
 * Immediate hard-delete is retained only as purge() for the retention job / admin override.
 */
class SchoolDeletionService
{
    /** Default retention before purge is allowed (days). */
    public const RETENTION_DAYS = 30;

    public function __construct(
        private TenantContext $context,
        private AuditLogger $audit,
    ) {}

    /**
     * Soft-stage deletion. Does not remove tenant rows yet.
     *
     * @return array{tenant_id:int,name:string,slug:string,purge_after:string}
     */
    public function schedule(School $school, User $actor, ?string $reason = null): array
    {
        if ($school->status === 'deletion_scheduled') {
            throw new RuntimeException('This school is already scheduled for deletion.');
        }

        $this->context->forPlatform();

        $purgeAfter = now()->addDays(self::RETENTION_DAYS);
        $before = $school->status;

        $school->forceFill([
            'status' => 'deletion_scheduled',
            'deletion_scheduled_at' => now(),
            'deletion_requested_by' => $actor->id,
            'deletion_reason' => $reason ? mb_substr(trim($reason), 0, 500) : null,
        ])->save();

        $payload = [
            'tenant_id' => $school->tenantId(),
            'name' => $school->name,
            'slug' => $school->slug,
            'school_id' => $school->id,
            'from_status' => $before,
            'purge_after' => $purgeAfter->toIso8601String(),
            'reason' => $school->deletion_reason,
            'by' => $actor->id,
        ];

        $this->audit->record('school.deletion_scheduled', $school, $payload, actor: $actor);

        return [
            'tenant_id' => $payload['tenant_id'],
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'purge_after' => $payload['purge_after'],
        ];
    }

    /** Restore a school that is still within the retention window. */
    public function restore(School $school, User $actor): void
    {
        if ($school->status !== 'deletion_scheduled') {
            throw new RuntimeException('Only schools scheduled for deletion can be restored.');
        }

        $this->context->forPlatform();

        $school->forceFill([
            'status' => 'suspended',
            'deletion_scheduled_at' => null,
            'deletion_requested_by' => null,
            'deletion_reason' => null,
        ])->save();

        $this->audit->record('school.deletion_restored', $school, [
            'tenant_id' => $school->tenantId(),
            'school_id' => $school->id,
            'restored_to' => 'suspended',
            'by' => $actor->id,
        ], actor: $actor);
    }

    /**
     * Permanently remove a school tenant and cascaded school-owned rows.
     * Also soft-deletes orphaned non-platform users left with no memberships.
     *
     * @return array{tenant_id:int,name:string,slug:string,users_removed:int}
     */
    public function purge(School $school, ?User $actor = null): array
    {
        return DB::transaction(function () use ($school, $actor) {
            $this->context->forPlatform();

            if ($school->status === 'deletion_scheduled' && $school->deletion_scheduled_at) {
                $eligibleAt = $school->deletion_scheduled_at->copy()->addDays(self::RETENTION_DAYS);
                if ($eligibleAt->isFuture() && $actor && ! $actor->hasPlatformPermission('platform.system.manage')) {
                    throw new RuntimeException(
                        'Retention period has not elapsed. Wait until '.$eligibleAt->toDateString().' or use system.manage override.'
                    );
                }
            }

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
            $this->audit->record('school.purged', null, $payload + [
                'school_id' => $schoolId,
                'by' => $actor?->id,
            ], actor: $actor);

            return $payload;
        });
    }

    /**
     * @deprecated Use schedule() for operator UI; purge() for final removal.
     *
     * @return array{tenant_id:int,name:string,slug:string,users_removed:int}
     */
    public function delete(School $school): array
    {
        return $this->purge($school);
    }
}
