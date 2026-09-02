<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ListingPendingApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $customerName,
        public string $productName,
        public string $dashboardUrl = '',
    ) {
        $frontend = config('app.frontend_url', 'http://localhost:3000');
        $this->dashboardUrl = $dashboardUrl ?: $frontend . '/vendor/dashboard';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your listing is pending approval – Tijaar',
            from: config('mail.from.address'),
            replyTo: [config('mail.from.address')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.listing-pending-approval',
        );
    }
}
