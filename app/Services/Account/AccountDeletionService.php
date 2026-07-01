<?php
namespace App\Services\Account;
use App\Models\Guardianship;
use App\Models\RoleAssignment;
use App\Models\SchoolInvitation;
use App\Models\Student;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * Erases a person's personal data from the database (DPPA 2019 right to erasure).
 *
 * Design notes:
 *  - Runs in PLATFORM scope so a user's data across ALL their schools is reachable.
 *  - The user IDENTITY and its personal links are hard-deleted (forceDelete).
 *  - A child's ACADEMIC record belongs to the school (legal record), not the
 *    guardian's account, so we DETACH the login link rather than destroy the
 *    school's record. If the user IS a learner, the login identity is removed
 *    and the learner row is de-identified, not silently destroyed.
 *  - Audit rows are retained but de-identified (actor_id FK nulls on delete);
 *    they contain ids, not names. See docs/DATA_PROTECTION.md for retention.
 */
class AccountDeletionService {
    public function __construct(private AuditLogger $audit, private TenantContext $context) {}

    public function erase(User $user, string $requestedBy = 'self'): void {
        $this->context->forPlatform();

        DB::transaction(function () use ($user, $requestedBy) {
            $userId = $user->id;

            // 1. Remove guardian links (the child's record stays with the school).
            Guardianship::withoutGlobalScopes()->where('guardian_user_id', $userId)->delete();

            // 2. Detach + de-identify any learner record tied to this login.
            Student::withoutGlobalScopes()->where('user_id', $userId)->each(function (Student $s) {
                $s->forceFill(['user_id' => null, 'nin' => null, 'lin' => null])->save();
            });

            // 3. Remove role assignments (permissions) everywhere.
            RoleAssignment::where('user_id', $userId)->delete();

            // 4. Invalidate outstanding invitations.
            SchoolInvitation::where('user_id', $userId)->delete();

            // 5. Audit BEFORE the identity is gone (actor link will null afterwards).
            $this->audit->record('account.erased', null, ['user_id'=>$userId,'requested_by'=>$requestedBy]);

            // 6. Hard-delete the identity itself (truly erased, not soft-deleted).
            $user->forceDelete();
        });
    }
}
