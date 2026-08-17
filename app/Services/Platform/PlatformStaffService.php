<?php

namespace App\Services\Platform;

use App\Mail\Auth\PlatformStaffWelcomeMail;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\PasswordResetService;
use App\Services\Auth\SessionInvalidator;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Create and manage PearlEdu platform staff (not school tenants). */
class PlatformStaffService
{
    /** @var list<string> */
    public const ROLE_KEYS = [
        'platform_admin',
        'platform_ops',
        'emis_data_entrant',
        'support_agent',
    ];

    /** Higher = more power. Admins manage anyone strictly below them. */
    public const ROLE_RANK = [
        'platform_admin' => 100,
        'platform_ops' => 70,
        'emis_data_entrant' => 40,
        'support_agent' => 30,
    ];

    public function __construct(
        private TenantContext $context,
        private AuditLogger $audit,
        private SessionInvalidator $sessions,
        private PasswordResetService $passwordResets,
    ) {}

    /** @return array<string, string> */
    public static function roleLabels(): array
    {
        return [
            'platform_admin' => 'Platform Admin',
            'platform_ops' => 'Platform Operations',
            'emis_data_entrant' => 'EMIS Data Entrant',
            'support_agent' => 'Support Agent',
        ];
    }

    public function rank(?string $roleKey): int
    {
        return self::ROLE_RANK[$roleKey] ?? 0;
    }

