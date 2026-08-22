<?php

namespace App\Services\Authorization;

use App\Models\Student;
use App\Models\User;

/**
 * learners.manage = create/update/archive (school-wide for SCHOOL_WIDE roles).
 * learners.view = profile read, scoped to assigned classes for teachers.
 * learners.profile.update = homeroom bio/photo/restream only (not archive or school-wide).
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

    public function canEditListed(User $user, int $schoolId): bool
    {
        return $this->canMutateAnywhere($user, $schoolId)
            || $this->has($user, $schoolId, 'learners.profile.update');
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

    /**
     * Homeroom teachers may update bio/photo/restream for their assigned class only.
     */
    public function canEditProfile(User $user, int $schoolId, Student $student): bool
    {
        if ($this->canMutateStudent($user, $schoolId, $student)) {
            return true;
        }

        if (! $this->has($user, $schoolId, 'learners.profile.update')) {
            return false;
        }

        if ((int) $student->school_id !== $schoolId) {
            return false;
        }

        $homeroom = $this->assigned->classTeacherClassIds($user, $schoolId);
        $classId = $student->class_id !== null ? (int) $student->class_id : 0;

        return $classId !== 0 && in_array($classId, $homeroom, true);
    }

    public function canRestreamTo(User $user, int $schoolId, Student $student, int $targetClassId): bool
    {
        if (! $this->canEditProfile($user, $schoolId, $student)) {
            return false;
        }

        $from = $student->schoolClass;
        if (! $from) {
            return $this->canMutateStudent($user, $schoolId, $student);
        }

        if ((int) $from->id === $targetClassId) {
            return true;
        }

        if ($this->canMutateStudent($user, $schoolId, $student)) {
            return true;
        }

        return $from->siblingStreams()->contains(fn ($c) => (int) $c->id === $targetClassId);
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
