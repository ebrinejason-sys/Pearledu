<?php

namespace App\Services\Authorization;

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
            'director', 'head_teacher', 'deputy_head_teacher', 'bursar',
            'class_teacher', 'subject_teacher', 'parent', 'student',
        ],
        'director' => [
            'head_teacher', 'deputy_head_teacher', 'bursar',
            'class_teacher', 'subject_teacher', 'parent', 'student',
        ],
        'head_teacher' => [
            'class_teacher', 'subject_teacher', 'parent', 'student',
        ],
        'deputy_head_teacher' => [
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
        'bursar',
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
}
