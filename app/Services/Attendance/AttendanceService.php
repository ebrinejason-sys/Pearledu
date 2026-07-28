<?php

namespace App\Services\Attendance;

use App\Models\AttendanceRecord;
use App\Models\Guardianship;
use App\Models\Student;
use App\Services\Sms\SmsSender;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function __construct(private SmsSender $sms) {}

    /**
     * @param  list<array{student_id:int, status:string, reason?:string|null}>  $rows
     * @return list<AttendanceRecord>
     */
    public function bulkUpsert(
        int $schoolId,
        int $classId,
        string $attendedOn,
        array $rows,
        ?int $recordedBy = null,
        bool $notifyAbsent = true,
    ): array {
        $saved = [];

        DB::transaction(function () use ($schoolId, $classId, $attendedOn, $rows, $recordedBy, $notifyAbsent, &$saved) {
            $studentIds = collect($rows)->pluck('student_id')->map(fn ($id) => (int) $id)->unique()->values();
            $validCount = Student::query()
                ->where('school_id', $schoolId)
                ->where('class_id', $classId)
                ->whereIn('id', $studentIds)
                ->count();

            if ($validCount !== $studentIds->count()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'records' => 'Every student must belong to the selected class.',
                ]);
            }

            foreach ($rows as $row) {
                $record = AttendanceRecord::query()->updateOrCreate(
                    [
                        'student_id' => $row['student_id'],
                        'attended_on' => $attendedOn,
                    ],
                    [
                        'school_id' => $schoolId,
                        'class_id' => $classId,
                        'status' => $row['status'],
                        'reason' => $row['reason'] ?? null,
                        'recorded_by' => $recordedBy,
                    ],
                );
                $saved[] = $record;

                if ($notifyAbsent && $row['status'] === 'absent') {
                    $this->notifyGuardians($schoolId, (int) $row['student_id'], $attendedOn);
                }
            }
        });

        return $saved;
    }

    private function notifyGuardians(int $schoolId, int $studentId, string $attendedOn): void
    {
        $student = Student::query()->find($studentId);
        if (! $student) {
            return;
        }

        $guardians = Guardianship::query()
            ->where('student_id', $studentId)
            ->with('guardian')
            ->get();

        $body = sprintf(
            '%s was marked absent on %s.',
            $student->full_name,
            $attendedOn,
        );

        foreach ($guardians as $link) {
            $phone = $link->guardian?->phone;
            if (! $phone) {
                continue;
            }
            try {
                $this->sms->send($schoolId, $phone, $body, 'attendance');
            } catch (\Throwable) {
                // SMS failures must not roll back attendance.
            }
        }
    }
}
