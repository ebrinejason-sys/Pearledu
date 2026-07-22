<?php

namespace App\Services\Auth;

use App\Models\School;
use App\Models\SchoolInvitation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/** Back-compat wrapper — prefer InvitationDispatcher for new code. */
class InvitationMailer
{
    public function __construct(private InvitationDispatcher $dispatcher) {}

    public function send(SchoolInvitation $invitation, string $rawToken, School $school): void
    {
        $this->dispatcher->send($invitation, $rawToken, $school);
    }

    public function resend(SchoolInvitation $invitation, School $school, ?int $operatorId = null): string
    {
        if ($invitation->isAccepted()) {
            throw new RuntimeException('This invitation was already accepted.');
        }

        $raw = Str::random(48);
        $invitation->update([
            'token_hash' => Hash::make($raw),
            'expires_at' => now()->addDays(7),
            'invited_by' => $operatorId ?? $invitation->invited_by,
        ]);

        $this->dispatcher->send($invitation->fresh(), $raw, $school);

        return $raw;
    }
}
