<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SchoolOnboardingRequestReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $schoolName,
        public string $contactName,
        public string $email,
        public string $phone,
        public string $message,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'PearlEdu onboarding request: '.$this->schoolName,
            replyTo: [new Address($this->email, $this->contactName)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.school-onboarding-request-received',
            with: ['messageBody' => $this->message],
        );
    }
}
