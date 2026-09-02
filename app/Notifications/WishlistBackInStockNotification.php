<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WishlistBackInStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.frontend_url', 'http://localhost:3000') . '/product/' . $this->product->slug;
        return (new MailMessage)
            ->subject("Back in stock: {$this->product->name}")
            ->line("A product in your wishlist is back in stock!")
            ->action('View Product', $url);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'wishlist_back_in_stock',
            'title' => 'Back in stock',
            'message' => "{$this->product->name} is back in stock",
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
        ];
    }
}
