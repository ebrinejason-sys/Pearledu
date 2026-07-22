<?php

namespace App\Services\Account;

use App\Models\Role;
use App\Models\RoleAssignment;
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
     * Accept: set password, activate the user, activate all pending role assignments
     * for this school, and mark open invitations accepted. Single-use per token.
     *
     * @return User
     */
    public function accept(int $invitationId, string $rawToken, string $password): User
    {
        return DB::transaction(function () use ($invitationId, $rawToken, $password) {
            $inv = $this->verify($invitationId, $rawToken);
            $user = $inv->user;
            if (! $user) {
                throw new RuntimeException('This invitation has no user account.');
            }

            $user->forceFill(['password' => $password, 'status' => 'active'])->save();

            if ($inv->school_id) {
                RoleAssignment::where('user_id', $user->id)
                    ->where('school_id', $inv->school_id)
                    ->update(['is_active' => true]);

                SchoolInvitation::query()
                    ->where('school_id', $inv->school_id)
                    ->where('user_id', $user->id)
                    ->whereNull('accepted_at')
                    ->update(['accepted_at' => now()]);
            } else {
                $roleId = Role::where('key', $inv->role_key)->value('id');
                if ($roleId) {
                    RoleAssignment::where('user_id', $user->id)->where('role_id', $roleId)
                        ->update(['is_active' => true]);
                }
                $inv->update(['accepted_at' => now()]);
            }

            $this->audit->record('invitation.accepted', $inv, [
                'school_id' => $inv->school_id,
                'role' => $inv->role_key,
            ]);

            if ($inv->school_id) {
                $school = \App\Models\School::find($inv->school_id);
                if ($school && ! $school->activated_at) {
                    $school->forceFill(['activated_at' => now()])->save();
                }
            }

            return $user->fresh();
        });
    }
}
