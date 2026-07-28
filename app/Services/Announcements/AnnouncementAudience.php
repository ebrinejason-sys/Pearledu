<?php

namespace App\Services\Announcements;

use App\Models\Guardianship;
use App\Models\RoleAssignment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/** Canonical audiences: all | class | parents | students | role */
class AnnouncementAudience
{
    public const CANONICAL = ['all', 'class', 'parents', 'students', 'role'];

    /** Map form / legacy values to stored canonical keys. */
    public function normalize(string $audience): string
    {
        return match ($audience) {
            'school' => 'all',
            'guardians' => 'parents',
            default => $audience,
        };
    }

    /**
     * @param  array{audience:string, class_id?:int|null, role_key?:string|null}  $data
     * @return array{audience:string, class_id:?int, role_key:?string}
     */
    public function validatedPayload(array $data): array
    {
        $audience = $this->normalize($data['audience']);
        if (! in_array($audience, self::CANONICAL, true)) {
            throw ValidationException::withMessages([
                'audience' => 'Invalid announcement audience.',
            ]);
        }

        $classId = isset($data['class_id']) && $data['class_id'] !== ''
            ? (int) $data['class_id']
            : null;
        $roleKey = isset($data['role_key']) && $data['role_key'] !== ''
            ? (string) $data['role_key']
            : null;

        if ($audience === 'class' && ! $classId) {
            throw ValidationException::withMessages([
                'class_id' => 'Select a class for class announcements.',
            ]);
        }
        if ($audience === 'role' && ! $roleKey) {
            throw ValidationException::withMessages([
                'role_key' => 'Select a role for role announcements.',
            ]);
        }
        if ($audience !== 'class') {
            $classId = null;
        }
        if ($audience !== 'role') {
            $roleKey = null;
        }

        return [
            'audience' => $audience,
            'class_id' => $classId,
            'role_key' => $roleKey,
        ];
    }

    /** Phone numbers for SMS delivery for a stored announcement audience. */
    public function recipientPhones(int $schoolId, string $audience, ?int $classId, ?string $roleKey): Collection
    {
        $audience = $this->normalize($audience);

        return match ($audience) {
            'all' => $this->phonesForSchoolMembers($schoolId),
            'parents' => $this->phonesForRole($schoolId, 'parent'),
            'students' => $this->phonesForRole($schoolId, 'student'),
            'role' => $roleKey ? $this->phonesForRole($schoolId, $roleKey) : collect(),
            'class' => $classId ? $this->phonesForClassGuardians($classId) : collect(),
            default => collect(),
        };
    }

    private function phonesForSchoolMembers(int $schoolId): Collection
    {
        return User::query()
            ->whereHas('roleAssignments', fn ($q) => $q->where('school_id', $schoolId)->where('is_active', true))
            ->whereNotNull('phone')
            ->pluck('phone')
            ->unique()
            ->values();
    }

    private function phonesForRole(int $schoolId, string $roleKey): Collection
    {
        $userIds = RoleAssignment::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->where('key', $roleKey))
            ->pluck('user_id');

        return User::query()
            ->whereIn('id', $userIds)
            ->whereNotNull('phone')
            ->pluck('phone')
            ->unique()
            ->values();
    }

    private function phonesForClassGuardians(int $classId): Collection
    {
        $studentIds = Student::query()->where('class_id', $classId)->pluck('id');
        $guardianIds = Guardianship::query()->whereIn('student_id', $studentIds)->pluck('guardian_user_id');

        return User::query()
            ->whereIn('id', $guardianIds)
            ->whereNotNull('phone')
            ->pluck('phone')
            ->unique()
            ->values();
    }
}
