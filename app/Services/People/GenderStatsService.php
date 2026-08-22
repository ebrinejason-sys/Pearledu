<?php

namespace App\Services\People;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
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
     * EMIS-style school census: learners, teaching vs non-teaching staff, NIN, nationality, class×sex.
     *
     * @return array<string, mixed>
     */
    public function emisOverview(School $school): array
    {
        $learners = $this->countStudents($school);
        $staffSplit = $this->countStaffByKind($school);
        $nin = $this->learnerNinCounts($school);

        return [
            'learners' => [
                'total' => $learners[Gender::MALE] + $learners[Gender::FEMALE] + $learners['unspecified'],
                'male' => $learners[Gender::MALE],
                'female' => $learners[Gender::FEMALE],
                'unspecified' => $learners['unspecified'],
            ],
            'teaching' => $staffSplit['teaching'],
            'non_teaching' => $staffSplit['non_teaching'],
            'nin' => $nin,
            'nationality' => $this->learnerNationality($school),
            'enrollment' => $this->enrollmentByClassAndSex($school),
        ];
    }

    /**
     * @return array{teaching: array<string, int>, non_teaching: array<string, int>}
     */
    public function countStaffByKind(School $school): array
    {
        $assignments = RoleAssignment::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->whereIn('key', Role::STAFF))
            ->with('role')
            ->get();

        $teachingIds = [];
        $staffIds = [];
        foreach ($assignments as $row) {
            $uid = (int) $row->user_id;
            $staffIds[$uid] = true;
            if (in_array($row->role?->key, [Role::SUBJECT_TEACHER, Role::CLASS_TEACHER, Role::DIRECTOR_OF_STUDIES], true)) {
                $teachingIds[$uid] = true;
            }
        }

        $nonTeachingIds = array_diff(array_keys($staffIds), array_keys($teachingIds));
        $genders = User::query()->whereIn('id', array_keys($staffIds))->pluck('gender', 'id');

        return [
            'teaching' => $this->tally($genders->only(array_keys($teachingIds))->values()),
            'non_teaching' => $this->tally($genders->only($nonTeachingIds)->values()),
        ];
    }

    /**
     * @return array{with: int, without: int}
     */
    public function learnerNinCounts(School $school): array
    {
        $base = Student::query()->where('school_id', $school->id)->where('status', 'active');
        $with = (clone $base)->whereNotNull('nin')->where('nin', '!=', '')->count();
        $total = (clone $base)->count();

        return ['with' => $with, 'without' => max(0, $total - $with)];
    }

    /**
     * @return list<array{label: string, count: int, pct: float}>
     */
    public function learnerNationality(School $school): array
    {
        $rows = Student::query()
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->selectRaw("COALESCE(NULLIF(trim(nationality), ''), 'Unspecified') as label, count(*) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->get();

        $sum = max(1, (int) $rows->sum('total'));

        return $rows->map(fn ($row) => [
            'label' => (string) $row->label,
            'count' => (int) $row->total,
            'pct' => round(((int) $row->total / $sum) * 100, 1),
        ])->all();
    }

    /**
     * @return list<array{label: string, male: int, female: int, total: int}>
     */
    public function enrollmentByClassAndSex(School $school): array
    {
        $classes = SchoolClass::query()
            ->where('school_id', $school->id)
            ->orderBy('level')
            ->orderBy('name')
            ->orderBy('stream')
            ->get();

        $counts = Student::query()
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->selectRaw('class_id, gender, count(*) as total')
            ->groupBy('class_id', 'gender')
            ->get()
            ->groupBy('class_id');

        $out = [];
        foreach ($classes as $class) {
            $byGender = $counts->get($class->id, collect());
            $male = (int) $byGender->firstWhere('gender', Gender::MALE)?->total;
            $female = (int) $byGender->firstWhere('gender', Gender::FEMALE)?->total;
            $unspecified = (int) $byGender->reject(fn ($row) => in_array($row->gender, Gender::keys(), true))->sum('total');
            $total = $male + $female + $unspecified;
            if ($total === 0) {
                continue;
            }
            $out[] = [
                'label' => $class->displayName(),
                'male' => $male,
                'female' => $female,
                'total' => $total,
            ];
        }

        return $out;
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
