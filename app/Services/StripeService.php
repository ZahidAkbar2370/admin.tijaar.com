<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\WalletDeposit;
use App\Services\WalletLedgerService;

class StripeService
{
    public function createWalletDepositSession(WalletDeposit $deposit, string $successUrl, string $cancelUrl): ?string
    {
        $key = \App\Models\Setting::get('stripe_secret') ?: config('services.stripe.secret');
        if (!$key) {
            return null;
        }

        \Stripe\Stripe::setApiKey($key);

        $currency = strtolower($deposit->currency ?? 'pkr');
        $amount = (float) $deposit->amount;
        if ($currency === 'pkr') {
            $currency = 'usd';
            $exchangeRate = (float) (\App\Models\Setting::get('stripe_pkr_to_usd') ?: config('services.stripe.pkr_to_usd', 280));
            $amount = $amount / $exchangeRate;
        }
        $unitAmount = (int) round($amount * 100);

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => 'Wallet deposit',
                        'description' => 'Add ' . number_format((float) $deposit->amount, 0) . ' ' . ($deposit->currency ?? 'PKR') . ' to your wallet',
                    ],
                    'unit_amount' => max(50, $unitAmount),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'wallet_deposit_id' => $deposit->id,
            ],
        ]);

        $deposit->update([
            'gateway_reference' => $session->id,
            'gateway_response' => array_merge(
                is_array($deposit->gateway_response) ? $deposit->gateway_response : [],
                ['session_id' => $session->id]
            ),
        ]);

        return $session->url;
    }

    public function createCheckoutSession(Order $order, Payment $payment, string $successUrl, string $cancelUrl): ?string
    {
        $key = \App\Models\Setting::get('stripe_secret') ?: config('services.stripe.secret');
        if (!$key) {
            return null;
        }

        \Stripe\Stripe::setApiKey($key);

        $currency = strtolower($payment->currency ?? 'pkr');
        $amount = (float) $payment->amount;
        if ($currency === 'pkr') {
            $currency = 'usd';
            $exchangeRate = (float) (\App\Models\Setting::get('stripe_pkr_to_usd') ?: config('services.stripe.pkr_to_usd', 280));
            $amount = $amount / $exchangeRate;
        } elseif ($currency !== 'aed') {
            $currency = 'usd';
        }
        $unitAmount = (int) round($amount * 100);

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => 'Order ' . $order->order_number,
                        'description' => 'Tijaar order payment (' . $payment->currency . ' ' . number_format((float) $payment->amount, 2) . ')',
                    ],
                    'unit_amount' => max(50, $unitAmount),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $order->id,
            'metadata' => [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
            ],
        ]);

        $payment->update([
            'gateway_reference' => $session->id,
            'gateway_response' => ['session_id' => $session->id],
        ]);

        return $session->url;
    }

    public function handleWebhook(string $payload, string $signature): bool
    {
        $secret = \App\Models\Setting::get('stripe_webhook_secret') ?: config('services.stripe.webhook_secret');
        if (!$secret) {
            return false;
        }

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $signature, $secret);
        } catch (\Exception $e) {
            return false;
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $depositId = $session->metadata->wallet_deposit_id ?? null;
            if ($depositId) {
                $deposit = WalletDeposit::find($depositId);
                if ($deposit && $deposit->gateway === 'stripe' && $deposit->status === 'pending') {
                    $wallet = Wallet::getOrCreateForUser($deposit->user_id, $deposit->currency);
                    WalletLedgerService::recordDeposit(
                        $wallet,
                        (float) $deposit->amount,
                        (int) $deposit->id,
                        'Payment Added to Wallet via Stripe',
                        ['gateway' => 'stripe', 'deposit_id' => $deposit->id]
                    );
                    $deposit->markCompleted($session->payment_intent ?? $session->id);
                    ListingFeeService::applyAfterDeposit($deposit->fresh());
                }
                return true;
            }
            $orderId = $session->metadata->order_id ?? null;
            if ($orderId) {
                $order = Order::find($orderId);
                if ($order) {
                    $payment = $order->payments()->where('gateway', 'stripe')->first();
                    if ($payment && $payment->status === 'pending') {
                        $payment->markCompleted($session->payment_intent ?? $session->id);
                        OrderWorkflowService::markPaymentSuccess($order->fresh(), 'Payment completed via Stripe');
                    }
                }
            }
        }

        return true;
    }
}
