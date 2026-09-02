<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\WalletDeposit;
use App\Services\WalletLedgerService;
use Illuminate\Support\Str;

class EasypaisaService
{
    /**
     * Easypaisa payment - get checkout form data for redirect.
     * Uses hashRequest (AES-128-ECB encrypted params) per Easypaisa merchant integration.
     * Config: easypaisa_store_id, easypaisa_hash_key
     */
    public function getCheckoutData(Order $order, Payment $payment, ?string $customerMobile = null, ?string $customerEmail = null): ?array
    {
        if (\App\Models\Setting::get('easypaisa_enabled') === '0') {
            return null;
        }
        $storeId = \App\Models\Setting::get('easypaisa_store_id') ?: config('services.easypaisa.store_id');
        $hashKey = \App\Models\Setting::get('easypaisa_hash_key') ?: config('services.easypaisa.hash_key');

        if (!$storeId || !$hashKey) {
            return null;
        }

        $baseUrl = config('app.url');
        $postBackUrl = \App\Models\Setting::get('easypaisa_postback_url')
            ?: config('services.easypaisa.postback_url', $baseUrl . '/api/v1/webhooks/easypaisa/callback');

        $orderRefNum = 'EP' . $order->id . '_' . Str::random(6);
        $amount = (float) $payment->amount;
        $expiryDate = now()->addDays(3)->format('Y-m-d\TH:i:00');
        $timeStamp = now()->format('Y-m-d\TH:i:00');

        $paramMap = [
            'amount' => $amount,
            'orderRefNum' => $orderRefNum,
            'paymentMethod' => 'InitialRequest',
            'postBackURL' => $postBackUrl,
            'storeId' => $storeId,
            'timeStamp' => $timeStamp,
        ];

        if ($customerEmail) {
            $paramMap['emailAddress'] = $customerEmail;
        }
        if ($customerMobile) {
            $paramMap['mobileNum'] = $customerMobile;
        }

        $hashRequest = $this->computeHashRequest($paramMap, $hashKey);

        $payment->update([
            'gateway_reference' => $orderRefNum,
            'gateway_response' => ['order_ref' => $orderRefNum],
        ]);

        $checkoutUrl = \App\Models\Setting::get('easypaisa_checkout_url')
            ?: config('services.easypaisa.checkout_url', 'https://easypay.easypaisa.com.pk/easypay/Index.jsf');

        return [
            'url' => $checkoutUrl,
            'method' => 'POST',
            'params' => [
                'storeId' => $storeId,
                'orderId' => $orderRefNum,
                'transactionAmount' => $amount,
                'mobileAccountNo' => $customerMobile ?? '',
                'emailAddress' => $customerEmail ?? '',
                'hashRequest' => $hashRequest,
                'paymentMethod' => 'InitialRequest',
                'postBackURL' => $postBackUrl,
                'expiryDate' => $expiryDate,
            ],
        ];
    }

    /**
     * Wallet deposit - get checkout form data. Ref: WD{deposit_id}_{random} (orderId param)
     */
    public function getWalletDepositCheckoutData(WalletDeposit $deposit, ?string $customerMobile = null, ?string $customerEmail = null): ?array
    {
        if (\App\Models\Setting::get('easypaisa_enabled') === '0') {
            return null;
        }
        $storeId = \App\Models\Setting::get('easypaisa_store_id') ?: config('services.easypaisa.store_id');
        $hashKey = \App\Models\Setting::get('easypaisa_hash_key') ?: config('services.easypaisa.hash_key');

        if (!$storeId || !$hashKey) {
            return null;
        }

        $baseUrl = config('app.url');
        $postBackUrl = \App\Models\Setting::get('easypaisa_postback_url')
            ?: config('services.easypaisa.postback_url', $baseUrl . '/api/v1/webhooks/easypaisa/callback');

        $orderRefNum = 'WD' . $deposit->id . '_' . Str::random(6);
        $amount = (float) $deposit->amount;
        $expiryDate = now()->addDays(3)->format('Y-m-d\TH:i:00');
        $timeStamp = now()->format('Y-m-d\TH:i:00');

        $paramMap = [
            'amount' => $amount,
            'orderRefNum' => $orderRefNum,
            'paymentMethod' => 'InitialRequest',
            'postBackURL' => $postBackUrl,
            'storeId' => $storeId,
            'timeStamp' => $timeStamp,
        ];

        if ($customerEmail) {
            $paramMap['emailAddress'] = $customerEmail;
        }
        if ($customerMobile) {
            $paramMap['mobileNum'] = $customerMobile;
        }

        $hashRequest = $this->computeHashRequest($paramMap, $hashKey);

        $deposit->update([
            'gateway_reference' => $orderRefNum,
            'gateway_response' => array_merge(
                is_array($deposit->gateway_response) ? $deposit->gateway_response : [],
                ['order_ref' => $orderRefNum]
            ),
        ]);

        $checkoutUrl = \App\Models\Setting::get('easypaisa_checkout_url')
            ?: config('services.easypaisa.checkout_url', 'https://easypay.easypaisa.com.pk/easypay/Index.jsf');

        return [
            'url' => $checkoutUrl,
            'method' => 'POST',
            'params' => [
                'storeId' => $storeId,
                'orderId' => $orderRefNum,
                'transactionAmount' => $amount,
                'mobileAccountNo' => $customerMobile ?? '',
                'emailAddress' => $customerEmail ?? '',
                'hashRequest' => $hashRequest,
                'paymentMethod' => 'InitialRequest',
                'postBackURL' => $postBackUrl,
                'expiryDate' => $expiryDate,
            ],
        ];
    }

