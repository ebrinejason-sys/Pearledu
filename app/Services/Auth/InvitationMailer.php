<?php
namespace App\Services\Auth;
use App\Mail\Auth\SchoolInvitationMail;
use App\Models\School;
use App\Models\SchoolInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class InvitationMailer
{
    public function send(SchoolInvitation $invitation, string $rawToken, School $school): void
    {
        $user = $invitation->user;
        if (! $user?->email) {
            throw new RuntimeException('This invitation has no email address to send to.');
        }

        $acceptUrl = URL::route('invitations.accept', [
            'invitation' => $invitation->id,
            'token' => $rawToken,
        ]);

        Mail::to($user->email)->send(new SchoolInvitationMail(
            $user,
            $school->name,
            $acceptUrl,
            $school->subdomainUrl(),
            $invitation->expires_at,
        ));
    }

    /** Re-issue a fresh token and email it (platform operators only). Expired invites can be renewed. */
    public function resend(SchoolInvitation $invitation, School $school, ?int $operatorId = null): string
    {
        if ($invitation->isAccepted()) {
            throw new RuntimeException('This invitation was already accepted.');
        }

        $raw = \Illuminate\Support\Str::random(48);
        $invitation->update([
            'token_hash' => \Illuminate\Support\Facades\Hash::make($raw),
            'expires_at' => now()->addDays(7),
            'invited_by' => $operatorId ?? $invitation->invited_by,
        ]);

        $this->send($invitation->fresh(), $raw, $school);
        return $raw;
    }
}
