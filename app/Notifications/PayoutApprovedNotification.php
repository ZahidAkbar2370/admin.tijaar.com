<?php

namespace App\Notifications;

use App\Mail\TemplateMail;
use App\Models\EmailTemplate;
use App\Models\Payout;
use App\Notifications\Concerns\SkipsMailWithoutEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutApprovedNotification extends Notification
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
        $transactionsUrl = config('app.frontend_url', 'http://localhost:3001') . '/seller/transactions';
        $template = EmailTemplate::getBySlug('payout_approved');
        if ($template) {
            $data = [
                'name' => $notifiable->name ?? 'Seller',
                'payout_number' => $this->payout->payout_number,
                'amount' => number_format((float) $this->payout->amount, 2),
                'transactions_url' => $transactionsUrl,
                'app_name' => $appName,
            ];
            $subject = $template->replaceSubject($data);
            $bodyHtml = $template->replacePlaceholders($data);
            $bodyPlain = $template->body_plain ? EmailTemplate::replaceInString($template->body_plain, $data) : null;
            return new TemplateMail($subject, $bodyHtml, $bodyPlain);
        }
        return (new MailMessage)
            ->subject('Payout approved – #' . $this->payout->payout_number . ' – ' . $appName)
            ->greeting('Payout approved')
            ->line('Your payout request has been approved by admin.')
            ->line('Payout #' . $this->payout->payout_number . ' – Amount: ' . number_format((float) $this->payout->amount, 2) . ' PKR')
            ->line('The amount has been deducted from your wallet. You will receive the transfer according to your payout method and bank details.')
            ->action('View transaction history', $transactionsUrl)
            ->line('Thank you for selling on ' . $appName);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payout_approved',
            'payout_id' => $this->payout->id,
            'payout_number' => $this->payout->payout_number,
            'amount' => $this->payout->amount,
            'title' => 'Payout approved',
            'message' => 'Payout #' . $this->payout->payout_number . ' has been approved.',
        ];
    }
}
