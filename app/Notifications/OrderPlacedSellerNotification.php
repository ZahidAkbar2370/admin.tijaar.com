<?php

namespace App\Notifications;

use App\Mail\TemplateMail;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Notifications\Concerns\SkipsMailWithoutEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlacedSellerNotification extends Notification
{
    use SkipsMailWithoutEmail;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return $this->channelsFor($notifiable, ['mail', 'database']);
    }

    public function toMail(object $notifiable): MailMessage|TemplateMail
    {
        $appName = config('app.name', 'Tijaar');
        $currency = $this->order->market === 'AE' ? 'AED' : 'PKR';
        $orderUrl = config('app.frontend_url', 'http://localhost:3001') . '/seller/orders/' . $this->order->id;
        $to = $notifiable->routeNotificationFor('mail', $this)
            ?? (is_string($notifiable->email ?? null) ? trim($notifiable->email) : null);
        if (! is_string($to) || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return (new MailMessage)->subject('Skipped')->line('No recipient');
        }

        $template = EmailTemplate::getBySlug('order_placed_seller');
        if ($template) {
            $data = [
                'seller_name' => $notifiable->name ?? 'Seller',
                'order_number' => $this->order->order_number,
                'order_total' => number_format((float) $this->order->total, 2),
                'currency' => $currency,
                'order_url' => $orderUrl,
                'app_name' => $appName,
            ];
            $subject = $template->replaceSubject($data);
            $bodyHtml = $template->replacePlaceholders($data);
            $bodyPlain = $template->body_plain ? EmailTemplate::replaceInString($template->body_plain, $data) : null;

            return (new TemplateMail($subject, $bodyHtml, $bodyPlain))->to($to);
        }

        return (new MailMessage)
            ->subject('New order #' . $this->order->order_number . ' – ' . $appName)
            ->greeting('New order received')
            ->line('A customer has placed an order that includes your products.')
            ->line('Order #' . $this->order->order_number . ' – Total: ' . number_format((float) $this->order->total, 2) . ' ' . $currency)
            ->action('View order', $orderUrl)
            ->line('Thank you for selling on ' . $appName);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_placed_seller',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'title' => 'New order #' . $this->order->order_number,
            'message' => 'A customer placed an order containing your products.',
        ];
    }
}
