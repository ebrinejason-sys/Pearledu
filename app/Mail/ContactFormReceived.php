<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $message,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'VoxSign contact form',
            replyTo: [new Address($this->email, $this->name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-form-received',
            // Note: Laravel's Mailer unconditionally injects a `$message` view
            // variable (an Illuminate\Mail\Message wrapper) into every mail view,
            // which would otherwise shadow this Mailable's own `$message` string
            // property. Remap it to `$messageBody` so the Blade view can render
            // the actual contact form message.
            with: ['messageBody' => $this->message],
        );
    }
}
