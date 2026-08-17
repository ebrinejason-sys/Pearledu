<?php

namespace App\Services\Provisioning;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Authorization\InvitePolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Sync active school role assignments for a staff member. */
class StaffRoleService
{
    public function __construct(
        private InvitePolicy $policy,
        private AuditLogger $audit,
    ) {}

    /**
     * @param  list<string>  $roleKeys
     */
    public function sync(School $school, User $target, array $roleKeys, User $actor, bool $asPlatform = false, ?int $classId = null): void
    {
        if ($target->is_platform) {
            throw ValidationException::withMessages(['user' => 'Platform accounts are managed under PearlEdu staff.']);
        }

        $roleKeys = array_values(array_unique($roleKeys));
        if ($roleKeys === []) {
            throw ValidationException::withMessages(['role_keys' => 'Select at least one role, or revoke access instead.']);
        }

        $allowed = $this->policy->rolesInvitableBy($actor, $school->id, $asPlatform);
        foreach ($roleKeys as $key) {
            $already = RoleAssignment::query()
                ->where('user_id', $target->id)
                ->where('school_id', $school->id)
                ->where('is_active', true)
                ->whereHas('role', fn ($q) => $q->where('key', $key))
                ->exists();

            if (! $already && ! in_array($key, $allowed, true)) {
                throw ValidationException::withMessages(['role_keys' => "You cannot assign the role [{$key}]."]);
            }
        }

        $roleIdsByKey = Role::query()->whereIn('key', $roleKeys)->pluck('id', 'key');
        foreach ($roleKeys as $key) {
            if (! isset($roleIdsByKey[$key])) {
                throw ValidationException::withMessages(['role_keys' => "Unknown role [{$key}]."]);
            }
        }

        $wantsHomeroom = in_array(Role::CLASS_TEACHER, $roleKeys, true);
        $classTeacherRoleId = Role::query()->where('key', Role::CLASS_TEACHER)->value('id');
        $existingHomeroom = RoleAssignment::query()
            ->where('user_id', $target->id)
            ->where('school_id', $school->id)
            ->where('role_id', $classTeacherRoleId)
            ->where('is_active', true)
            ->first();

        if ($wantsHomeroom) {
            $resolvedClassId = $classId ?: ($existingHomeroom?->class_id ? (int) $existingHomeroom->class_id : null);
            if (! $resolvedClassId) {
                throw ValidationException::withMessages(['class_id' => 'Choose a homeroom class for the class teacher.']);
            }
            $classId = $resolvedClassId;
        }

        DB::transaction(function () use ($school, $target, $roleKeys, $roleIdsByKey, $actor, $classId) {
            $keepIds = $roleIdsByKey->values()->all();

            RoleAssignment::query()
                ->where('user_id', $target->id)
                ->where('school_id', $school->id)
                ->where('is_active', true)
                ->whereNotIn('role_id', $keepIds)
                ->update(['is_active' => false, 'ends_on' => now()]);

            foreach ($roleKeys as $key) {
                $roleId = $roleIdsByKey[$key];
                $assignment = RoleAssignment::query()
                    ->where('user_id', $target->id)
                    ->where('role_id', $roleId)
                    ->where('school_id', $school->id)
                    ->where('is_active', true)
                    ->first();

                if ($assignment) {
                    if ($key === Role::CLASS_TEACHER && $classId) {
                        $assignment->update(['class_id' => $classId, 'assigned_by' => $actor->id]);
                    }

                    continue;
                }

                RoleAssignment::create([
                    'user_id' => $target->id,
                    'role_id' => $roleId,
                    'school_id' => $school->id,
                    'class_id' => $key === Role::CLASS_TEACHER ? $classId : null,
                    'is_active' => true,
                    'assigned_by' => $actor->id,
                ]);
            }
        });

        $this->audit->record('staff.roles_updated', $target, [
            'school_id' => $school->id,
            'role_keys' => $roleKeys,
            'class_id' => $wantsHomeroom ? $classId : null,
            'actor_id' => $actor->id,
        ], actor: $actor);
    }

    public function revoke(School $school, User $target, User $actor): void
    {
        if ((int) $target->id === (int) $actor->id) {
            throw ValidationException::withMessages(['user' => 'You cannot revoke your own school access here.']);
        }

        RoleAssignment::query()
            ->where('user_id', $target->id)
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->update(['is_active' => false, 'ends_on' => now()]);

        $this->audit->record('staff.access_revoked', $target, [
            'school_id' => $school->id,
            'actor_id' => $actor->id,
        ], actor: $actor);
    }
}
