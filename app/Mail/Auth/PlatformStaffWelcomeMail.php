<?php

namespace App\Mail\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlatformStaffWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $setPasswordUrl;

    public function __construct(
        public User $user,
        public string $roleLabel,
        public string $temporaryPassword,
        public string $loginUrl,
        public bool $isPasswordReset = false,
    ) {
        $this->setPasswordUrl = preg_replace('#/login/?$#', '/forgot-password', $loginUrl)
            ?: rtrim($loginUrl, '/').'/forgot-password';
    }

    public function envelope(): Envelope
    {
        $subject = $this->isPasswordReset
            ? 'Your PearlEdu admin password was reset'
            : 'Your PearlEdu staff account';

        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.auth.platform-staff-welcome');
    }
}
