<?php

namespace App\Services\Authorization;

use App\Models\Student;
use App\Models\User;

/**
 * learners.manage = create/update/archive (school-wide for SCHOOL_WIDE roles).
 * learners.view = profile read, scoped to assigned classes for teachers.
 */
class LearnerScope
{
    public function __construct(private AssignedClassResolver $assigned) {}

    public function canViewAnywhere(User $user, int $schoolId): bool
    {
        return $this->has($user, $schoolId, 'learners.manage')
            || $this->has($user, $schoolId, 'learners.view');
    }

    public function canMutateAnywhere(User $user, int $schoolId): bool
    {
        return $this->has($user, $schoolId, 'learners.manage');
    }

    /**
     * Classes whose learner rows are visible. Null = unrestricted. Empty = none.
     *
     * @return list<int>|null
     */
    public function viewableClassIds(User $user, int $schoolId): ?array
    {
        if (! $this->canViewAnywhere($user, $schoolId)) {
            return [];
        }

        if ($this->assigned->isSchoolWide($user, $schoolId)) {
            return null;
        }

        return $this->assigned->assignedClassIds($user, $schoolId);
    }

    public function canViewStudent(User $user, int $schoolId, Student $student): bool
    {
        if ((int) $student->school_id !== $schoolId) {
            return false;
        }

        $ids = $this->viewableClassIds($user, $schoolId);
        if ($ids === null) {
            return true;
        }

        $classId = $student->class_id !== null ? (int) $student->class_id : 0;

        return $classId !== 0 && in_array($classId, $ids, true);
    }

    public function canMutateStudent(User $user, int $schoolId, Student $student): bool
    {
        return $this->canMutateAnywhere($user, $schoolId)
            && $this->canViewStudent($user, $schoolId, $student);
    }

    public function canLinkGuardian(User $user, int $schoolId, Student $student): bool
    {
        if (! $this->canViewStudent($user, $schoolId, $student)) {
            return false;
        }

        return $this->canMutateAnywhere($user, $schoolId)
            || $this->has($user, $schoolId, 'users.invite.parent');
    }

    private function has(User $user, int $schoolId, string $permission): bool
    {
        return in_array($permission, $user->permissionsForSchool($schoolId), true);
    }
}
