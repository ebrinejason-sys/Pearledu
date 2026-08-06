<?php

namespace App\Services\Platform;

use App\Models\HelpdeskTicket;
use App\Models\RoleAssignment;
use App\Models\School;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/** Platform operators can view the app as a school user. Every start/stop is audited. */
class ImpersonationService
{
    public const SESSION_OPERATOR = 'impersonation.operator_id';

    public const SESSION_SCHOOL = 'impersonation.school_id';

    public const SESSION_REASON = 'impersonation.reason';

    public const SESSION_TICKET = 'impersonation.ticket_id';

    public const SESSION_STARTED = 'impersonation.started_at';

    public const SESSION_EXPIRES = 'impersonation.expires_at';

    public const SESSION_WRITE = 'impersonation.elevated_write';

    /** Absolute max imitation session length (minutes). */
    public const MAX_MINUTES = 60;

    public function __construct(
        private AuditLogger $audit,
        private TenantContext $context,
    ) {}

    public function isActive(): bool
    {
        if (! session()->has(self::SESSION_OPERATOR)) {
            return false;
        }

        $expires = (int) session(self::SESSION_EXPIRES, 0);
        if ($expires > 0 && time() > $expires) {
            $this->forceExpire();

            return false;
        }

        return true;
    }

    public function operatorId(): ?int
    {
        $id = session(self::SESSION_OPERATOR);

        return $id ? (int) $id : null;
    }

    public function schoolId(): ?int
    {
        $id = session(self::SESSION_SCHOOL);

        return $id ? (int) $id : null;
    }

    public function reason(): ?string
    {
        $r = session(self::SESSION_REASON);

        return is_string($r) && $r !== '' ? $r : null;
    }

    public function allowsWrites(): bool
    {
        return (bool) session(self::SESSION_WRITE, false);
    }

    public function operator(): ?User
    {
        $id = $this->operatorId();

        return $id ? User::find($id) : null;
    }

    public function grantsFullSchoolAccess(User $target, int $schoolId): bool
    {
        if (! $this->isActive() || ! $this->allowsWrites()) {
            return false;
        }
        if ((int) Auth::id() !== (int) $target->id || (int) $this->schoolId() !== $schoolId) {
            return false;
        }

        // SESSION_WRITE is set only after the operator permission check in start().
        // Do not re-query the operator's platform RoleAssignment here: school RLS
        // intentionally hides platform-scoped (school_id = null) assignments.
        return $this->operatorId() !== null;
    }

