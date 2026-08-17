<?php

namespace App\Services\Authorization;

use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Permission answers “what”; assignments answer “where”.
 * assessment.manage = school-wide. Otherwise scope from teaching / class-teacher assignments.
 */
class AssessmentScope
{
    public function __construct(private AssignedClassResolver $assigned) {}

    public function canManage(User $user, int $schoolId): bool
    {
        return $this->hasPermission($user, $schoolId, 'assessment.manage');
    }

    public function canEnterAnywhere(User $user, int $schoolId): bool
    {
        return $this->canManage($user, $schoolId)
            || $this->hasPermission($user, $schoolId, 'assessment.enter');
    }

    public function canViewAnywhere(User $user, int $schoolId): bool
    {
        return $this->canManage($user, $schoolId)
            || $this->hasPermission($user, $schoolId, 'assessment.view')
            || $this->hasPermission($user, $schoolId, 'assessment.enter');
    }

    public function canEnter(User $user, int $schoolId, int $classId, int $subjectId): bool
    {
        if ($this->canManage($user, $schoolId)) {
            return true;
        }

        if (! $this->hasPermission($user, $schoolId, 'assessment.enter')) {
            return false;
        }

        return $this->teachingAssignments($user, $schoolId)
            ->contains(fn (TeachingAssignment $a) => (int) $a->class_id === $classId
                && (int) $a->subject_id === $subjectId);
    }

    public function canViewClass(User $user, int $schoolId, int $classId): bool
    {
        if ($this->canManage($user, $schoolId)) {
            return true;
        }

        $viewable = $this->viewableClassIds($user, $schoolId);

        return $viewable === null || in_array($classId, $viewable, true);
    }

    /**
     * Class IDs the user may select for mark entry. Null = unrestricted (manager).
     *
     * @return list<int>|null
     */
    public function enterableClassIds(User $user, int $schoolId): ?array
    {
        if ($this->canManage($user, $schoolId)) {
            return null;
        }

        if (! $this->hasPermission($user, $schoolId, 'assessment.enter')) {
            return [];
        }

        return $this->teachingAssignments($user, $schoolId)
            ->pluck('class_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Subject IDs enterable for a class. Null = unrestricted.
     *
     * @return list<int>|null
     */
    public function enterableSubjectIds(User $user, int $schoolId, int $classId): ?array
    {
        if ($this->canManage($user, $schoolId)) {
            return null;
        }

        if (! $this->hasPermission($user, $schoolId, 'assessment.enter')) {
            return [];
        }

        return $this->teachingAssignments($user, $schoolId)
            ->where('class_id', $classId)
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Classes visible on broadsheet / report cards. Null = unrestricted.
     *
     * @return list<int>|null
     */
    public function viewableClassIds(User $user, int $schoolId): ?array
    {
        if ($this->canManage($user, $schoolId)) {
            return null;
        }

        if ($this->assigned->isSchoolWide($user, $schoolId)
            && $this->hasPermission($user, $schoolId, 'assessment.view')) {
            return null;
        }

        if (! $this->canViewAnywhere($user, $schoolId)) {
            return [];
        }

        $ids = [];

        foreach ($this->teachingAssignments($user, $schoolId) as $assignment) {
            $ids[(int) $assignment->class_id] = true;
        }

        if ($this->hasPermission($user, $schoolId, 'assessment.view')) {
            foreach ($this->assigned->classTeacherClassIds($user, $schoolId) as $classId) {
                $ids[$classId] = true;
            }
        }

        return array_map('intval', array_keys($ids));
    }

    /**
     * Effective teaching assignments for the school's current academic year.
     *
     * @return Collection<int, TeachingAssignment>
     */
    public function teachingAssignments(User $user, int $schoolId): Collection
    {
        return $this->assigned->teachingAssignments($user, $schoolId);
    }

    private function hasPermission(User $user, int $schoolId, string $permission): bool
    {
        return in_array($permission, $user->permissionsForSchool($schoolId), true);
    }
}
