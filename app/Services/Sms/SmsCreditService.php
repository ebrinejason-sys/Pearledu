<?php
namespace App\Services\Sms;
use App\Models\School;
use App\Models\SmsCreditEntry;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * SMS credit is resold by the platform: only the platform admin tops schools up;
 * schools spend credit when sending. The ledger is append-only; balance is the
 * latest balance_after, computed under a row lock to avoid race conditions.
 */
class SmsCreditService {
    public function __construct(private AuditLogger $audit, private TenantContext $context) {}

    public function balance(int $schoolId): int {
        return (int) (SmsCreditEntry::withoutGlobalScopes()->where('school_id', $schoolId)
            ->orderByDesc('id')->value('balance_after') ?? 0);
    }

    /** Platform admin grants credit to a school. */
    public function topUp(School $school, int $credits, ?int $actorId, ?string $reference = null): SmsCreditEntry {
        if ($credits <= 0) throw new RuntimeException('Top-up must be positive.');
        return $this->append($school->id, $credits, 'topup', $reference, $actorId);
    }

    /** Spend credit (called by the send pipeline). Throws if insufficient. */
    public function spend(int $schoolId, int $credits, string $reference, ?int $actorId): SmsCreditEntry {
        if ($credits <= 0) throw new RuntimeException('Spend must be positive.');
        return $this->append($schoolId, -$credits, 'send', $reference, $actorId);
    }

    private function append(int $schoolId, int $delta, string $reason, ?string $ref, ?int $actorId): SmsCreditEntry {
        return DB::transaction(function () use ($schoolId, $delta, $reason, $ref, $actorId) {
            // lock the latest row to serialize concurrent writers
            $last = DB::table('sms_credit_ledger')->where('school_id', $schoolId)
                ->orderByDesc('id')->lockForUpdate()->first();
            $current = (int) ($last->balance_after ?? 0);
            $next = $current + $delta;
            if ($next < 0) throw new RuntimeException('Insufficient SMS credit.');

            $entry = SmsCreditEntry::create([
                'school_id' => $schoolId, 'delta' => $delta, 'balance_after' => $next,
                'reason' => $reason, 'reference' => $ref, 'actor_id' => $actorId, 'created_at' => now(),
            ]);
            $this->audit->record('sms.credit.'.$reason, $entry, ['delta'=>$delta,'balance'=>$next]);
            return $entry;
        });
    }
}
