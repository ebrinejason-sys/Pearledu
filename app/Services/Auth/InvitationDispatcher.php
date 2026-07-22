<?php

namespace App\Services\Auth;

use App\Mail\Auth\SchoolInvitationMail;
use App\Models\School;
use App\Models\SchoolInvitation;
use App\Services\Sms\Gateway\SmsGateway;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use RuntimeException;

/** Deliver activation invites by email and/or SMS (transactional — no SMS credit charge). */
class InvitationDispatcher
{
    public function __construct(
        private SmsGateway $sms,
    ) {}

    public function send(SchoolInvitation $invitation, string $rawToken, School $school): void
    {
        $user = $invitation->user;
        if (! $user) {
            throw new RuntimeException('This invitation has no user account.');
        }

        $acceptUrl = URL::route('invitations.accept', [
            'invitation' => $invitation->id,
            'token' => $rawToken,
        ]);

        $sent = false;

        if ($user->email || $invitation->email) {
            $to = $user->email ?: $invitation->email;
            Mail::to($to)->send(new SchoolInvitationMail(
                $user,
                $school->name,
                $acceptUrl,
                $school->subdomainUrl(),
                $invitation->expires_at,
            ));
            $sent = true;
        }

        $phone = $invitation->phone ?: $user->phone;
        if ($phone) {
            $body = "PearlEdu: You are invited to {$school->name}. Set your password: {$acceptUrl}";
            $this->sms->send($phone, $body, null);
            $sent = true;
        }

        if (! $sent) {
            throw new RuntimeException('Invitation needs an email or phone number to deliver.');
        }
    }
}
