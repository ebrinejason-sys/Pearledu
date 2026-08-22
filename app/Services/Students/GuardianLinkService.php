<?php

namespace App\Services\Students;

use App\Mail\GuardianInvitationMail;
use App\Models\Guardianship;
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

class GuardianLinkService
{
    public function __construct(
        private TenantContext $context,
        private AuditLogger $audit,
    ) {}

    /**
     * Attach an existing school member (by email) as a guardian.
     */
    public function attachExisting(
        Student $student,
        string $email,
        ?string $relationship = null,
        bool $isPrimary = false,
        ?int $invitedBy = null,
    ): Guardianship {
        $schoolId = $this->requireSchoolId($student);
        $email = strtolower(trim($email));

        $user = User::query()
            ->whereRaw('lower(email) = ?', [$email])
            ->whereHas('roleAssignments', fn ($q) => $q->where('school_id', $schoolId)->where('is_active', true))
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'No active school member found with that email. Use invite to add a new guardian.',
            ]);
        }

        return $this->link($student, $user, $relationship, $isPrimary, $invitedBy);
    }

    /**
     * Invite a new guardian (or re-invite an existing invited user) and link them.
     */
    public function inviteNew(
        Student $student,
        string $fullName,
        string $email,
        ?string $phone = null,
        ?string $relationship = null,
        bool $isPrimary = false,
        ?int $invitedBy = null,
        ?string $nin = null,
    ): Guardianship {
        $schoolId = $this->requireSchoolId($student);
        $email = strtolower(trim($email));
        $school = School::findOrFail($schoolId);

        return DB::transaction(function () use ($student, $fullName, $email, $phone, $relationship, $isPrimary, $invitedBy, $schoolId, $school, $nin) {
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

                // This email already belongs to a real (non-pending) account — a
                // teacher/admin at another school, a platform operator, etc. — that
                // just isn't a member of this school yet. Inviting must never grant an
                // active role to an account that already exists and can already log
                // in; only brand-new (status='invited') accounts are safe to grant a
                // role to immediately, since their status keeps them locked out until
                // they go through the real invitation-acceptance flow.
                if ($user->status !== 'invited') {
                    throw ValidationException::withMessages([
                        'email' => 'That email belongs to an existing account. If they should have access here, ask them to accept an invitation through the normal onboarding flow rather than inviting them as a new guardian.',
                    ]);
                }
            } else {
                $user = User::create([
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phone ?: null,
                    'nin' => $nin,
                    'status' => 'invited',
                ]);
            }

            if (filled($nin) && ! $user->hasNationalIdOnFile()) {
                $user->forceFill(['nin' => $nin])->save();
            }

            $this->ensureParentRole($user, $schoolId, $invitedBy, active: false);

            $raw = Str::random(48);
            $invitation = SchoolInvitation::create([
                'school_id' => $schoolId,
                'user_id' => $user->id,
                'email' => $email,
                'phone' => $phone ?: $user->phone,
                'role_key' => 'parent',
                'token_hash' => Hash::make($raw),
                'expires_at' => now()->addDays(7),
                'invited_by' => $invitedBy,
                'batch_id' => (string) Str::uuid(),
            ]);

            $guardianship = $this->createGuardianship($student, $user, $relationship, $isPrimary);

            $acceptUrl = URL::route('invitations.accept', [
                'invitation' => $invitation->id,
                'token' => $raw,
            ]);

            Mail::to($email)->send(new GuardianInvitationMail(
                $user->full_name,
                $school->name,
                $student->full_name,
                $acceptUrl,
                $school->portalUrl(),
            ));

            $this->audit->record('guardian.invited', $guardianship, [
                'student_id' => $student->id,
                'guardian_user_id' => $user->id,
                'invitation_id' => $invitation->id,
            ]);

            return $guardianship;
        });
    }

    public function makePrimary(Guardianship $guardianship): void
    {
        DB::transaction(function () use ($guardianship) {
            Guardianship::where('student_id', $guardianship->student_id)
                ->where('id', '!=', $guardianship->id)
                ->update(['is_primary' => false]);

            $guardianship->update(['is_primary' => true]);
        });
    }

    public function detach(Guardianship $guardianship): void
    {
        $guardianship->delete();
        $this->audit->record('guardian.detached', $guardianship, [
            'student_id' => $guardianship->student_id,
            'guardian_user_id' => $guardianship->guardian_user_id,
        ]);
    }

    private function link(
        Student $student,
        User $user,
        ?string $relationship,
        bool $isPrimary,
        ?int $invitedBy,
    ): Guardianship {
        $schoolId = $this->requireSchoolId($student);
        $this->ensureParentRole($user, $schoolId, $invitedBy);

        return DB::transaction(function () use ($student, $user, $relationship, $isPrimary) {
            $guardianship = $this->createGuardianship($student, $user, $relationship, $isPrimary);
            $this->audit->record('guardian.attached', $guardianship, [
                'student_id' => $student->id,
                'guardian_user_id' => $user->id,
            ]);

            return $guardianship;
        });
    }

    private function createGuardianship(
        Student $student,
        User $user,
        ?string $relationship,
        bool $isPrimary,
    ): Guardianship {
        if (Guardianship::where('student_id', $student->id)->where('guardian_user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'That guardian is already linked to this student.',
            ]);
        }

        if ($isPrimary) {
            Guardianship::where('student_id', $student->id)->update(['is_primary' => false]);
        }

        return Guardianship::create([
            'student_id' => $student->id,
            'guardian_user_id' => $user->id,
            'relationship' => $relationship,
            'is_primary' => $isPrimary,
        ]);
    }

    private function ensureParentRole(User $user, int $schoolId, ?int $assignedBy, bool $active = true): void
    {
        $roleId = Role::where('key', 'parent')->value('id');
        if (! $roleId) {
            throw new RuntimeException('Parent role is not seeded.');
        }

        RoleAssignment::firstOrCreate(
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
            throw new RuntimeException('No school context for guardian linkage.');
        }

        return (int) $schoolId;
    }
}
