<?php

namespace App\Services\Authorization;

use App\Models\User;

/**
 * attendance.manage = school-wide register.
 * attendance.mark without manage = assigned teaching / homeroom classes.
 * attendance.view without mark = school-wide read (director oversight).
 */
class AttendanceScope
{
    public function __construct(private AssignedClassResolver $assigned) {}

    public function canManage(User $user, int $schoolId): bool
    {
        return $this->has($user, $schoolId, 'attendance.manage');
    }

    public function canMarkAnywhere(User $user, int $schoolId): bool
    {
        return $this->canManage($user, $schoolId)
            || $this->has($user, $schoolId, 'attendance.mark');
    }

    public function canViewAnywhere(User $user, int $schoolId): bool
    {
        return $this->canManage($user, $schoolId)
            || $this->has($user, $schoolId, 'attendance.mark')
            || $this->has($user, $schoolId, 'attendance.view');
    }

    public function canMarkClass(User $user, int $schoolId, int $classId): bool
    {
        $ids = $this->markableClassIds($user, $schoolId);

        return $ids === null || in_array($classId, $ids, true);
    }

    public function canViewClass(User $user, int $schoolId, int $classId): bool
    {
        $ids = $this->viewableClassIds($user, $schoolId);

        return $ids === null || in_array($classId, $ids, true);
    }

    /**
     * Null = unrestricted. Empty = none.
     *
     * @return list<int>|null
     */
    public function markableClassIds(User $user, int $schoolId): ?array
    {
        if ($this->canManage($user, $schoolId)) {
            return null;
        }

        if (! $this->has($user, $schoolId, 'attendance.mark')) {
            return [];
        }

        return $this->assigned->assignedClassIds($user, $schoolId);
    }

    /**
     * Null = unrestricted. Empty = none.
     *
     * @return list<int>|null
     */
    public function viewableClassIds(User $user, int $schoolId): ?array
    {
        if ($this->canManage($user, $schoolId)) {
            return null;
        }

        if ($this->has($user, $schoolId, 'attendance.mark')) {
            return $this->assigned->assignedClassIds($user, $schoolId);
        }

        if ($this->has($user, $schoolId, 'attendance.view')) {
            return null;
        }

        return [];
    }

    private function has(User $user, int $schoolId, string $permission): bool
    {
        return in_array($permission, $user->permissionsForSchool($schoolId), true);
    }
}
