<?php

namespace App\Services\Provisioning;

use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

/**
 * Tenant lifecycle: schedule deletion → optional restore → permanent purge.
 * Immediate hard-delete is available as purge() for the operator UI and the retention job.
 */
class SchoolDeletionService
{
    /** Default retention before automatic purge is allowed (days). */
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
            throw new RuntimeException('This school is already scheduled for deletion. Restore it, or delete it permanently.');
        }

        $this->context->forPlatform();
        $this->assertDeletionColumns();

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
        $this->assertDeletionColumns();

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
    public function purge(School $school, ?User $actor = null, bool $force = false): array
    {
        return DB::transaction(function () use ($school, $actor, $force) {
            $this->context->forPlatform();

            if (! $force && $school->status === 'deletion_scheduled' && $school->deletion_scheduled_at) {
                $eligibleAt = $school->deletion_scheduled_at->copy()->addDays(self::RETENTION_DAYS);
                if ($eligibleAt->isFuture() && $actor && ! $actor->hasPlatformPermission('platform.system.manage')) {
                    throw new RuntimeException(
                        'Retention period has not elapsed. Wait until '.$eligibleAt->toDateString()
                        .' or delete permanently from the school page.'
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
            $this->breakCircularReferences($schoolId);

            try {
                $school->delete();
            } catch (Throwable $e) {
                throw new RuntimeException(
                    'Database refused to remove this school: '.$e->getMessage(),
                    (int) $e->getCode(),
                    $e
                );
            }

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
                'forced' => $force,
            ], actor: $actor);

            return $payload;
        });
    }

    /**
     * Schools whose retention window has elapsed.
     *
     * @return Collection<int, School>
     */
    public function dueForPurge()
    {
        $this->context->forPlatform();

        if (! Schema::hasColumn('schools', 'deletion_scheduled_at')) {
            return School::query()->whereRaw('1 = 0')->get();
        }

        return School::query()
            ->where('status', 'deletion_scheduled')
            ->whereNotNull('deletion_scheduled_at')
            ->where('deletion_scheduled_at', '<=', now()->subDays(self::RETENTION_DAYS))
            ->orderBy('id')
            ->get();
    }

    /**
     * @deprecated Use schedule() for operator UI; purge() for final removal.
     *
     * @return array{tenant_id:int,name:string,slug:string,users_removed:int}
     */
    public function delete(School $school): array
    {
        return $this->purge($school, force: true);
    }

    private function assertDeletionColumns(): void
    {
        foreach (['deletion_scheduled_at', 'deletion_requested_by', 'deletion_reason'] as $column) {
            if (! Schema::hasColumn('schools', $column)) {
                throw new RuntimeException(
                    'School deletion columns are missing. Run `php artisan migrate --force` on the server.'
                );
            }
        }
    }

    /**
     * Composite tenant FKs (school_id, parent_id) can block CASCADE from schools
     * depending on delete order. Null the nullable ones first.
     */
    private function breakCircularReferences(int $schoolId): void
    {
        $clears = [
            ['students', 'class_id'],
            ['announcements', 'class_id'],
            ['admission_applications', 'requested_class_id'],
            ['admission_applications', 'student_id'],
            ['fee_payments', 'reverses_payment_id'],
            ['assessment_periods', 'grading_scheme_id'],
        ];

        foreach ($clears as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)->where('school_id', $schoolId)->update([$column => null]);
        }
    }
}
