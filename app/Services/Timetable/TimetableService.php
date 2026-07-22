<?php

namespace App\Services\Timetable;

use App\Models\TimetableSlot;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class TimetableService
{
    /**
     * @param  array{
     *   school_id:int,
     *   academic_year_id?:int|null,
     *   day_of_week:int,
     *   period_id:int,
     *   class_id:int,
     *   subject_id:int,
     *   teacher_id:int,
     *   room_id?:int|null
     * }  $data
     */
    public function storeSlot(array $data): TimetableSlot
    {
        $this->assertNoCollisions($data);

        try {
            return TimetableSlot::create($data);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                throw ValidationException::withMessages([
                    'slot' => $this->messageFromUnique($e, $data),
                ]);
            }
            throw $e;
        }
    }

    /** @param array<string, mixed> $data */
    private function assertNoCollisions(array $data): void
    {
        $day = (int) $data['day_of_week'];
        $periodId = (int) $data['period_id'];
        $schoolId = (int) $data['school_id'];

        $classHit = TimetableSlot::query()
            ->where('school_id', $schoolId)
            ->where('day_of_week', $day)
            ->where('period_id', $periodId)
            ->where('class_id', $data['class_id'])
            ->exists();
        if ($classHit) {
            throw ValidationException::withMessages([
                'class_id' => 'This class already has a lesson in that period.',
            ]);
        }

        $teacherHit = TimetableSlot::query()
            ->where('school_id', $schoolId)
            ->where('day_of_week', $day)
            ->where('period_id', $periodId)
            ->where('teacher_id', $data['teacher_id'])
            ->exists();
        if ($teacherHit) {
            throw ValidationException::withMessages([
                'teacher_id' => 'This teacher is already assigned in that period.',
            ]);
        }

        if (! empty($data['room_id'])) {
            $roomHit = TimetableSlot::query()
                ->where('school_id', $schoolId)
                ->where('day_of_week', $day)
                ->where('period_id', $periodId)
                ->where('room_id', $data['room_id'])
                ->exists();
            if ($roomHit) {
                throw ValidationException::withMessages([
                    'room_id' => 'This room is already booked in that period.',
                ]);
            }
        }
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? '';
        $message = $e->getMessage();

        return $sqlState === '23505'
            || str_contains($message, 'UNIQUE constraint failed')
            || str_contains($message, 'Duplicate entry')
            || str_contains($message, 'tt_class_collision')
            || str_contains($message, 'tt_teacher_collision')
            || str_contains($message, 'tt_room_collision');
    }

    /** @param array<string, mixed> $data */
    private function messageFromUnique(QueryException $e, array $data): string
    {
        $msg = $e->getMessage();
        if (str_contains($msg, 'tt_teacher_collision')) {
            return 'This teacher is already assigned in that period.';
        }
        if (str_contains($msg, 'tt_room_collision')) {
            return 'This room is already booked in that period.';
        }
        if (str_contains($msg, 'tt_class_collision')) {
            return 'This class already has a lesson in that period.';
        }

        return 'Timetable collision: class, teacher, or room is already booked for that period.';
    }
}
