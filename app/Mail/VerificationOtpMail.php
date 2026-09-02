<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otpCode
    ) {}

    public function envelope(): Envelope
    {
        $from = config('mail.from.address', 'noreply@example.com');
        $name = config('mail.from.name', config('app.name', 'Tijaar'));
        return new Envelope(
            subject: config('app.name', 'Tijaar') . ' – Verify Your Email',
            from: new \Illuminate\Mail\Mailables\Address($from, $name),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verification-otp',
        );
    }
}
