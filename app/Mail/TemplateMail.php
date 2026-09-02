<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var string */
    public $bodyHtml;

    /** @var string|null */
    public $bodyPlain;

    public function __construct($subject, $bodyHtml, $bodyPlain = null)
    {
        $this->subject($subject);
        $this->bodyHtml = $bodyHtml;
        $this->bodyPlain = $bodyPlain;
    }

    public function send($mailer)
    {
        // Laravel MailChannel does not auto-attach recipients when toMail() returns a Mailable.
        if (empty($this->to) && empty($this->cc) && empty($this->bcc)) {
            \Illuminate\Support\Facades\Log::warning('TemplateMail skipped: missing To/Cc/Bcc recipient');

            return null;
        }

        return parent::send($mailer);
    }

    public function envelope(): Envelope
    {
        // Do not pass empty to/cc/bcc — that clears recipients set via ->to().
        return new Envelope(
            subject: is_string($this->subject) ? $this->subject : null,
        );
    }

    public function content(): Content
    {
        return new Content(
            'emails.template',
            null,
            null,
            null,
            [
                'bodyHtml' => $this->bodyHtml,
                'bodyPlain' => $this->bodyPlain,
            ]
        );
    }
}