    /**
     * Roles the actor may assign (strictly below their own rank).
     * Platform admins may also assign platform_admin.
     *
     * @return array<string, string>
     */
    public function assignableRoles(User $actor): array
    {
        $actorKey = $this->resolvedRoleKey($actor);
        $actorRank = $this->rank($actorKey);
        $labels = self::roleLabels();

        if ($actorKey === 'platform_admin') {
            return $labels;
        }

        return array_filter(
            $labels,
            fn ($label, $key) => $this->rank($key) < $actorRank,
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function canManage(User $actor, User $target): bool
    {
        if (! $actor->is_platform || ! $target->is_platform) {
            return false;
        }
        if ((int) $actor->id === (int) $target->id) {
            return false; // never manage yourself via staff tools
        }

        // Platform Admins are peers operationally, but one admin must be able to
        // recover, disable, or remove another compromised admin account.
        if ($this->resolvedRoleKey($actor) === 'platform_admin') {
            return true;
        }

        return $this->rank($this->resolvedRoleKey($actor)) > $this->rank($this->resolvedRoleKey($target));
    }

    public function assertCanManage(User $actor, User $target): void
    {
        if (! $this->canManage($actor, $target)) {
            throw new RuntimeException('You can only manage PearlEdu staff below your own role.');
        }
    }

    public function assertCanAssign(User $actor, string $roleKey): void
    {
        if (! isset(self::ROLE_RANK[$roleKey])) {
            throw new RuntimeException('Invalid PearlEdu staff role.');
        }
        if (! array_key_exists($roleKey, $this->assignableRoles($actor))) {
            throw new RuntimeException('You cannot assign a role at or above your own level.');
        }
    }

    /**
     * Resolve platform role key. Never auto-assigns roles (GET-safe).
     * Returns null when the account is misconfigured (is_platform without assignment).
     */
    public function resolvedRoleKey(User $user): ?string
    {
        return $this->platformRoleKey($user);
    }

    /**
     * @param  array{full_name:string,email:string,phone?:string|null,role_key:string,password?:string|null}  $data
     * @return array{user: User, temporary_password: string, emailed: bool, mail_error: ?string}
     */
    public function create(array $data, User $actor): array
    {
        $this->assertCanAssign($actor, $data['role_key']);

        $result = DB::transaction(function () use ($data, $actor) {
            $this->context->forPlatform();

            $email = strtolower(trim($data['email']));
            if (User::whereRaw('lower(email) = ?', [$email])->exists()) {
                throw new RuntimeException('A user with that email already exists.');
            }

            $temporaryPassword = ! empty($data['password'])
                ? (string) $data['password']
                : Str::password(14);

            $user = new User([
                'full_name' => $data['full_name'],
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'status' => 'active',
                'password' => $temporaryPassword,
            ]);
            $user->forceFill(['is_platform' => true])->save();

            $this->assignRoleQuietly($user, $data['role_key'], (int) $actor->id);

            $this->audit->record('platform.staff.created', $user, [
                'role_key' => $data['role_key'],
                'by' => $actor->id,
            ]);

            return [
                'user' => $user->fresh(),
                'temporary_password' => $temporaryPassword,
                'role_key' => $data['role_key'],
            ];
        });

        $mail = $this->sendWelcomeEmail(
            $result['user'],
            $result['role_key'],
            $result['temporary_password'],
            isPasswordReset: false,
        );

        return [
            'user' => $result['user'],
            'temporary_password' => $result['temporary_password'],
            'emailed' => $mail['ok'],
            'mail_error' => $mail['error'],
        ];
    }

    /**
     * @param  array{full_name:string,email:string,phone?:string|null,role_key:string,status:string}  $data
     */
    public function update(User $target, array $data, User $actor): void
    {
        $this->assertCanManage($actor, $target);
        $this->assertCanAssign($actor, $data['role_key']);

        $this->context->forPlatform();

        $email = strtolower(trim($data['email']));
        $taken = User::whereRaw('lower(email) = ?', [$email])
            ->where('id', '!=', $target->id)
            ->exists();
        if ($taken) {
            throw new RuntimeException('A user with that email already exists.');
        }

        $previousRole = $this->resolvedRoleKey($target);
        $previousStatus = $target->status;
        $removesActiveAdmin = $previousRole === 'platform_admin'
            && $previousStatus === 'active'
            && ($data['role_key'] !== 'platform_admin' || $data['status'] !== 'active');
        if ($removesActiveAdmin) {
            $this->assertAnotherActiveAdmin($target);
        }

        DB::transaction(function () use ($target, $data, $email, $actor) {
            $this->context->forPlatform();
            $target->update([
                'full_name' => $data['full_name'],
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'],
            ]);

            $this->assignRoleQuietly($target, $data['role_key'], (int) $actor->id);
            $this->audit->record('platform.staff.updated', $target, [
                'role_key' => $data['role_key'],
                'status' => $data['status'],
                'by' => $actor->id,
            ]);
        });

        $roleChanged = $previousRole !== $data['role_key'];
        $disabled = $previousStatus !== 'disabled' && $data['status'] === 'disabled';
        if ($roleChanged || $disabled) {
            $fresh = $target->fresh();
            $this->sessions->invalidate($fresh);
            if ($disabled) {
                $this->passwordResets->revokeTokens($fresh);
                $this->revokePendingTwoFactorCodes($fresh);
            }
        }
    }

    public function delete(User $target, User $actor): void
    {
        $this->assertCanManage($actor, $target);

        if ($this->resolvedRoleKey($target) === 'platform_admin' && $target->status === 'active') {
            $this->assertAnotherActiveAdmin($target);
        }

        DB::transaction(function () use ($target, $actor) {
            $this->context->forPlatform();
            $originalEmail = $target->email;

            RoleAssignment::query()
                ->where('user_id', $target->id)
                ->whereNull('school_id')
                ->update(['is_active' => false, 'ends_on' => now()]);

            $target->forceFill([
                'is_platform' => false,
                'status' => 'disabled',
                'email' => 'deleted+'.$target->id.'.'.time().'@invalid.local',
                'phone' => null,
            ])->save();

            if (! $target->trashed()) {
                $target->delete();
            }

            $this->sessions->invalidate($target);

            $this->audit->record('platform.staff.deleted', null, [
                'user_id' => $target->id,
                'email' => $originalEmail,
                'by' => $actor->id,
            ]);
        });
    }

    /**
     * Send a password-reset email. Does not change the current password until the staff member completes the link.
     *
     * @return array{emailed: bool, mail_error: ?string}
     */
    public function resetPassword(User $target, User $actor): array
    {
        $this->assertCanManage($actor, $target);
        $this->context->forPlatform();

        try {
            $this->passwordResets->sendResetLinkTo($target);
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            $this->audit->record('platform.staff.password_reset', $target, [
                'by' => $actor->id,
                'emailed' => false,
            ]);

            return ['emailed' => false, 'mail_error' => $e->getMessage()];
        }

        $this->audit->record('platform.staff.password_reset', $target, [
            'by' => $actor->id,
            'emailed' => true,
        ]);
        $this->audit->record('platform.staff.password_emailed', $target, ['by' => $actor->id]);

        return ['emailed' => true, 'mail_error' => null];
    }

    public function forceLogout(User $target, User $actor): void
    {
        $this->assertCanManage($actor, $target);
        $this->context->forPlatform();
        $this->sessions->invalidate($target);
        $this->audit->record('platform.staff.force_logout', $target, ['by' => $actor->id]);
    }

    public function resetTwoFactor(User $target, User $actor): void
    {
        $this->assertCanManage($actor, $target);
        $this->context->forPlatform();

        $target->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        $this->sessions->invalidate($target);
        $this->revokePendingTwoFactorCodes($target);
        $this->audit->record('platform.staff.two_factor_reset', $target, ['by' => $actor->id]);
    }

    public function platformRoleKey(User $user): ?string
    {
        return $user->platformRoleKey();
    }

    private function assignRoleQuietly(User $user, string $roleKey, ?int $assignedBy): void
    {
        $roleId = Role::where('key', $roleKey)->value('id');
        if (! $roleId) {
            throw new RuntimeException('Role is not seeded. Run RoleSeeder.');
        }

        RoleAssignment::query()
            ->where('user_id', $user->id)
            ->whereNull('school_id')
            ->where('is_active', true)
            ->update(['is_active' => false, 'ends_on' => now()]);

        RoleAssignment::create([
            'user_id' => $user->id,
            'role_id' => $roleId,
            'school_id' => null,
            'is_active' => true,
            'assigned_by' => $assignedBy,
        ]);
    }

    private function assertAnotherActiveAdmin(User $target): void
    {
        $otherAdmins = User::query()
            ->where('is_platform', true)
            ->where('id', '!=', $target->id)
            ->where('status', 'active')
            ->get()
            ->filter(fn (User $user) => $this->resolvedRoleKey($user) === 'platform_admin')
            ->count();

        if ($otherAdmins < 1) {
            throw new RuntimeException('Cannot disable, demote, or delete the last active Platform Admin.');
        }
    }

    private function revokePendingTwoFactorCodes(User $user): void
    {
        DB::table('two_factor_email_codes')->where('user_id', $user->id)->delete();
    }

    /** @return array{ok: bool, error: ?string} */
    private function sendWelcomeEmail(User $user, string $roleKey, string $temporaryPassword, bool $isPasswordReset): array
    {
        if (! $user->email) {
            return ['ok' => false, 'error' => 'Staff account has no email address.'];
        }

        $loginUrl = rtrim((string) config('app.url'), '/').'/login';
        $host = config('tenancy.pearledu_landing_host');
        if ($host) {
            $loginUrl = 'https://'.$host.'/login';
        }

        try {
            Mail::to($user->email)->send(new PlatformStaffWelcomeMail(
                user: $user,
                roleLabel: self::roleLabels()[$roleKey] ?? $roleKey,
                temporaryPassword: $temporaryPassword,
                loginUrl: $loginUrl,
                isPasswordReset: $isPasswordReset,
            ));

            $this->audit->record(
                $isPasswordReset ? 'platform.staff.password_emailed' : 'platform.staff.welcome_emailed',
                $user,
            );

            return ['ok' => true, 'error' => null];
        } catch (Throwable $e) {
            report($e);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
