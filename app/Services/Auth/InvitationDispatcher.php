<?php

namespace App\Services\Auth;

use App\Mail\Auth\SchoolInvitationMail;
use App\Models\School;
use App\Models\SchoolInvitation;
use App\Services\Sms\Gateway\SmsGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

/** Deliver activation invites by email and/or SMS (transactional — no SMS credit charge). */
class InvitationDispatcher
{
    public function __construct(
        private SmsGateway $sms,
    ) {}

    /**
     * @return array{email: bool, sms: bool, warnings: list<string>}
     */
    public function send(SchoolInvitation $invitation, string $rawToken, School $school): array
    {
        $user = $invitation->user;
        if (! $user) {
            throw new RuntimeException('This invitation has no user account.');
        }

        // Prefer the school's portal host so invite links never depend on a wrong APP_URL.
        $acceptUrl = rtrim($school->portalUrl(), '/').'/invitations/'.$invitation->id.'/accept?token='.urlencode($rawToken);

        $emailed = false;
        $texted = false;
        $warnings = [];

        $toEmail = $user->email ?: $invitation->email;
        if ($toEmail) {
            $from = (string) config('mail.from.address');
            if ($from === '' || str_contains($from, 'example.com')) {
                $warnings[] = 'MAIL_FROM_ADDRESS is not configured, so the invitation email was not sent.';
            } else {
                try {
                    Mail::to($toEmail)->send(new SchoolInvitationMail(
                        $user,
                        $school->name,
                        $acceptUrl,
                        $school->portalUrl(),
                        $invitation->expires_at,
                    ));
                    $emailed = true;
                } catch (Throwable $e) {
                    report($e);
                    $warnings[] = 'Invitation email failed: '.$e->getMessage();
                }
            }
        }

        $phone = $invitation->phone ?: $user->phone;
        if ($phone) {
            try {
                $body = "PearlEdu: You are invited to {$school->name}. Set your password: {$acceptUrl}";
                $this->sms->send($phone, $body, null);
                $texted = true;
            } catch (Throwable $e) {
                report($e);
                Log::warning('Invitation SMS failed', [
                    'invitation_id' => $invitation->id,
                    'error' => $e->getMessage(),
                ]);
                $warnings[] = 'Invitation SMS could not be sent ('.$e->getMessage().').';
            }
        }

        if (! $emailed && ! $texted) {
            $detail = $warnings !== [] ? ' '.implode(' ', $warnings) : '';
            throw new RuntimeException(
                'Invitation was created but could not be delivered by email or SMS.'.$detail
                .' Share this activation link out-of-band: '.$acceptUrl
            );
        }

        return ['email' => $emailed, 'sms' => $texted, 'warnings' => $warnings, 'accept_url' => $acceptUrl];
    }
}
