<?php

namespace App\Services\Provisioning;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolInvitation;
use App\Models\User;
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
     *   class_id?: ?int
     * }  $data
     * @return array{user: User, invitations: list<SchoolInvitation>, tokens: list<string>}
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

            $user->forceFill([
                'full_name' => $data['full_name'],
                'email' => $email ?? $user->email,
                'phone' => $phone ?? $user->phone,
            ])->save();

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

            // One activation link covers every role in this invite batch.
            if ($invitations !== []) {
                $this->dispatcher->send($invitations[0], $tokens[0], $school);
            }

            return ['user' => $user, 'invitations' => $invitations, 'tokens' => $tokens];
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
