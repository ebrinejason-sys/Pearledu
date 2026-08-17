<?php

namespace App\Services\Timetable;

use App\Models\School;
use App\Models\TeachingAssignment;
use App\Models\TimetablePeriod;
use App\Models\TimetableSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fills class periods from teaching assignments.
 *
 * Hard constraints: no teacher double-booking, no class double-booking,
 * only lesson periods (kind=class), only configured teaching days,
 * only teachers/subjects from effective teaching assignments.
 * Soft: spread a subject's periods across different weekdays.
 */
class TimetableGenerator
{
    public function __construct(private TimetableService $slots) {}

    /**
     * @return array{created:int,skipped:int,unplaced:list<string>}
     */
    public function generate(School $school, ?int $classId = null, ?int $academicYearId = null, bool $clearExisting = false): array
    {
        $days = $school->teachingDays();
        $periods = TimetablePeriod::query()
            ->where('school_id', $school->id)
            ->where(function ($q) {
                $q->where('kind', 'class')->orWhereNull('kind');
            })
            ->orderBy('sequence')
            ->orderBy('starts_at')
            ->get();

        if ($periods->isEmpty()) {
            throw ValidationException::withMessages([
                'generate' => 'Add at least one Class period before generating a timetable.',
            ]);
        }

        if ($days === []) {
            throw ValidationException::withMessages([
                'generate' => 'Select at least one teaching day in the week.',
            ]);
        }

        $assignments = TeachingAssignment::query()
            ->where('school_id', $school->id)
            ->effective()
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->with(['teacher', 'subject', 'schoolClass'])
            ->orderBy('class_id')
            ->get();

        if ($assignments->isEmpty()) {
            throw ValidationException::withMessages([
                'generate' => 'No teaching assignments found. Assign staff to subjects and classes first (Teaching).',
            ]);
        }

        return DB::transaction(function () use ($school, $assignments, $days, $periods, $classId, $academicYearId, $clearExisting) {
            if ($clearExisting) {
                TimetableSlot::query()
                    ->where('school_id', $school->id)
                    ->when($classId, fn ($q) => $q->where('class_id', $classId))
                    ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                    ->delete();
            }

            $teacherBusy = [];
            $classBusy = [];
            foreach (TimetableSlot::query()->where('school_id', $school->id)->get(['day_of_week', 'period_id', 'teacher_id', 'class_id']) as $existing) {
                $teacherBusy[$existing->day_of_week][$existing->period_id][$existing->teacher_id] = true;
                $classBusy[$existing->day_of_week][$existing->period_id][$existing->class_id] = true;
            }

            $created = 0;
            $skipped = 0;
            $unplaced = [];

            $ordered = $assignments->sortByDesc(fn ($a) => (int) ($a->periods_per_week ?: 1))->values();

            foreach ($ordered as $assignment) {
                $need = max(1, min(20, (int) ($assignment->periods_per_week ?: 1)));
                $placedDays = [];
                $placed = 0;

                // First pass: prefer spreading across days (soft).
                $dayOrder = $days;
                foreach ($dayOrder as $day) {
                    if ($placed >= $need) {
                        break;
                    }
                    foreach ($periods as $period) {
                        if ($placed >= $need) {
                            break;
                        }
                        if (! empty($teacherBusy[$day][$period->id][$assignment->user_id])) {
                            continue;
                        }
                        if (! empty($classBusy[$day][$period->id][$assignment->class_id])) {
                            continue;
                        }
                        if (($placedDays[$day] ?? 0) >= 1 && count($days) > 1 && $placed + 1 < $need) {
                            continue;
                        }

                        if ($this->tryPlace($school, $assignment, (int) $day, (int) $period->id, $teacherBusy, $classBusy, $placedDays, $placed, $created, $skipped)) {
                            // placed via refs
                        }
                    }
                }

                // Second pass: allow same-day doubles if still short.
                if ($placed < $need) {
                    foreach ($days as $day) {
                        if ($placed >= $need) {
                            break;
                        }
                        foreach ($periods as $period) {
                            if ($placed >= $need) {
                                break;
                            }
                            if (! empty($teacherBusy[$day][$period->id][$assignment->user_id])) {
                                continue;
                            }
                            if (! empty($classBusy[$day][$period->id][$assignment->class_id])) {
                                continue;
                            }
                            $this->tryPlace($school, $assignment, (int) $day, (int) $period->id, $teacherBusy, $classBusy, $placedDays, $placed, $created, $skipped);
                        }
                    }
                }

                if ($placed < $need) {
                    $label = ($assignment->schoolClass?->displayName() ?? 'Class')
                        .' · '.($assignment->subject?->name ?? 'Subject')
                        .' · '.($assignment->teacher?->full_name ?? 'Teacher');
                    $unplaced[] = "{$label}: placed {$placed}/{$need} periods";
                }
            }

            return compact('created', 'skipped', 'unplaced');
        });
    }

    /**
     * @param  array<int, array<int, array<int, bool>>>  $teacherBusy
     * @param  array<int, array<int, array<int, bool>>>  $classBusy
     * @param  array<int, int>  $placedDays
     */
    private function tryPlace(
        School $school,
        TeachingAssignment $assignment,
        int $day,
        int $periodId,
        array &$teacherBusy,
        array &$classBusy,
        array &$placedDays,
        int &$placed,
        int &$created,
        int &$skipped,
    ): bool {
        try {
            $this->slots->storeSlot([
                'school_id' => $school->id,
                'academic_year_id' => $assignment->academic_year_id,
                'day_of_week' => $day,
                'period_id' => $periodId,
                'class_id' => (int) $assignment->class_id,
                'subject_id' => (int) $assignment->subject_id,
                'teacher_id' => (int) $assignment->user_id,
                'room_id' => null,
            ]);
            $teacherBusy[$day][$periodId][$assignment->user_id] = true;
            $classBusy[$day][$periodId][$assignment->class_id] = true;
            $placedDays[$day] = ($placedDays[$day] ?? 0) + 1;
            $placed++;
            $created++;

            return true;
        } catch (ValidationException) {
            $skipped++;

            return false;
        }
    }
}