    /**
     * Compute Easypaisa hashRequest: AES-128-ECB encrypt of param string.
     */
    private function computeHashRequest(array $paramMap, string $hashKey): string
    {
        $parts = [];
        foreach ($paramMap as $key => $val) {
            $parts[] = $key . '=' . $val;
        }
        $mapString = implode('&', $parts);

        $encrypted = openssl_encrypt($mapString, 'aes-128-ecb', $hashKey, OPENSSL_RAW_DATA);

        return base64_encode($encrypted ?: '');
    }

    /**
     * Handle Easypaisa callback (POST to postBackURL).
     * Easypaisa sends: orderId, paymentToken, status, etc.
     */
    public function handleCallback(array $data): bool
    {
        $orderRefNum = $data['orderId'] ?? $data['orderRefNum'] ?? null;
        $status = $data['status'] ?? $data['paymentStatus'] ?? null;

        if (!$orderRefNum) {
            return false;
        }

        // Wallet deposit: WD{depositId}_{random}
        if (preg_match('/^WD(\d+)_/', $orderRefNum, $m)) {
            $deposit = WalletDeposit::find((int) $m[1]);
            if ($deposit && $deposit->gateway === 'easypaisa' && $deposit->gateway_reference === $orderRefNum && $deposit->status === 'pending') {
                $success = in_array(strtolower((string) $status), ['success', 'paid', 'completed', '1'], true);
                if ($success) {
                    $deposit->update(['gateway_response' => array_merge($deposit->gateway_response ?? [], $data)]);
                    $wallet = Wallet::getOrCreateForUser($deposit->user_id, $deposit->currency);
                    WalletLedgerService::recordDeposit(
                        $wallet,
                        (float) $deposit->amount,
                        (int) $deposit->id,
                        'Payment Added to Wallet via Easypaisa',
                        ['gateway' => 'easypaisa', 'deposit_id' => $deposit->id]
                    );
                    $deposit->markCompleted($orderRefNum);
                    ListingFeeService::applyAfterDeposit($deposit->fresh());
                }
            }
            return true;
        }

        // Order payment: EP{orderId}_{random}
        if (preg_match('/^EP(\d+)_/', $orderRefNum, $m)) {
            $orderId = (int) $m[1];
            $order = Order::find($orderId);
            if (!$order) {
                return false;
            }
            $payment = $order->payments()->where('gateway', 'easypaisa')->where('gateway_reference', $orderRefNum)->first();
            if (!$payment || $payment->status === 'completed') {
                return true;
            }
            $success = in_array(strtolower((string) $status), ['success', 'paid', 'completed', '1'], true);
            if ($success) {
                $payment->update(['status' => 'completed', 'paid_at' => now(), 'gateway_response' => array_merge($payment->gateway_response ?? [], $data)]);
                OrderWorkflowService::markPaymentSuccess($order->fresh(), 'Payment completed via Easypaisa');
            }
            return true;
        }

        return false;
    }
}
