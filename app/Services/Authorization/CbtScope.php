<?php

namespace App\Services\Authorization;

use App\Models\TeachingAssignment;
use App\Models\User;

/**
 * CBT authoring follows teaching assignments. School-wide managers are unrestricted.
 */
class CbtScope
{
    public function __construct(private AssignedClassResolver $assigned) {}

    public function canManageSchoolWide(User $user, int $schoolId): bool
    {
        return $this->has($user, $schoolId, 'cbt.manage') && $this->assigned->isSchoolWide($user, $schoolId);
    }

    public function canManageAnywhere(User $user, int $schoolId): bool
    {
        return $this->has($user, $schoolId, 'cbt.manage');
    }

    public function canWrite(User $user, int $schoolId, ?int $classId, ?int $subjectId): bool
    {
        if (! $this->canManageAnywhere($user, $schoolId)) {
            return false;
        }
        if ($this->canManageSchoolWide($user, $schoolId)) {
            return true;
        }
        if (! $classId || ! $subjectId) {
            return false;
        }

        return $this->assigned->teachingAssignments($user, $schoolId)
            ->contains(fn (TeachingAssignment $a) => (int) $a->class_id === $classId
                && (int) $a->subject_id === $subjectId);
    }

    /**
     * @return list<int>|null
     */
    public function writableClassIds(User $user, int $schoolId): ?array
    {
        if ($this->canManageSchoolWide($user, $schoolId)) {
            return null;
        }
        if (! $this->canManageAnywhere($user, $schoolId)) {
            return [];
        }

        return $this->assigned->teachingClassIds($user, $schoolId);
    }

    private function has(User $user, int $schoolId, string $permission): bool
    {
        return in_array($permission, $user->permissionsForSchool($schoolId), true);
    }
}
