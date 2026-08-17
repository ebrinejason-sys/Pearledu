<?php

namespace App\Services\Authorization;

use App\Models\TeachingAssignment;
use App\Models\User;

/**
 * LMS writes follow teaching assignments. School-wide managers (DOS / school admin) are unrestricted.
 */
class LmsScope
{
    public function __construct(private AssignedClassResolver $assigned) {}

    public function canManageSchoolWide(User $user, int $schoolId): bool
    {
        return $this->has($user, $schoolId, 'lms.manage') && $this->assigned->isSchoolWide($user, $schoolId);
    }

    public function canManageAnywhere(User $user, int $schoolId): bool
    {
        return $this->has($user, $schoolId, 'lms.manage');
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
     * @return list<int>|null Null = unrestricted
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

    /**
     * @return list<int>|null
     */
    public function writableSubjectIds(User $user, int $schoolId, int $classId): ?array
    {
        if ($this->canManageSchoolWide($user, $schoolId)) {
            return null;
        }
        if (! $this->canManageAnywhere($user, $schoolId)) {
            return [];
        }

        return $this->assigned->teachingAssignments($user, $schoolId)
            ->where('class_id', $classId)
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function has(User $user, int $schoolId, string $permission): bool
    {
        return in_array($permission, $user->permissionsForSchool($schoolId), true);
    }
}
