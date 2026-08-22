<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Idle logout for school MIS sessions. Remember-me cannot resurrect an expired idle window. */
class IdleSessionService
{
    public function lifetimeMinutes(): int
    {
        return max(1, (int) config('session.lifetime', 30));
    }

    public function warningMinutes(): int
    {
        $warning = max(1, (int) config('session.idle_warning_minutes', 2));
        $life = $this->lifetimeMinutes();

        return min($warning, max(1, $life - 1));
    }

    public function isExpired(User $user): bool
    {
        if ($user->last_seen_at === null) {
            return false;
        }

        return $user->last_seen_at->lte(now()->subMinutes($this->lifetimeMinutes()));
    }

    public function remainingSeconds(User $user): int
    {
        if ($user->last_seen_at === null) {
            return $this->lifetimeMinutes() * 60;
        }

        $expiresAt = $user->last_seen_at->copy()->addMinutes($this->lifetimeMinutes());

        return max(0, $expiresAt->getTimestamp() - now()->getTimestamp());
    }

    public function touch(User $user, bool $force = false): void
    {
        $now = now();
        if (! $force && $user->last_seen_at && $user->last_seen_at->gt($now->copy()->subMinute())) {
            return;
        }

        $user->forceFill(['last_seen_at' => $now])->save();
    }

    public function expire(Request $request, User $user): void
    {
        Auth::logout();
        app(SessionInvalidator::class)->invalidate($user);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function expiryMessage(): string
    {
        $minutes = $this->lifetimeMinutes();

        return 'You were signed out after '.$minutes.' '.($minutes === 1 ? 'minute' : 'minutes').' of inactivity. Sign in again to continue.';
    }
}
