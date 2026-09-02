<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsappTemplate;
use App\Support\PhoneHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WachatService
{
    public static function isEnabled(): bool
    {
        return (string) Setting::get('wachat_enabled', '0') === '1'
            && self::apiKey() !== ''
            && self::sender() !== ''
            && self::endpoint() !== '';
    }

    public static function eventEnabled(string $eventKey): bool
    {
        return self::isEnabled()
            && (string) Setting::get($eventKey, '1') === '1';
    }

    public static function apiKey(): string
    {
        return trim((string) Setting::get('wachat_api_key', config('settings_defaults.wachat_api_key', '')));
    }

    public static function sender(): string
    {
        $raw = trim((string) Setting::get('wachat_sender', config('settings_defaults.wachat_sender', '')));
        // Waghl expects international digits (e.g. 923…).
        $intl = PhoneHelper::toInternational($raw);
        if ($intl) {
            return $intl;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        return $digits;
    }

    public static function endpoint(): string
    {
        $url = trim((string) Setting::get('wachat_api_endpoint', config('settings_defaults.wachat_api_endpoint', '')));

        return $url !== '' ? $url : 'https://custom2.waghl.com/send-message';
    }

    /**
     * Normalize recipient for Waghl (international digits, e.g. 923XXXXXXXXX).
     */
    public static function formatNumber(?string $phone): ?string
    {
        $intl = PhoneHelper::toInternational($phone);
        if ($intl) {
            return $intl;
        }

        // Allow non-PK numbers already stored with country code (digits only).
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if (strlen($digits) >= 10 && strlen($digits) <= 15) {
            return $digits;
        }

        return null;
    }

    public static function userCanReceive(?User $user): bool
    {
        if (! $user || ! $user->whatsapp_verified_at) {
            return false;
        }

        return self::formatNumber($user->phone) !== null;
    }

    /**
     * Send a WhatsApp message via Waghl API.
     *
     * @return array{ok: bool, message?: string, response?: mixed}
     */
    public static function send(string $number, string $message): array
    {
        if (! self::isEnabled()) {
            return ['ok' => false, 'message' => 'WaChat WhatsApp is disabled or not configured.'];
        }

        $to = self::formatNumber($number);
        if (! $to) {
            return ['ok' => false, 'message' => 'Invalid WhatsApp number.'];
        }

        $sender = self::sender();
        if ($sender === '') {
            return ['ok' => false, 'message' => 'WaChat sender number is not configured.'];
        }

        $message = trim($message);
        if ($message === '') {
            return ['ok' => false, 'message' => 'Message is empty.'];
        }

        $payload = [
            'api_key' => self::apiKey(),
            'sender' => $sender,
            'number' => $to,
            'message' => $message,
        ];

        try {
            $response = Http::timeout(25)
                ->acceptJson()
                ->asJson()
                ->post(self::endpoint(), $payload);

            $json = $response->json();
            $bodyStatus = self::responseBodyOk($json);
            $apiMsg = self::responseBodyMessage($json);

            if (! $response->successful() || $bodyStatus === false) {
                Log::warning('WaChat send failed', [
                    'http' => $response->status(),
                    'body' => is_array($json) ? $json : $response->body(),
                    'number' => $to,
                    'sender' => $sender,
                ]);

                return [
                    'ok' => false,
                    'message' => $apiMsg !== ''
                        ? $apiMsg
                        : ('WhatsApp API error (HTTP '.$response->status().').'),
                    'response' => $json ?? $response->body(),
                ];
            }

            return ['ok' => true, 'response' => $json, 'message' => $apiMsg !== '' ? $apiMsg : 'Message sent.'];
        } catch (\Throwable $e) {
            Log::warning('WaChat send exception: '.$e->getMessage(), ['number' => $to]);

            return ['ok' => false, 'message' => 'WhatsApp send failed: '.$e->getMessage()];
        }
    }

    /**
     * Waghl returns { "status": true|false, "msg": "..." }.
     */
    protected static function responseBodyOk(mixed $json): ?bool
    {
        if (! is_array($json)) {
            return null;
        }

        foreach (['status', 'success', 'ok'] as $key) {
            if (! array_key_exists($key, $json)) {
                continue;
            }
            $v = $json[$key];
            if (is_bool($v)) {
                return $v;
            }
            if (is_numeric($v)) {
                return (int) $v === 1;
            }
            if (is_string($v)) {
                $lower = strtolower($v);
                if (in_array($lower, ['1', 'true', 'success', 'ok', 'sent'], true)) {
                    return true;
                }
                if (in_array($lower, ['0', 'false', 'error', 'failed', 'fail'], true)) {
                    return false;
                }
            }
        }

        return null;
    }

    protected static function responseBodyMessage(mixed $json): string
    {
        if (! is_array($json)) {
            return '';
        }

        foreach (['msg', 'message', 'error', 'detail'] as $key) {
            if (! empty($json[$key]) && is_string($json[$key])) {
                return trim($json[$key]);
            }
        }

        return '';
    }

    /**
     * Send to a verified user only.
     */
    public static function sendToVerifiedUser(?User $user, string $message, string $eventKey): bool
    {
        if (! self::eventEnabled($eventKey)) {
            Log::info('WaChat skipped: event off or WaChat disabled', [
                'event' => $eventKey,
                'user_id' => $user?->id,
            ]);

            return false;
        }

        // Admin master switch for user WhatsApp notification channel
        if ((string) Setting::get('notification_whatsapp_enabled', '1') !== '1') {
            Log::info('WaChat skipped: notification_whatsapp_enabled off', ['user_id' => $user?->id]);

            return false;
        }

        if ($user && ! \App\Models\NotificationPreference::userAllows((int) $user->id, 'whatsapp', 'order')) {
            Log::info('WaChat skipped: user disabled whatsapp order prefs', ['user_id' => $user->id]);

            return false;
        }

        if (! self::userCanReceive($user)) {
            Log::info('WaChat skipped: user not WhatsApp-verified or invalid phone', [
                'event' => $eventKey,
                'user_id' => $user?->id,
                'phone' => $user?->phone,
                'verified' => (bool) ($user?->whatsapp_verified_at),
            ]);

            return false;
        }

        $result = self::send((string) $user->phone, $message);
        if (! ($result['ok'] ?? false)) {
            Log::warning('WaChat event send failed', [
                'event' => $eventKey,
                'user_id' => $user->id,
                'message' => $result['message'] ?? null,
            ]);
        }

        return (bool) ($result['ok'] ?? false);
    }

    /**
     * Run after the HTTP response so order/payment APIs are not blocked.
     */
    protected static function afterResponse(callable $callback): void
    {
        try {
            dispatch($callback)->afterResponse();
        } catch (\Throwable $e) {
            // Fallback: run inline if queue/dispatch unavailable
            try {
                $callback();
            } catch (\Throwable $inner) {
                Log::warning('WaChat afterResponse failed: '.$inner->getMessage());
            }
        }
    }

    public static function notifyOrderPlacedCustomer(Order $order): void
    {
        $orderId = (int) $order->id;
        self::afterResponse(function () use ($orderId) {
            try {
                $order = Order::with('user')->find($orderId);
                if (! $order) {
                    return;
                }
                $user = $order->user;
                $data = [
                    'order_number' => $order->order_number,
                    'order_total' => number_format((float) $order->total, 2),
                    'customer_name' => $user?->name ?? '',
                    'app_name' => config('app.name', 'Tijaar'),
                ];
                $fallback = 'Tijaar: Your order {{order_number}} has been placed successfully. Total: {{order_total}} PKR. Thank you!';
                $msg = WhatsappTemplate::renderSlug('order_placed_customer', $data, $fallback);
                self::sendToVerifiedUser($user, $msg, 'wachat_msg_order_placed_customer');
            } catch (\Throwable $e) {
                Log::warning('WaChat order placed (customer) failed: '.$e->getMessage());
            }
        });
    }

    public static function notifyOrderPlacedSellers(Order $order): void
    {
        $orderId = (int) $order->id;
        self::afterResponse(function () use ($orderId) {
            if (! self::eventEnabled('wachat_msg_order_placed_seller')) {
                Log::info('WaChat skipped: order_placed_seller event off', ['order_id' => $orderId]);

                return;
            }

            try {
                $order = Order::with(['items.store.seller' => fn ($q) => $q->withTrashed()])->find($orderId);
                if (! $order) {
                    return;
                }

                $msg = WhatsappTemplate::renderSlug('order_placed_seller', [
                    'order_number' => $order->order_number,
                    'order_total' => number_format((float) $order->total, 2),
                    'app_name' => config('app.name', 'Tijaar'),
                ], 'Tijaar: New order {{order_number}} received. Total: {{order_total}} PKR. Please review and approve.');

                foreach (self::sellerUserIdsForOrder($order) as $userId) {
                    $seller = User::find($userId);
                    $sellerMsg = WhatsappTemplate::replaceInString($msg, [
                        'seller_name' => $seller?->name ?? '',
                    ]);
                    self::sendToVerifiedUser($seller, $sellerMsg, 'wachat_msg_order_placed_seller');
                }
            } catch (\Throwable $e) {
                Log::warning('WaChat order placed (seller) failed: '.$e->getMessage());
            }
        });
    }

    public static function notifyPaymentSuccess(Order $order): void
    {
        $orderId = (int) $order->id;
        self::afterResponse(function () use ($orderId) {
            try {
                $order = Order::with('user')->find($orderId);
                if (! $order) {
                    return;
                }
                $msg = WhatsappTemplate::renderSlug('payment_success', [
                    'order_number' => $order->order_number,
                    'order_total' => number_format((float) $order->total, 2),
                    'customer_name' => $order->user?->name ?? '',
                    'app_name' => config('app.name', 'Tijaar'),
                ], 'Tijaar: Payment received for order {{order_number}}. Amount: {{order_total}} PKR. Thank you!');
                self::sendToVerifiedUser($order->user, $msg, 'wachat_msg_payment_success');
            } catch (\Throwable $e) {
                Log::warning('WaChat payment success failed: '.$e->getMessage());
            }
        });
    }

    public static function notifyOrderApproved(Order $order): void
    {
        $orderId = (int) $order->id;
        self::afterResponse(function () use ($orderId) {
            try {
                $order = Order::with('user')->find($orderId);
                if (! $order) {
                    return;
                }
                $msg = WhatsappTemplate::renderSlug('order_approved', [
                    'order_number' => $order->order_number,
                    'customer_name' => $order->user?->name ?? '',
                    'app_name' => config('app.name', 'Tijaar'),
                ], 'Tijaar: Good news! Your order {{order_number}} has been accepted by the seller and will be shipped soon.');
                self::sendToVerifiedUser($order->user, $msg, 'wachat_msg_order_approved');
            } catch (\Throwable $e) {
                Log::warning('WaChat order approved failed: '.$e->getMessage());
            }
        });
    }

    public static function notifyOrderShipped(Order $order, ?string $trackingNumber = null, ?string $carrier = null): void
    {
        $orderId = (int) $order->id;
        self::afterResponse(function () use ($orderId, $trackingNumber, $carrier) {
            try {
                $order = Order::with('user')->find($orderId);
                if (! $order) {
                    return;
                }
                $tracking = $trackingNumber ?: $order->tracking_number;
                $carrierPart = $carrier ? ' via '.$carrier : '';
                $trackingPart = $tracking ? '. Tracking ID: '.$tracking : '';
                $msg = WhatsappTemplate::renderSlug('order_shipped', [
                    'order_number' => $order->order_number,
                    'tracking_number' => (string) ($tracking ?? ''),
                    'carrier' => (string) ($carrier ?? ''),
                    'carrier_part' => $carrierPart,
                    'tracking_part' => $trackingPart,
                    'customer_name' => $order->user?->name ?? '',
                    'app_name' => config('app.name', 'Tijaar'),
                ], 'Tijaar: Your order {{order_number}} has been shipped{{carrier_part}}{{tracking_part}}.');
                self::sendToVerifiedUser($order->user, $msg, 'wachat_msg_order_shipped');
            } catch (\Throwable $e) {
                Log::warning('WaChat order shipped failed: '.$e->getMessage());
            }
        });
    }

    public static function notifyOrderDeliveredSellers(Order $order): void
    {
        $orderId = (int) $order->id;
        self::afterResponse(function () use ($orderId) {
            if (! self::eventEnabled('wachat_msg_order_delivered_seller')) {
                return;
            }

            try {
                $order = Order::with(['items.store.seller' => fn ($q) => $q->withTrashed()])->find($orderId);
                if (! $order) {
                    return;
                }

                $msg = WhatsappTemplate::renderSlug('order_delivered_seller', [
                    'order_number' => $order->order_number,
                    'app_name' => config('app.name', 'Tijaar'),
                ], 'Tijaar: Order {{order_number}} has been delivered to the customer.');

                foreach (self::sellerUserIdsForOrder($order) as $userId) {
                    $seller = User::find($userId);
                    $sellerMsg = WhatsappTemplate::replaceInString($msg, [
                        'seller_name' => $seller?->name ?? '',
                    ]);
                    self::sendToVerifiedUser($seller, $sellerMsg, 'wachat_msg_order_delivered_seller');
                }
            } catch (\Throwable $e) {
                Log::warning('WaChat order delivered (seller) failed: '.$e->getMessage());
            }
        });
    }

    /**
     * Resolve seller user IDs for an order (business store + private seller).
     *
     * @return list<int>
     */
    public static function sellerUserIdsForOrder(Order $order): array
    {
        $order->loadMissing(['items.store.seller' => fn ($q) => $q->withTrashed()]);
        $ids = [];

        foreach ($order->items as $item) {
            $userId = null;
            if ($item->store_id && $item->store?->seller?->user_id) {
                $userId = (int) $item->store->seller->user_id;
            } elseif ($item->seller_id) {
                // products/order_items.seller_id is users.id for both private and business listings
                $userId = (int) $item->seller_id;
            }
            if ($userId > 0) {
                $ids[$userId] = true;
            }
        }

        return array_map('intval', array_keys($ids));
    }
}
