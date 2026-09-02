<?php

namespace App\Notifications;

use App\Mail\TemplateMail;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Notifications\Concerns\SkipsMailWithoutEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SkipsMailWithoutEmail;

    public function __construct(
        public Order $order,
        public string $status
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channelsFor($notifiable, ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage|TemplateMail
    {
        $appName = config('app.name', 'Tijaar');
        $orderUrl = config('app.frontend_url', 'http://localhost:3001') . '/account/orders/' . $this->order->id;
        $to = $notifiable->routeNotificationFor('mail', $this)
            ?? (is_string($notifiable->email ?? null) ? trim($notifiable->email) : null);
        if (! is_string($to) || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            // Should not be reached when channelsFor strips mail — safety for queued jobs.
            return (new MailMessage)->subject('Skipped')->line('No recipient');
        }

        $template = EmailTemplate::getBySlug('order_status');
        if ($template) {
            $data = [
                'name' => $notifiable->name ?? 'Customer',
                'order_number' => $this->order->order_number,
                'status' => $this->status,
                'order_url' => $orderUrl,
                'app_name' => $appName,
            ];
            $subject = $template->replaceSubject($data);
            $bodyHtml = $template->replacePlaceholders($data);
            $bodyPlain = $template->body_plain ? EmailTemplate::replaceInString($template->body_plain, $data) : null;

            return (new TemplateMail($subject, $bodyHtml, $bodyPlain))->to($to);
        }

        return (new MailMessage)
            ->subject('Order #' . $this->order->order_number . ' – ' . $this->status)
            ->greeting('Order update')
            ->line('Your order **#' . $this->order->order_number . '** status: ' . $this->status)
            ->action('View order', $orderUrl)
            ->line('Thank you, ' . $appName);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_status',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->status,
            'title' => "Order #{$this->order->order_number}",
            'message' => "Your order status: {$this->status}",
        ];
    }
}
