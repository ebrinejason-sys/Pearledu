<?php

namespace App\Services\Account;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\SchoolInvitation;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class InvitationService
{
    public function __construct(private AuditLogger $audit, private TenantContext $context) {}

    /** Locate a valid invitation by id and verify the raw token against its hash. */
    public function verify(int $invitationId, string $rawToken): SchoolInvitation
    {
        $this->context->forPlatform();
        $inv = SchoolInvitation::find($invitationId);
        if (! $inv || $inv->isAccepted() || $inv->isExpired() || ! Hash::check($rawToken, $inv->token_hash)) {
            throw new RuntimeException('This invitation link is invalid or has expired.');
        }

        return $inv;
    }

    /**
     * Break-glass: set a password and activate every open invite for this email
     * without the mailed token. Used when mail never arrived (local/dev or ops).
     */
    public function activateInvitedAccount(string $email, string $password): User
    {
        return DB::transaction(function () use ($email, $password) {
            $this->context->forPlatform();

            $normalized = strtolower(trim($email));
            $user = User::query()
                ->whereRaw('lower(email) = ?', [$normalized])
                ->lockForUpdate()
                ->first();

            if (! $user) {
                throw new RuntimeException('No account found for that email.');
            }
            if ($user->status === 'disabled') {
                throw new RuntimeException('This account is disabled and cannot be activated.');
            }
            if ($user->is_platform) {
                throw new RuntimeException('Platform operator accounts cannot be activated this way.');
            }

            $open = SchoolInvitation::query()
                ->where('user_id', $user->id)
                ->whereNull('accepted_at')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->get();

            if ($open->isEmpty() && $user->status !== 'invited') {
                throw new RuntimeException('No open invitation for that email. They may already have accepted.');
            }

            $user->forceFill(['password' => $password, 'status' => 'active'])->save();

            foreach ($open as $inv) {
                $this->activateInvitationRole($user, $inv);
                $inv->forceFill(['accepted_at' => now()])->save();
            }

            if ($open->isEmpty()) {
                RoleAssignment::query()
                    ->where('user_id', $user->id)
                    ->where('is_active', false)
                    ->update(['is_active' => true]);
            }

            $this->audit->record('invitation.activated_cli', $user, [
                'email' => $user->email,
                'roles_activated' => $open->pluck('role_key')->values()->all(),
            ]);

            return $user->fresh();
        });
    }

    /**
     * Accept: set password, activate only this invitation's batch (or single role),
     * and mark those invitations accepted. Locked against concurrent accept races.
     */
    public function accept(int $invitationId, string $rawToken, string $password): User
    {
        return DB::transaction(function () use ($invitationId, $rawToken, $password) {
            $this->context->forPlatform();

            $inv = SchoolInvitation::query()->whereKey($invitationId)->lockForUpdate()->first();
            if (! $inv || $inv->isAccepted() || $inv->isExpired() || ! Hash::check($rawToken, $inv->token_hash)) {
                throw new RuntimeException('This invitation link is invalid or has expired.');
            }

            $user = $inv->user;
            if (! $user) {
                throw new RuntimeException('This invitation has no user account.');
            }

            if ($user->status === 'disabled') {
                throw new RuntimeException('This account is disabled and cannot accept invitations.');
            }

            $user->forceFill(['password' => $password, 'status' => 'active'])->save();

            $batch = $inv->batch_id
                ? SchoolInvitation::query()
                    ->where('batch_id', $inv->batch_id)
                    ->whereNull('accepted_at')
                    ->lockForUpdate()
                    ->get()
                : collect([$inv]);

            foreach ($batch as $item) {
                $this->activateInvitationRole($user, $item);
                $item->forceFill(['accepted_at' => now()])->save();
            }

            $this->audit->record('invitation.accepted', $inv, [
                'school_id' => $inv->school_id,
                'role' => $inv->role_key,
                'batch_id' => $inv->batch_id,
                'roles_activated' => $batch->pluck('role_key')->values()->all(),
            ]);

            if ($inv->school_id) {
                $school = School::find($inv->school_id);
                if ($school && ! $school->activated_at) {
                    $school->forceFill(['activated_at' => now()])->save();
                }
            }

            return $user->fresh();
        });
    }

    private function activateInvitationRole(User $user, SchoolInvitation $inv): void
    {
        $roleId = Role::where('key', $inv->role_key)->value('id');
        if (! $roleId) {
            return;
        }

        $query = RoleAssignment::query()
            ->where('user_id', $user->id)
            ->where('role_id', $roleId);

        if ($inv->school_id) {
            $query->where('school_id', $inv->school_id);
        } else {
            $query->whereNull('school_id');
        }

        $query->update(['is_active' => true]);
    }
}
