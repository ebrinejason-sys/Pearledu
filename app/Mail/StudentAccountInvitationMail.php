<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentAccountInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $learnerName,
        public string $schoolName,
        public string $studentRecordName,
        public string $acceptUrl,
        public string $schoolUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'VoxSign'),
            subject: "Your student portal account — {$this->schoolName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student-account-invitation',
        );
    }
}
