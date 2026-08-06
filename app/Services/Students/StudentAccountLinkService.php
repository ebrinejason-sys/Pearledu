<?php

namespace App\Services\Students;

use App\Mail\StudentAccountInvitationMail;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolInvitation;
use App\Models\Student;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/** Link a login account to a learner record so student portal / LMS / CBT work. */
class StudentAccountLinkService
{
    public function __construct(
        private TenantContext $context,
        private AuditLogger $audit,
    ) {}

    /**
     * Attach an existing school member (by email) as this learner's login.
     */
    public function attachExisting(Student $student, string $email, ?int $assignedBy = null): Student
    {
        $schoolId = $this->requireSchoolId($student);
        $email = strtolower(trim($email));

        $user = User::query()
            ->whereRaw('lower(email) = ?', [$email])
            ->whereHas('roleAssignments', fn ($q) => $q->where('school_id', $schoolId)->where('is_active', true))
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'No active school member found with that email. Use invite to create a student login.',
            ]);
        }

        return $this->link($student, $user, $assignedBy);
    }

    /**
     * Invite a new student login (or re-invite an invited user) and link them.
     */
    public function inviteNew(
        Student $student,
        string $fullName,
        string $email,
        ?string $phone = null,
        ?int $invitedBy = null,
    ): Student {
        $schoolId = $this->requireSchoolId($student);
        $email = strtolower(trim($email));
        $school = School::findOrFail($schoolId);

        if ($student->user_id) {
            throw ValidationException::withMessages([
                'email' => 'This learner already has a linked login. Unlink it first.',
            ]);
        }

        return DB::transaction(function () use ($student, $fullName, $email, $phone, $invitedBy, $schoolId, $school) {
            $user = User::whereRaw('lower(email) = ?', [$email])->first();

            if ($user) {
                $alreadyMember = RoleAssignment::query()
                    ->where('user_id', $user->id)
                    ->where('school_id', $schoolId)
                    ->where('is_active', true)
                    ->exists();

                if ($alreadyMember && $user->status === 'active') {
                    throw ValidationException::withMessages([
                        'email' => 'That person is already a school member. Use attach instead of invite.',
                    ]);
                }

                if ($user->status !== 'invited') {
                    throw ValidationException::withMessages([
                        'email' => 'That email belongs to an existing account. Attach them if they already belong to this school, or invite through Staff.',
                    ]);
                }
            } else {
                $user = User::create([
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phone ?: null,
                    'status' => 'invited',
                ]);
            }

            $this->ensureStudentRole($user, $schoolId, $invitedBy, active: false);

            $raw = Str::random(48);
            $invitation = SchoolInvitation::create([
                'school_id' => $schoolId,
                'user_id' => $user->id,
                'email' => $email,
                'phone' => $phone ?: $user->phone,
                'role_key' => 'student',
                'token_hash' => Hash::make($raw),
                'expires_at' => now()->addDays(7),
                'invited_by' => $invitedBy,
                'batch_id' => (string) Str::uuid(),
            ]);

            $student->forceFill(['user_id' => $user->id])->save();

            $acceptUrl = URL::route('invitations.accept', [
                'invitation' => $invitation->id,
                'token' => $raw,
            ]);

            Mail::to($email)->send(new StudentAccountInvitationMail(
                $user->full_name,
                $school->name,
                $student->full_name,
                $acceptUrl,
                $school->portalUrl(),
            ));

            $this->audit->record('student.account.invited', $student, [
                'user_id' => $user->id,
                'invitation_id' => $invitation->id,
            ]);

            return $student->fresh();
        });
    }

    public function unlink(Student $student): void
    {
        $schoolId = $this->requireSchoolId($student);
        $userId = $student->user_id;
        if (! $userId) {
            return;
        }

        $student->forceFill(['user_id' => null])->save();
        $this->audit->record('student.account.unlinked', $student, [
            'user_id' => $userId,
            'school_id' => $schoolId,
        ]);
    }

    private function link(Student $student, User $user, ?int $assignedBy): Student
    {
        $schoolId = $this->requireSchoolId($student);

        if ($user->is_platform) {
            throw ValidationException::withMessages([
                'email' => 'Platform operators cannot be linked as student logins.',
            ]);
        }

        if ($student->user_id && (int) $student->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'email' => 'This learner already has a different linked login. Unlink it first.',
            ]);
        }

        $taken = Student::query()
            ->where('school_id', $schoolId)
            ->where('user_id', $user->id)
            ->where('id', '!=', $student->id)
            ->exists();
        if ($taken) {
            throw ValidationException::withMessages([
                'email' => 'That account is already linked to another learner at this school.',
            ]);
        }

        return DB::transaction(function () use ($student, $user, $assignedBy, $schoolId) {
            $this->ensureStudentRole($user, $schoolId, $assignedBy, active: true);
            $student->forceFill(['user_id' => $user->id])->save();
            $this->audit->record('student.account.linked', $student, [
                'user_id' => $user->id,
            ]);

            return $student->fresh();
        });
    }

    private function ensureStudentRole(User $user, int $schoolId, ?int $assignedBy, bool $active = true): void
    {
        $roleId = Role::where('key', 'student')->value('id');
        if (! $roleId) {
            throw new RuntimeException('Student role is not seeded.');
        }

        RoleAssignment::updateOrCreate(
            [
                'user_id' => $user->id,
                'role_id' => $roleId,
                'school_id' => $schoolId,
            ],
            [
                'is_active' => $active,
                'assigned_by' => $assignedBy,
            ],
        );
    }

    private function requireSchoolId(Student $student): int
    {
        $schoolId = $this->context->schoolId() ?? $student->school_id;
        if (! $schoolId) {
            throw new RuntimeException('No school context for student account linkage.');
        }

        return (int) $schoolId;
    }
}
