<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WishlistPriceDropNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Product $product,
        public float $oldPrice,
        public float $newPrice
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.frontend_url', 'http://localhost:3000') . '/product/' . $this->product->slug;
        return (new MailMessage)
            ->subject("Price drop: {$this->product->name}")
            ->line("A product in your wishlist has dropped in price!")
            ->line("{$this->product->name}: {$this->oldPrice} → {$this->newPrice}")
            ->action('View Product', $url);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'wishlist_price_drop',
            'title' => 'Price drop',
            'message' => "{$this->product->name} dropped from {$this->oldPrice} to {$this->newPrice}",
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'old_price' => $this->oldPrice,
            'new_price' => $this->newPrice,
        ];
    }
}
