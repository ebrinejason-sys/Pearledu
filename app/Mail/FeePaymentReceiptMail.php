<?php

namespace App\Mail;

use App\Models\FeePayment;
use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeePaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public School $school,
        public FeePayment $payment,
    ) {}

    public function envelope(): Envelope
    {
        $ref = $this->payment->invoice?->reference ?? (string) $this->payment->id;

        return new Envelope(
            subject: 'Fee receipt '.$ref.' · '.$this->school->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.fee-payment-receipt',
        );
    }
}
