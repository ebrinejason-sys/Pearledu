<?php
namespace App\Services\Account;
use App\Models\RoleAssignment;
use App\Models\Role;
use App\Models\SchoolInvitation;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class InvitationService {
    public function __construct(private AuditLogger $audit, private TenantContext $context) {}

    /** Locate a valid invitation by id and verify the raw token against its hash. */
    public function verify(int $invitationId, string $rawToken): SchoolInvitation {
        $this->context->forPlatform();
        $inv = SchoolInvitation::find($invitationId);
        if (! $inv || $inv->isAccepted() || $inv->isExpired() || ! Hash::check($rawToken, $inv->token_hash)) {
            throw new RuntimeException('This invitation link is invalid or has expired.');
        }
        return $inv;
    }

    /** Accept: set password, activate the user, ensure the role assignment is live. Single-use. */
    public function accept(int $invitationId, string $rawToken, string $password): void {
        DB::transaction(function () use ($invitationId, $rawToken, $password) {
            $inv = $this->verify($invitationId, $rawToken);
            $user = $inv->user;
            if (! $user) {
                throw new RuntimeException('This invitation has no user account.');
            }

            // Password cast is 'hashed' — pass plaintext.
            $user->forceFill(['password' => $password, 'status' => 'active'])->save();

            if ($inv->school_id) {
                $roleId = Role::where('key', $inv->role_key)->value('id');
                RoleAssignment::where('user_id', $user->id)->where('role_id', $roleId)
                    ->where('school_id', $inv->school_id)->update(['is_active' => true]);
            }
            $inv->update(['accepted_at' => now()]);
            $this->audit->record('invitation.accepted', $inv, ['school_id'=>$inv->school_id,'role'=>$inv->role_key]);
        });
    }
}