    /**
     * @param  array{reason: string, ticket_id?: int|string|null, elevated_write?: bool}  $options
     */
    public function start(User $operator, User $target, School $school, array $options): void
    {
        if (! $operator->isPlatformOperator()) {
            throw ValidationException::withMessages(['user' => 'Only platform operators can imitate accounts.']);
        }
        if (! $operator->hasPlatformPermission('platform.users.impersonate')) {
            throw ValidationException::withMessages(['user' => 'You do not have permission to imitate accounts.']);
        }
        if ($this->isActive()) {
            throw ValidationException::withMessages(['user' => 'Stop the current imitation session first.']);
        }
        if ($target->isPlatformOperator()) {
            throw ValidationException::withMessages(['user' => 'Platform accounts cannot be imitated.']);
        }
        if (! in_array($target->status, ['active', 'invited'], true)) {
            throw ValidationException::withMessages(['user' => 'Only active or invited accounts can be imitated.']);
        }

        $reason = trim((string) ($options['reason'] ?? ''));
        if (strlen($reason) < 8) {
            throw ValidationException::withMessages(['reason' => 'Provide a support reason (at least 8 characters).']);
        }

        $belongs = RoleAssignment::query()
            ->where('user_id', $target->id)
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->exists();
        if (! $belongs) {
            throw ValidationException::withMessages(['user' => 'This user has no active role at the selected school.']);
        }

        $elevated = ! empty($options['elevated_write']);
        if ($elevated && ! $operator->hasPlatformPermission('platform.users.impersonate_write')) {
            throw ValidationException::withMessages(['elevated_write' => 'You cannot start an elevated write imitation session.']);
        }

        $started = time();
        $expires = $started + (self::MAX_MINUTES * 60);
        $ticket = isset($options['ticket_id']) ? trim((string) $options['ticket_id']) : null;
        if ($ticket === '') {
            $ticket = null;
        }
        if ($elevated && $ticket === null) {
            throw ValidationException::withMessages([
                'ticket_id' => 'A support ticket is required for elevated write imitation.',
            ]);
        }
        if ($ticket !== null) {
            $supportTicket = HelpdeskTicket::query()->find($ticket);
            if (! $supportTicket || (int) $supportTicket->school_id !== (int) $school->id) {
                throw ValidationException::withMessages([
                    'ticket_id' => 'Select a support ticket belonging to this school.',
                ]);
            }
        }

        // Audit BEFORE Auth::login so actor_id stays the operator.
        // Attach the event to the affected school so it remains visible under
        // that school's RLS scope as well as from the platform audit console.
        $this->context->forSchool($school->id);
        $this->audit->record('user.impersonation.started', $target, [
            'operator_id' => $operator->id,
            'target_id' => $target->id,
            'school_id' => $school->id,
            'school_slug' => $school->slug,
            'reason' => $reason,
            'ticket_id' => $ticket,
            'elevated_write' => $elevated,
            'expires_at' => $expires,
        ], actor: $operator);

        session()->put(self::SESSION_OPERATOR, $operator->id);
        session()->put(self::SESSION_SCHOOL, $school->id);
        session()->put(self::SESSION_REASON, $reason);
        session()->put(self::SESSION_TICKET, $ticket);
        session()->put(self::SESSION_STARTED, $started);
        session()->put(self::SESSION_EXPIRES, $expires);
        session()->put(self::SESSION_WRITE, $elevated);
        session()->forget('platform.entered_school_id');

        Auth::login($target);
        session()->regenerate();
        $this->context->forSchool($school->id);
    }

    public function stop(): void
    {
        if (! session()->has(self::SESSION_OPERATOR)) {
            throw ValidationException::withMessages(['session' => 'No active imitation session.']);
        }

        $operatorId = $this->operatorId();
        $target = Auth::user();
        $schoolId = $this->schoolId();
        $reason = $this->reason();
        $operator = User::findOrFail($operatorId);

        session()->forget([
            self::SESSION_OPERATOR,
            self::SESSION_SCHOOL,
            self::SESSION_REASON,
            self::SESSION_TICKET,
            self::SESSION_STARTED,
            self::SESSION_EXPIRES,
            self::SESSION_WRITE,
        ]);

        Auth::login($operator);
        session()->regenerate();
        $this->context->forPlatform();

        $this->audit->record('user.impersonation.stopped', $target, [
            'operator_id' => $operatorId,
            'target_id' => $target?->id,
            'school_id' => $schoolId,
            'reason' => $reason,
        ], actor: $operator);
    }

    private function forceExpire(): void
    {
        $operatorId = $this->operatorId();
        $target = Auth::user();
        $schoolId = $this->schoolId();
        $reason = $this->reason();

        session()->forget([
            self::SESSION_OPERATOR,
            self::SESSION_SCHOOL,
            self::SESSION_REASON,
            self::SESSION_TICKET,
            self::SESSION_STARTED,
            self::SESSION_EXPIRES,
            self::SESSION_WRITE,
        ]);

        if ($operatorId) {
            $operator = User::find($operatorId);
            if ($operator) {
                Auth::login($operator);
                session()->regenerate();
                $this->context->forPlatform();
                $this->audit->record('user.impersonation.expired', $target, [
                    'operator_id' => $operatorId,
                    'target_id' => $target?->id,
                    'school_id' => $schoolId,
                    'reason' => $reason,
                ], actor: $operator);
            }
        }
    }
}
