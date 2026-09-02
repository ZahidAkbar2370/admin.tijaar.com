<?php

namespace App\Notifications;

use App\Mail\TemplateMail;
use App\Mail\VerificationOtpMail;
use App\Models\EmailTemplate;
use Illuminate\Notifications\Notification;

class EmailVerificationOtpNotification extends Notification
{
    /** @param int $expiryMinutes Minutes until OTP expires (for template placeholder) */
    public function __construct(
        public string $otpCode,
        public int $expiryMinutes = 10,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): VerificationOtpMail|TemplateMail
    {
        $appName = config('app.name', 'Tijaar');
        $template = EmailTemplate::getBySlug('verify_email')
            ?: EmailTemplate::getBySlug('otp_verification');
        if ($template) {
            $data = [
                'name' => $notifiable->name ?? 'User',
                'otp' => $this->otpCode,
                'expiry_minutes' => (string) $this->expiryMinutes,
                'app_name' => $appName,
            ];
            $subject = $template->replaceSubject($data);
            $bodyHtml = $template->replacePlaceholders($data);
            $bodyPlain = $template->body_plain ? EmailTemplate::replaceInString($template->body_plain, $data) : null;
            return new TemplateMail($subject, $bodyHtml, $bodyPlain);
        }

        return new VerificationOtpMail($this->otpCode);
    }
}
