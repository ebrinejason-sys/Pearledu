<?php

namespace App\Services\Platform;

use App\Mail\Auth\PlatformStaffWelcomeMail;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Services\Audit\AuditLogger;
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

    public function __construct(
        private TenantContext $context,
        private AuditLogger $audit,
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

    /**
     * @param  array{full_name:string,email:string,phone?:string|null,role_key:string,password?:string|null}  $data
     * @return array{user: User, temporary_password: string, emailed: bool, mail_error: ?string}
     */
    public function create(array $data, int $createdBy): array
    {
        if (! in_array($data['role_key'], self::ROLE_KEYS, true)) {
            throw new RuntimeException('Invalid PearlEdu staff role.');
        }

        $result = DB::transaction(function () use ($data, $createdBy) {
            $this->context->forPlatform();

            $email = strtolower(trim($data['email']));
            if (User::whereRaw('lower(email) = ?', [$email])->exists()) {
                throw new RuntimeException('A user with that email already exists.');
            }

            // Always issue a password we can email (admin-provided or auto-generated).
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

            $roleId = Role::where('key', $data['role_key'])->value('id');
            if (! $roleId) {
                throw new RuntimeException('Role is not seeded. Run RoleSeeder.');
            }

            RoleAssignment::create([
                'user_id' => $user->id,
                'role_id' => $roleId,
                'school_id' => null,
                'is_active' => true,
                'assigned_by' => $createdBy,
            ]);

            $this->audit->record('platform.staff.created', $user, [
                'role_key' => $data['role_key'],
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

    public function updateRole(User $user, string $roleKey, int $updatedBy): void
    {
        if (! $user->is_platform) {
            throw new RuntimeException('Only PearlEdu staff can be updated here.');
        }
        if (! in_array($roleKey, self::ROLE_KEYS, true)) {
            throw new RuntimeException('Invalid PearlEdu staff role.');
        }

        $this->context->forPlatform();
        $roleId = Role::where('key', $roleKey)->value('id');
        abort_unless($roleId, 500);

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
            'assigned_by' => $updatedBy,
        ]);

        $this->audit->record('platform.staff.role_updated', $user, ['role_key' => $roleKey]);
    }

    public function setStatus(User $user, string $status): void
    {
        if (! $user->is_platform) {
            throw new RuntimeException('Only PearlEdu staff can be updated here.');
        }
        abort_unless(in_array($status, ['active', 'disabled'], true), 422);

        $user->update(['status' => $status]);
        $this->audit->record('platform.staff.status', $user, ['status' => $status]);
    }

    /**
     * @return array{temporary_password: string, emailed: bool, mail_error: ?string}
     */
    public function resetPassword(User $user): array
    {
        if (! $user->is_platform) {
            throw new RuntimeException('Only PearlEdu staff can be updated here.');
        }

        $temp = Str::password(14);
        $user->forceFill(['password' => $temp])->save();

        $this->audit->record('platform.staff.password_reset', $user);

        $roleKey = $this->platformRoleKey($user) ?? 'platform_ops';
        $mail = $this->sendWelcomeEmail($user, $roleKey, $temp, isPasswordReset: true);

        return [
            'temporary_password' => $temp,
            'emailed' => $mail['ok'],
            'mail_error' => $mail['error'],
        ];
    }

    public function platformRoleKey(User $user): ?string
    {
        return $user->activeAssignments()
            ->whereNull('school_id')
            ->whereHas('role', fn ($q) => $q->where('scope', 'platform'))
            ->with('role')
            ->first()
            ?->role
            ?->key;
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
