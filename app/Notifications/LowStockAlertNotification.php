<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.frontend_url', 'http://localhost:3000') . '/vendor/products';
        return (new MailMessage)
            ->subject("Low stock alert: {$this->product->name}")
            ->line("Product \"{$this->product->name}\" has low stock ({$this->product->quantity} remaining).")
            ->action('Manage Inventory', $url);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'title' => 'Low stock alert',
            'message' => "{$this->product->name} has {$this->product->quantity} units left.",
            'product_id' => $this->product->id,
        ];
    }
}
