<?php
namespace App\Services\Platform;
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

    public function __construct(
        private AuditLogger $audit,
        private TenantContext $context,
    ) {}

    public function isActive(): bool
    {
        return session()->has(self::SESSION_OPERATOR);
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

    public function operator(): ?User
    {
        $id = $this->operatorId();
        return $id ? User::find($id) : null;
    }

    public function start(User $operator, User $target, School $school): void
    {
        if (! $operator->isPlatformOperator()) {
            throw ValidationException::withMessages(['user' => 'Only platform operators can imitate accounts.']);
        }
        if ($this->isActive()) {
            throw ValidationException::withMessages(['user' => 'Stop the current imitation session first.']);
        }
        if ($target->isPlatformOperator()) {
            throw ValidationException::withMessages(['user' => 'Platform accounts cannot be imitated.']);
        }
        if ($target->status !== 'active') {
            throw ValidationException::withMessages(['user' => 'Only active accounts can be imitated.']);
        }

        $belongs = RoleAssignment::query()
            ->where('user_id', $target->id)
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->exists();
        if (! $belongs) {
            throw ValidationException::withMessages(['user' => 'This user has no active role at the selected school.']);
        }

        session()->put(self::SESSION_OPERATOR, $operator->id);
        session()->put(self::SESSION_SCHOOL, $school->id);
        session()->forget('platform.entered_school_id');

        Auth::login($target);
        session()->regenerate();
        $this->context->forSchool($school->id);

        $this->audit->record('user.impersonation.started', $target, [
            'operator_id' => $operator->id,
            'school_id' => $school->id,
            'school_slug' => $school->slug,
        ]);
    }

    public function stop(): void
    {
        if (! $this->isActive()) {
            throw ValidationException::withMessages(['session' => 'No active imitation session.']);
        }

        $operatorId = $this->operatorId();
        $target = Auth::user();
        $schoolId = $this->schoolId();

        session()->forget([self::SESSION_OPERATOR, self::SESSION_SCHOOL]);

        $operator = User::findOrFail($operatorId);
        Auth::login($operator);
        session()->regenerate();
        $this->context->forPlatform();

        $this->audit->record('user.impersonation.stopped', $target, [
            'operator_id' => $operatorId,
            'school_id' => $schoolId,
        ]);
    }
}
