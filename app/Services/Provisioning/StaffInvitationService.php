<?php
namespace App\Services\Provisioning;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolInvitation;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\InvitationMailer;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StaffInvitationService
{
    /** @var list<string> */
    public const INVITABLE_ROLES = [
        'school_admin',
        'director',
        'head_teacher',
        'bursar',
        'class_teacher',
        'subject_teacher',
    ];

    public function __construct(
        private AuditLogger $audit,
        private TenantContext $context,
        private InvitationMailer $mailer,
    ) {}

    /**
     * Invite (or re-invite) a staff member to a school and email the accept link.
     *
     * @param  array{full_name: string, email: string, phone?: ?string, role_key: string}  $data
     * @return array{user: User, invitation: SchoolInvitation, token: string}
     */
    public function invite(School $school, array $data, int $operatorId): array
    {
        if (! in_array($data['role_key'], self::INVITABLE_ROLES, true)) {
            throw ValidationException::withMessages(['role_key' => 'That role cannot be invited from the platform console.']);
        }

        return DB::transaction(function () use ($school, $data, $operatorId) {
            $this->context->forPlatform();

            $email = strtolower(trim($data['email']));
            $user = User::whereRaw('lower(email) = ?', [$email])->first();

            if ($user?->is_platform) {
                throw ValidationException::withMessages(['email' => 'Platform operators cannot be invited as school staff.']);
            }

            if (! $user) {
                $user = User::create([
                    'full_name' => $data['full_name'],
                    'email' => $email,
                    'phone' => $data['phone'] ?? null,
                    'status' => 'invited',
                ]);
            } elseif ($user->status === 'disabled') {
                throw ValidationException::withMessages(['email' => 'That account is disabled.']);
            } else {
                $user->forceFill([
                    'full_name' => $data['full_name'],
                    'phone' => $data['phone'] ?? $user->phone,
                ])->save();
            }

            $roleId = Role::where('key', $data['role_key'])->value('id');
            if (! $roleId) {
                throw new RuntimeException('Role is not seeded: '.$data['role_key']);
            }

            RoleAssignment::firstOrCreate([
                'user_id' => $user->id,
                'role_id' => $roleId,
                'school_id' => $school->id,
            ], [
                'is_active' => true,
                'assigned_by' => $operatorId,
            ]);

            // Supersede any open invite for this user/school/role.
            SchoolInvitation::query()
                ->where('school_id', $school->id)
                ->where('user_id', $user->id)
                ->where('role_key', $data['role_key'])
                ->whereNull('accepted_at')
                ->delete();

            $raw = Str::random(48);
            $invitation = SchoolInvitation::create([
                'school_id' => $school->id,
                'user_id' => $user->id,
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'role_key' => $data['role_key'],
                'token_hash' => Hash::make($raw),
                'expires_at' => now()->addDays(7),
                'invited_by' => $operatorId,
            ]);

            $this->audit->record('staff.invited', $invitation, [
                'school_id' => $school->id,
                'role' => $data['role_key'],
                'email' => $email,
            ]);

            $this->mailer->send($invitation, $raw, $school);

            return ['user' => $user, 'invitation' => $invitation, 'token' => $raw];
        });
    }
}
