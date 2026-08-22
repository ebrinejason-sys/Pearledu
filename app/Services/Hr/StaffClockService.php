<?php

namespace App\Services\Hr;

use App\Models\School;
use App\Models\StaffTimePunch;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use RuntimeException;

class StaffClockService
{
    public function __construct(
        private StaffBadgeService $badges,
        private AuditLogger $audit,
    ) {}

    public function punchByCode(School $school, string $code, User $scanner): StaffTimePunch
    {
        $badge = $this->badges->findActive($school, $code);
        $staff = $badge?->user;
        if (! $staff instanceof User) {
            throw new RuntimeException('Unknown or revoked staff ID.');
        }

        return $this->punch($school, $staff, $scanner, 'scan');
    }

    public function punch(School $school, User $staff, User $recorder, string $source = 'scan'): StaffTimePunch
    {
        $last = StaffTimePunch::query()
            ->where('school_id', $school->id)
            ->where('user_id', $staff->id)
            ->orderByDesc('punched_at')
            ->first();

        $direction = $last?->direction === StaffTimePunch::IN
            ? StaffTimePunch::OUT
            : StaffTimePunch::IN;

        $punch = StaffTimePunch::create([
            'school_id' => $school->id,
            'user_id' => $staff->id,
            'recorded_by' => $recorder->id,
            'direction' => $direction,
            'source' => $source,
            'punched_at' => now(),
        ]);

        $this->audit->record('staff.clocked', $punch, [
            'user_id' => $staff->id,
            'direction' => $direction,
            'source' => $source,
        ], $recorder);

        return $punch->load('user');
    }
}
