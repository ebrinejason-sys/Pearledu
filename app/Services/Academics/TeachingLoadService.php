<?php

namespace App\Services\Academics;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Subject × class teaching load for one staff member.
 *
 * One person may hold many rows (English → P5 East, Biology → S3 West).
 * The timetable generator already reads these rows and periods_per_week.
 */
class TeachingLoadService
{
    public const DEFAULT_PERIODS = 3;

    public const MIN_PERIODS = 1;

    public const MAX_PERIODS = 20;

    public function __construct(private CurrentAcademicContext $academic) {}

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array{starts_on?: ?string, ends_on?: ?string}  $meta
     * @return int Number of (subject, class) pairs written
     */
    public function sync(
        School $school,
        User $user,
        array $rows,
        ?int $academicYearId = null,
        ?int $termId = null,
        bool $requireNonEmpty = true,
        array $meta = [],
    ): int {
        $pairs = $this->pairsFromRows($rows);

        if ($pairs === [] && $requireNonEmpty) {
            throw ValidationException::withMessages([
                'teaching_assignments' => 'Choose what this person teaches and which class (or classes). One staff member may have many subject–class rows so the timetable does not collide.',
            ]);
        }

        if ($pairs === []) {
            return 0;
        }

        $year = $this->resolveYear($school, $academicYearId);
        $resolvedTermId = $this->resolveTermId($school, $year, $termId);

        $written = 0;
        foreach ($pairs as $pair) {
            $this->assertSchoolOwned($school, $pair['subject_id'], $pair['class_id']);

            TeachingAssignment::query()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'user_id' => $user->id,
                    'subject_id' => $pair['subject_id'],
                    'class_id' => $pair['class_id'],
                    'academic_year_id' => $year->id,
                    'term_id' => $resolvedTermId,
                ],
                [
                    'status' => 'active',
                    'periods_per_week' => $pair['periods_per_week'],
                    'starts_on' => $meta['starts_on'] ?? null,
                    'ends_on' => $meta['ends_on'] ?? null,
                ],
            );
            $written++;
        }

        return $written;
    }

    public function hasCurrentLoad(School $school, User $user, ?int $academicYearId = null): bool
    {
        $yearId = $academicYearId
            ?? AcademicYear::query()
                ->where('school_id', $school->id)
                ->where('is_current', true)
                ->value('id');

        if (! $yearId) {
            return false;
        }

        return TeachingAssignment::query()
            ->where('school_id', $school->id)
            ->where('user_id', $user->id)
            ->where('academic_year_id', $yearId)
            ->effective()
            ->exists();
    }

    /**
     * Class × subject occupancy for the timetable planner.
     *
     * @return array{
     *   year: ?AcademicYear,
     *   classes: Collection<int, SchoolClass>,
     *   subjects: Collection<int, Subject>,
     *   cells: array<int, array<int, list<array{assignment_id: int, user_id: int, teacher: string, periods: int}>>>,
     *   collisions: int,
     *   teacherCards: list<array{user_id: int, name: string, initials: string, total_periods: int, items: list<array{assignment_id: int, subject: string, class: string, periods: int}>}>
     * }
     */
    public function occupancy(School $school, ?int $academicYearId = null): array
    {
        $year = $academicYearId
            ? AcademicYear::query()->where('school_id', $school->id)->whereKey($academicYearId)->first()
            : AcademicYear::query()->where('school_id', $school->id)->where('is_current', true)->first();

        $classes = SchoolClass::query()
            ->where('school_id', $school->id)
            ->orderBy('name')
            ->orderBy('stream')
            ->get();
        $subjects = Subject::query()
            ->where('school_id', $school->id)
            ->orderBy('name')
            ->get();

        $assignments = TeachingAssignment::query()
            ->where('school_id', $school->id)
            ->when(
                $year,
                fn ($q) => $q->where('academic_year_id', $year->id),
                fn ($q) => $q->whereRaw('0 = 1'),
            )
            ->effective()
            ->with(['teacher', 'subject', 'schoolClass'])
            ->orderBy('id')
            ->get();

        $cells = [];
        $collisions = 0;
        $byTeacher = [];

        foreach ($assignments as $assignment) {
            $subjectId = (int) $assignment->subject_id;
            $classId = (int) $assignment->class_id;
            $teacher = $assignment->teacher;
            $teacherName = 'Staff';
            $initials = '?';
            if ($teacher instanceof User) {
                $teacherName = $teacher->full_name !== '' ? $teacher->full_name : 'Staff';
                $initials = $teacher->avatarInitial();
            }
            $entry = [
                'assignment_id' => (int) $assignment->id,
                'user_id' => (int) $assignment->user_id,
                'teacher' => $teacherName,
                'periods' => max(self::MIN_PERIODS, (int) ($assignment->periods_per_week ?: self::DEFAULT_PERIODS)),
            ];
            $cells[$subjectId][$classId][] = $entry;

            $teacherId = (int) $assignment->user_id;
            if (! isset($byTeacher[$teacherId])) {
                $byTeacher[$teacherId] = [
                    'user_id' => $teacherId,
                    'name' => $teacherName,
                    'initials' => $initials,
                    'total_periods' => 0,
                    'items' => [],
                ];
            }
            $byTeacher[$teacherId]['total_periods'] += $entry['periods'];
            $byTeacher[$teacherId]['items'][] = [
                'assignment_id' => $entry['assignment_id'],
                'subject' => $assignment->subject?->name ?: 'Subject',
                'class' => $assignment->schoolClass instanceof SchoolClass
                    ? $assignment->schoolClass->displayName()
                    : 'Class',
                'periods' => $entry['periods'],
            ];
        }

        foreach ($cells as $byClass) {
            foreach ($byClass as $entries) {
                $teacherIds = array_unique(array_column($entries, 'user_id'));
                if (count($teacherIds) > 1) {
                    $collisions++;
                }
            }
        }

        $teacherCards = collect($byTeacher)
            ->sortBy(fn (array $card) => strtolower($card['name']))
            ->values()
            ->all();

        return [
            'year' => $year,
            'classes' => $classes,
            'subjects' => $subjects,
            'cells' => $cells,
            'collisions' => $collisions,
            'teacherCards' => $teacherCards,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{subject_id: int, class_id: int, periods_per_week: int}>
     */
    public function pairsFromRows(array $rows): array
    {
        $pairs = [];
        $seen = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $subjectId = (int) ($row['subject_id'] ?? 0);
            $classIds = $row['class_ids'] ?? [];
            if (! is_array($classIds)) {
                $classIds = [$classIds];
            }
            if ($classIds === [] && isset($row['class_id'])) {
                $classIds = [$row['class_id']];
            }
            if ($subjectId <= 0) {
                continue;
            }

            $periods = (int) ($row['periods_per_week'] ?? self::DEFAULT_PERIODS);
            if ($periods < self::MIN_PERIODS) {
                $periods = self::DEFAULT_PERIODS;
            }
            if ($periods > self::MAX_PERIODS) {
                $periods = self::MAX_PERIODS;
            }

            foreach ($classIds as $classId) {
                $id = (int) $classId;
                if ($id <= 0) {
                    continue;
                }
                $key = $subjectId.':'.$id;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $pairs[] = [
                    'subject_id' => $subjectId,
                    'class_id' => $id,
                    'periods_per_week' => $periods,
                ];
            }
        }

        return $pairs;
    }

    private function resolveYear(School $school, ?int $academicYearId): AcademicYear
    {
        if ($academicYearId) {
            $year = AcademicYear::query()
                ->where('school_id', $school->id)
                ->whereKey($academicYearId)
                ->first();
            if ($year) {
                return $year;
            }
        }

        $year = AcademicYear::query()
            ->where('school_id', $school->id)
            ->where('is_current', true)
            ->first()
            ?? $this->academic->year();

        if (! $year || (int) $year->school_id !== (int) $school->id) {
            throw ValidationException::withMessages([
                'teaching_assignments' => 'Set a current academic year before assigning subjects to classes.',
            ]);
        }

        return $year;
    }

    private function resolveTermId(School $school, AcademicYear $year, ?int $termId): ?int
    {
        if (! $termId) {
            return null;
        }

        $ok = Term::query()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->whereKey($termId)
            ->exists();

        if (! $ok) {
            throw ValidationException::withMessages([
                'term_id' => 'Selected term must belong to the academic year.',
            ]);
        }

        return $termId;
    }

    private function assertSchoolOwned(School $school, int $subjectId, int $classId): void
    {
        $subjectOk = Subject::query()
            ->where('school_id', $school->id)
            ->whereKey($subjectId)
            ->exists();
        $classOk = SchoolClass::query()
            ->where('school_id', $school->id)
            ->whereKey($classId)
            ->exists();

        if (! $subjectOk || ! $classOk) {
            throw ValidationException::withMessages([
                'teaching_assignments' => 'Each teaching assignment must use a subject and class from this school.',
            ]);
        }
    }
}
