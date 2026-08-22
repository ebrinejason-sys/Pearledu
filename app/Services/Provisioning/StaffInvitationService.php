<?php

namespace App\Services\Provisioning;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolInvitation;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Academics\CurrentAcademicContext;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\InvitationDispatcher;
use App\Services\Authorization\InvitePolicy;
use App\Services\Tenancy\TenantContext;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StaffInvitationService
{
    /** @deprecated Use InvitePolicy::PLATFORM_INVITABLE */
    public const INVITABLE_ROLES = InvitePolicy::PLATFORM_INVITABLE;

    public function __construct(
        private AuditLogger $audit,
        private TenantContext $context,
        private InvitationDispatcher $dispatcher,
        private InvitePolicy $policy,
        private CurrentAcademicContext $academic,
    ) {}

    /**
     * Invite (or re-invite) a staff member. Email and/or phone required.
     * Supports one or many role_keys (multi-role).
     *
     * @param  array{
     *   full_name: string,
     *   email?: ?string,
     *   phone?: ?string,
     *   role_key?: string,
     *   role_keys?: list<string>,
     *   class_id?: ?int,
     *   date_of_birth?: ?string,
     *   nationality?: ?string,
     *   home_address?: ?string,
     *   teaching_assignments?: list<array{subject_id:int, class_ids: list<int>, periods_per_week?: int}>
     * }  $data
     * @return array{
     *   user: User,
     *   invitations: list<SchoolInvitation>,
     *   tokens: list<string>,
     *   delivery: array{email: bool, sms: bool, warnings: list<string>, accept_url?: string}
     * }
     */
    public function invite(School $school, array $data, User $inviter, bool $asPlatform = false): array
    {
        $roleKeys = $this->normalizeRoleKeys($data);
        foreach ($roleKeys as $roleKey) {
            if (! $this->policy->canInvite($inviter, $roleKey, $school->id, $asPlatform)) {
                throw ValidationException::withMessages([
                    'role_keys' => "You are not allowed to invite the role: {$roleKey}.",
                ]);
            }
        }

        $classId = isset($data['class_id']) && $data['class_id'] !== '' ? (int) $data['class_id'] : null;
        if (in_array('class_teacher', $roleKeys, true) && ! $classId) {
            throw ValidationException::withMessages([
                'class_id' => 'Select a class for the class teacher role.',
            ]);
        }

        $email = isset($data['email']) && $data['email'] !== ''
            ? strtolower(trim((string) $data['email']))
            : null;
        $phone = PhoneNormalizer::normalize($data['phone'] ?? null);

        if (! $email && ! $phone) {
            throw ValidationException::withMessages([
                'email' => 'Provide an email address or a phone number.',
            ]);
        }

        return DB::transaction(function () use ($school, $data, $inviter, $asPlatform, $roleKeys, $email, $phone, $classId) {
            if ($asPlatform || $inviter->isPlatformOperator()) {
                $this->context->forPlatform();
            }

            $user = $this->resolveUser($email, $phone, $data['full_name']);

            if ($user->is_platform) {
                throw ValidationException::withMessages([
                    'email' => 'Platform operators cannot be invited as school staff.',
                ]);
            }

            if ($user->status === 'disabled') {
                throw ValidationException::withMessages([
                    'email' => 'That account is disabled.',
                ]);
            }

            $fill = [
                'full_name' => $data['full_name'],
                'email' => $email ?? $user->email,
                'phone' => $phone ?? $user->phone,
            ];
            if (filled($data['gender'] ?? null)) {
                $fill['gender'] = $data['gender'];
            }
            if (filled($data['nin'] ?? null)) {
                $fill['nin'] = $data['nin'];
            }
            if (array_key_exists('date_of_birth', $data) && filled($data['date_of_birth'])) {
                $fill['date_of_birth'] = $data['date_of_birth'];
            }
            if (array_key_exists('nationality', $data) && filled($data['nationality'])) {
                $fill['nationality'] = $data['nationality'];
            }
            if (array_key_exists('home_address', $data) && filled($data['home_address'])) {
                $fill['home_address'] = $data['home_address'];
            }
            $user->forceFill($fill)->save();

            $invitations = [];
            $tokens = [];
            $batchId = (string) Str::uuid();

            foreach ($roleKeys as $roleKey) {
                $roleId = Role::where('key', $roleKey)->value('id');
                if (! $roleId) {
                    throw new RuntimeException('Role is not seeded: '.$roleKey);
                }

                // Always inactive until this invitation batch is accepted — never
                // grant school access merely because the account already exists.
                $assignmentClassId = $roleKey === 'class_teacher' ? $classId : null;

                RoleAssignment::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'role_id' => $roleId,
                        'school_id' => $school->id,
                    ],
                    [
                        'is_active' => false,
                        'assigned_by' => $inviter->id,
                        'class_id' => $assignmentClassId,
                    ]
                );

                SchoolInvitation::query()
                    ->where('school_id', $school->id)
                    ->where('user_id', $user->id)
                    ->where('role_key', $roleKey)
                    ->whereNull('accepted_at')
                    ->delete();

                $raw = Str::random(48);
                $invitation = SchoolInvitation::create([
                    'school_id' => $school->id,
                    'user_id' => $user->id,
                    'email' => $email,
                    'phone' => $phone,
                    'role_key' => $roleKey,
                    'token_hash' => Hash::make($raw),
                    'expires_at' => now()->addDays(7),
                    'invited_by' => $inviter->id,
                    'batch_id' => $batchId,
                ]);

                $this->audit->record('staff.invited', $invitation, [
                    'school_id' => $school->id,
                    'role' => $roleKey,
                    'email' => $email,
                    'phone' => $phone,
                    'batch_id' => $batchId,
                ]);

                $invitations[] = $invitation;
                $tokens[] = $raw;
            }

            if (in_array('subject_teacher', $roleKeys, true)) {
                $this->syncTeachingAssignments($school, $user, $data['teaching_assignments'] ?? []);
            }

            // One activation link covers every role in this invite batch.
            $delivery = ['email' => false, 'sms' => false, 'warnings' => []];
            if ($invitations !== []) {
                $delivery = $this->dispatcher->send($invitations[0], $tokens[0], $school);
            }

            return [
                'user' => $user,
                'invitations' => $invitations,
                'tokens' => $tokens,
                'delivery' => $delivery,
            ];
        });
    }

    /**
     * @param  array{role_key?: string, role_keys?: list<string>}  $data
     * @return list<string>
     */
    private function normalizeRoleKeys(array $data): array
    {
        $keys = $data['role_keys'] ?? null;
        if (is_array($keys) && $keys !== []) {
            return array_values(array_unique(array_map('strval', $keys)));
        }

        if (! empty($data['role_key'])) {
            return [(string) $data['role_key']];
        }

        throw ValidationException::withMessages(['role_keys' => 'Select at least one role.']);
    }

    /**
     * @param  list<array{subject_id?: mixed, class_ids?: mixed, periods_per_week?: mixed}>  $rows
     */
    private function syncTeachingAssignments(School $school, User $user, array $rows): void
    {
        $pairs = [];
        foreach ($rows as $row) {
            $subjectId = (int) ($row['subject_id'] ?? 0);
            $classIds = $row['class_ids'] ?? [];
            if (! is_array($classIds)) {
                $classIds = [$classIds];
            }
            if ($subjectId <= 0) {
                continue;
            }
            $periods = (int) ($row['periods_per_week'] ?? 3);
            if ($periods < 1) {
                $periods = 3;
            }
            foreach ($classIds as $classId) {
                $id = (int) $classId;
                if ($id > 0) {
                    $pairs[] = ['subject_id' => $subjectId, 'class_id' => $id, 'periods_per_week' => $periods];
                }
            }
        }

        if ($pairs === []) {
            throw ValidationException::withMessages([
                'teaching_assignments' => 'Specify a subject and at least one class for this teacher so the timetable can avoid collisions.',
            ]);
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

        foreach ($pairs as $pair) {
            $subjectOk = Subject::query()
                ->where('school_id', $school->id)
                ->whereKey($pair['subject_id'])
                ->exists();
            $classOk = SchoolClass::query()
                ->where('school_id', $school->id)
                ->whereKey($pair['class_id'])
                ->exists();
            if (! $subjectOk || ! $classOk) {
                throw ValidationException::withMessages([
                    'teaching_assignments' => 'Each teaching assignment must use a subject and class from this school.',
                ]);
            }

            TeachingAssignment::query()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'user_id' => $user->id,
                    'subject_id' => $pair['subject_id'],
                    'class_id' => $pair['class_id'],
                    'academic_year_id' => $year->id,
                    'term_id' => null,
                ],
                [
                    'status' => 'active',
                    'periods_per_week' => $pair['periods_per_week'],
                ],
            );
        }
    }

    private function resolveUser(?string $email, ?string $phone, string $fullName): User
    {
        $user = null;
        if ($email) {
            $user = User::whereRaw('lower(email) = ?', [$email])->first();
        }
        if (! $user && $phone) {
            $user = User::where('phone', $phone)->first();
        }

        if ($user) {
            return $user;
        }

        return User::create([
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'status' => 'invited',
        ]);
    }
}
