<?php

namespace App\Services\People;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Support\Gender;
use Illuminate\Support\Collection;

class GenderStatsService
{
    /**
     * @return array{learners: array<string, int>, staff: array<string, int>}
     */
    public function forSchool(School $school): array
    {
        return [
            'learners' => $this->countStudents($school),
            'staff' => $this->countStaff($school),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function countStudents(School $school, ?int $classId = null): array
    {
        $query = Student::query()
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->when($classId, fn ($q) => $q->where('class_id', $classId));

        return $this->tally($query->pluck('gender'));
    }

    /**
     * @return array<string, int>
     */
    public function countStaff(School $school): array
    {
        $userIds = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->whereIn('key', Role::STAFF))
            ->pluck('user_id')
            ->unique();

        return $this->tally(User::query()->whereIn('id', $userIds)->pluck('gender'));
    }

    /**
     * @param  Collection<int, string|null>  $values
     * @return array<string, int>
     */
    private function tally($values): array
    {
        $counts = [
            Gender::MALE => 0,
            Gender::FEMALE => 0,
            'unspecified' => 0,
        ];
        foreach ($values as $value) {
            if ($value === Gender::MALE || $value === Gender::FEMALE) {
                $counts[$value]++;
            } else {
                $counts['unspecified']++;
            }
        }

        return $counts;
    }
}
