<?php
namespace App\Mail\Auth;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SchoolInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $schoolName,
        public string $acceptUrl,
        public string $schoolUrl,
        public \DateTimeInterface $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'You are invited to '.$this->schoolName.' on '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.auth.school-invitation');
    }
}
