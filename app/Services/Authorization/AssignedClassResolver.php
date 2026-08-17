<?php

namespace App\Services\Authorization;

use App\Models\Role;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

/** Resolves class IDs from teaching assignments and homeroom (class teacher) bindings. */
class AssignedClassResolver
{
    /**
     * Effective teaching assignments for the school's current academic year.
     *
     * @return Collection<int, TeachingAssignment>
     */
    public function teachingAssignments(User $user, int $schoolId): Collection
    {
        return TeachingAssignment::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $user->id)
            ->forCurrentYear($schoolId)
            ->effective()
            ->where(function ($q) {
                $q->whereNull('term_id')
                    ->orWhereHas('term', function ($tq) {
                        $tq->where(fn ($inner) => $inner->whereNull('starts_on')->orWhereDate('starts_on', '<=', now()))
                            ->where(fn ($inner) => $inner->whereNull('ends_on')->orWhereDate('ends_on', '>=', now()));
                    });
            })
            ->get(['id', 'user_id', 'school_id', 'subject_id', 'class_id', 'academic_year_id', 'term_id']);
    }

    /** @return list<int> */
    public function teachingClassIds(User $user, int $schoolId): array
    {
        return $this->teachingAssignments($user, $schoolId)
            ->pluck('class_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /** @return list<int> */
    public function classTeacherClassIds(User $user, int $schoolId): array
    {
        return $user->activeAssignments()
            ->where('school_id', $schoolId)
            ->whereNotNull('class_id')
            ->whereHas('role', fn ($q) => $q->where('key', Role::CLASS_TEACHER))
            ->pluck('class_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /** Teaching classes ∪ homeroom classes. */
    /** @return list<int> */
    public function assignedClassIds(User $user, int $schoolId): array
    {
        $ids = [];
        foreach ($this->teachingClassIds($user, $schoolId) as $id) {
            $ids[$id] = true;
        }
        foreach ($this->classTeacherClassIds($user, $schoolId) as $id) {
            $ids[$id] = true;
        }

        return array_map('intval', array_keys($ids));
    }

    public function isSchoolWide(User $user, int $schoolId): bool
    {
        return $user->activeAssignments()
            ->where('school_id', $schoolId)
            ->whereHas('role', fn ($q) => $q->whereIn('key', Role::SCHOOL_WIDE))
            ->exists();
    }
}
