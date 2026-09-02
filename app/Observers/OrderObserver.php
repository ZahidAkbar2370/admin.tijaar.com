<?php

namespace App\Observers;

use App\Events\PushNotificationRequested;
use App\Models\Order;
use App\Notifications\OrderStatusNotification;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if ($order->isDirty('status')) {
            try {
                $order->user?->notify(new OrderStatusNotification($order, $order->status));
            } catch (\Throwable $e) {
                \Log::warning('Order notification failed: ' . $e->getMessage());
            }

            // FCM push: log + send to devices
            $user = $order->user;
            if ($user) {
                $frontend = config('app.frontend_url', 'http://localhost:3001');
                PushNotificationRequested::dispatch(
                    $user->id,
                    'Order #' . $order->order_number,
                    'Your order status: ' . $order->status,
                    [
                        'type' => 'order',
                        'id' => (string) $order->id,
                        'deep_link' => $frontend . '/account/orders/' . $order->id,
                    ]
                );
            }
        }
    }
}
