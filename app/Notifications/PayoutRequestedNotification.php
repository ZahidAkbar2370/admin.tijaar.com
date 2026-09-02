<?php

namespace App\Notifications;

use App\Mail\TemplateMail;
use App\Models\EmailTemplate;
use App\Models\Payout;
use App\Notifications\Concerns\SkipsMailWithoutEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutRequestedNotification extends Notification
{
    use SkipsMailWithoutEmail;

    public function __construct(public Payout $payout) {}

    public function via(object $notifiable): array
    {
        return $this->channelsFor($notifiable, ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage|TemplateMail
    {
        $appName = config('app.name', 'Tijaar');
        $payoutsUrl = config('app.frontend_url', 'http://localhost:3001') . '/seller/payouts';
        $template = EmailTemplate::getBySlug('payout_requested');
        if ($template) {
            $data = [
                'name' => $notifiable->name ?? 'Seller',
                'payout_number' => $this->payout->payout_number,
                'amount' => number_format((float) $this->payout->amount, 2),
                'payouts_url' => $payoutsUrl,
                'app_name' => $appName,
            ];
            $subject = $template->replaceSubject($data);
            $bodyHtml = $template->replacePlaceholders($data);
            $bodyPlain = $template->body_plain ? EmailTemplate::replaceInString($template->body_plain, $data) : null;
            return new TemplateMail($subject, $bodyHtml, $bodyPlain);
        }
        return (new MailMessage)
            ->subject('Payout request submitted – ' . $appName)
            ->greeting('Payout request received')
            ->line('Your payout request has been submitted successfully.')
            ->line('Payout #' . $this->payout->payout_number . ' – Amount: ' . number_format((float) $this->payout->amount, 2) . ' PKR')
            ->line('Status: Pending admin approval. You will receive an email once it is approved.')
            ->action('View payouts', $payoutsUrl)
            ->line('Thank you for selling on ' . $appName);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payout_requested',
            'payout_id' => $this->payout->id,
            'payout_number' => $this->payout->payout_number,
            'amount' => $this->payout->amount,
            'title' => 'Payout request submitted',
            'message' => 'Payout #' . $this->payout->payout_number . ' is pending approval.',
        ];
    }
}
