<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OutOfStockAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isAdmin = ($notifiable->role ?? '') === 'admin';
        $url = $isAdmin
            ? rtrim(config('app.url'), '/') . '/admin/inventory/out-of-stock'
            : rtrim(config('app.frontend_url', 'http://localhost:3000'), '/') . '/seller/products';

        return (new MailMessage)
            ->subject("Out of stock: {$this->product->name}")
            ->line("Product \"{$this->product->name}\" is now out of stock.")
            ->line('It is hidden from the public shop until stock is added.')
            ->action($isAdmin ? 'View out of stock' : 'Update inventory', $url);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'out_of_stock',
            'title' => 'Out of stock',
            'message' => "{$this->product->name} is out of stock and hidden from the shop.",
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
        ];
    }
}
