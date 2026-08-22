<?php

namespace App\Services\Authorization;

use App\Models\Role;
use App\Models\User;

/**
 * Hierarchical invite matrix: who may invite which school roles.
 * Platform operators may invite any school role (emergency / onboarding).
 */
class InvitePolicy
{
    /** @var array<string, list<string>> */
    public const MATRIX = [
        'school_admin' => [
            'director', 'head_teacher', 'deputy_head_teacher', 'director_of_studies', 'bursar', 'secretary',
            'class_teacher', 'subject_teacher', 'parent', 'student',
        ],
        'director' => [
            'head_teacher', 'deputy_head_teacher', 'director_of_studies', 'bursar', 'secretary',
            'class_teacher', 'subject_teacher', 'parent', 'student',
        ],
        'head_teacher' => [
            'director_of_studies', 'secretary', 'class_teacher', 'subject_teacher', 'parent', 'student',
        ],
        'secretary' => [],
        'deputy_head_teacher' => [
            'class_teacher', 'subject_teacher', 'parent', 'student',
        ],
        'director_of_studies' => [
            'class_teacher', 'subject_teacher', 'parent', 'student',
        ],
        'class_teacher' => [
            'parent',
        ],
        'bursar' => [],
        'subject_teacher' => [],
        'parent' => [],
        'student' => [],
    ];

    /** Roles the platform console may invite (school onboarding + emergency). */
    public const PLATFORM_INVITABLE = [
        'school_admin',
        'director',
        'head_teacher',
        'deputy_head_teacher',
        'director_of_studies',
        'bursar',
        'secretary',
        'class_teacher',
        'subject_teacher',
        'parent',
        'student',
    ];

    /**
     * @return list<string>
     */
    public function rolesInvitableBy(User $inviter, int $schoolId, bool $asPlatform = false): array
    {
        if ($asPlatform || $inviter->isPlatformOperator()) {
            return self::PLATFORM_INVITABLE;
        }

        $keys = $inviter->activeAssignments()
            ->where('school_id', $schoolId)
            ->with('role')
            ->get()
            ->pluck('role.key')
            ->filter()
            ->unique()
            ->all();

        $allowed = [];
        foreach ($keys as $key) {
            foreach (self::MATRIX[$key] ?? [] as $role) {
                $allowed[$role] = true;
            }
        }

        return array_keys($allowed);
    }

    public function canInvite(User $inviter, string $roleKey, int $schoolId, bool $asPlatform = false): bool
    {
        return in_array($roleKey, $this->rolesInvitableBy($inviter, $schoolId, $asPlatform), true);
    }

    /**
     * Staff roles the person currently holds in this school.
     *
     * @return list<string>
     */
    public function staffRoleKeys(User $user, int $schoolId): array
    {
        return $user->activeAssignments()
            ->where('school_id', $schoolId)
            ->with('role')
            ->get()
            ->pluck('role.key')
            ->filter(fn ($key) => is_string($key) && in_array($key, Role::STAFF, true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Leadership may administer staff whose every school role they are allowed to invite.
     * School admin may administer anyone on the staff list. Nobody administers themselves here
     * (own details stay on Account).
     */
    public function canAdminister(User $actor, User $subject, int $schoolId): bool
    {
        if ((int) $actor->id === (int) $subject->id) {
            return false;
        }

        $subjectKeys = $this->staffRoleKeys($subject, $schoolId);
        if ($subjectKeys === []) {
            return false;
        }

        $actorKeys = $actor->activeAssignments()
            ->where('school_id', $schoolId)
            ->with('role')
            ->get()
            ->pluck('role.key')
            ->filter()
            ->unique()
            ->all();

        if (in_array(Role::SCHOOL_ADMIN, $actorKeys, true)) {
            return true;
        }

        $invitable = $this->rolesInvitableBy($actor, $schoolId);

        foreach ($subjectKeys as $key) {
            if (! in_array($key, $invitable, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Who may update biodata/photo/documents. Secretary keeps school-wide staff files.
     * Other actors need staff.manage or staff.profile.update AND hierarchy.
     */
    public function canEditStaffProfile(User $actor, User $subject, int $schoolId): bool
    {
        if ((int) $actor->id === (int) $subject->id) {
            return true;
        }

        $perms = $actor->permissionsForSchool($schoolId);
        $actorKeys = $actor->activeAssignments()
            ->where('school_id', $schoolId)
            ->with('role')
            ->get()
            ->pluck('role.key')
            ->filter()
            ->unique()
            ->all();

        if (in_array(Role::SECRETARY, $actorKeys, true) && in_array('staff.profile.update', $perms, true)) {
            return true;
        }

        if (in_array(Role::SCHOOL_ADMIN, $actorKeys, true)) {
            return true;
        }

        if (! in_array('staff.manage', $perms, true) && ! in_array('staff.profile.update', $perms, true)) {
            return false;
        }

        return $this->canAdminister($actor, $subject, $schoolId);
    }
}
