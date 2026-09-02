<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\WalletDeposit;
use App\Services\WalletLedgerService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JazzCashService
{
    public const ORCHESTRATOR_BASE = 'https://onlinepayments.jazzcash.com.pk/payment-orchestrator';

    public const DEFAULT_CHECKOUT_URL = self::ORCHESTRATOR_BASE . '/CustomerPortal/transactionmanagement/merchantform';

    // public const DEFAULT_STATUS_INQUIRY_URL = self::ORCHESTRATOR_BASE . '/api/v1/rest/payments/status-inquiry';
    public const DEFAULT_STATUS_INQUIRY_URL = self::ORCHESTRATOR_BASE . '/api/v1/rest/payments/status/inquiry';

    /** MWallet REST v1.1 — server-to-server (not hosted Payment Portal). */
    public const DEFAULT_MWALLET_URL = self::ORCHESTRATOR_BASE . '/api/v1/rest/payments/m-wallet';

    /** MWallet REST v2.0 — requires mobile + CNIC. */
    public const DEFAULT_MWALLET_V2_URL = self::ORCHESTRATOR_BASE . '/api/v2/rest/payments/m-wallet';

    public const CALLBACK_PATH = '/api/v1/webhooks/jazzcash/callback';

    public const IPN_PATH = '/api/v1/webhooks/jazzcash/ipn';

    public const TXN_REF_PREFIX = 'T';

    /** Pending / interim JazzCash response codes (keep payment pending; cron may reconcile). */
    public const PENDING_RESPONSE_CODES = ['013', '124', '157'];

    public const MODE_PORTAL = 'portal';

    public const MODE_MWALLET_V1 = 'mwallet_v1';

    public const MODE_MWALLET_V2 = 'mwallet_v2';

    /**
     * Admin setting: portal (hosted form redirect), mwallet_v1, or mwallet_v2.
     * Default mwallet_v2 — JazzCash (Hassan) requires Wallet API v2.0.
     */
    public static function checkoutMode(): string
    {
        $mode = strtolower(trim((string) Setting::get('jazzcash_checkout_mode', self::MODE_MWALLET_V2)));

        return in_array($mode, [self::MODE_PORTAL, self::MODE_MWALLET_V1, self::MODE_MWALLET_V2], true)
            ? $mode
            : self::MODE_MWALLET_V2;
    }

    public static function requiresCnic(): bool
    {
        return self::checkoutMode() === self::MODE_MWALLET_V2;
    }

    public static function requiresMobile(): bool
    {
        return in_array(self::checkoutMode(), [self::MODE_MWALLET_V1, self::MODE_MWALLET_V2], true);
    }

    /**
     * Normalize PK mobile for JazzCash API (03XXXXXXXXX).
     * Accepts stored 923… format and converts for the gateway.
     */
    public static function normalizeMobile(?string $phone): ?string
    {
        return \App\Support\PhoneHelper::toJazzCashFormat($phone);
    }

    /**
     * CNIC for MWallet v2 — JazzCash expects exactly the last 6 digits
     * (official sample: "123456"). Full 13-digit CNIC returns response 110.
     */
    public static function normalizeCnic(?string $cnic): ?string
    {
        if ($cnic === null || trim($cnic) === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $cnic) ?? '';
        if (strlen($digits) < 6 || strlen($digits) > 13) {
            return null;
        }

        return substr($digits, -6);
    }

    /**
     * Unified order payment: MWallet REST only (no portal redirect).
     * JazzCash support asked for Wallet API v2.0 (mobile + CNIC).
     *
     * @return array{
     *   success: bool,
     *   payment_ok: bool,
     *   payment_status: string,
     *   message: string,
     *   response_code: ?string,
     *   mode: string,
     *   checkout_url?: string,
     *   checkout_method?: string,
     *   checkout_params?: array<string, string>,
     *   fallback_from?: string
     * }
     */
    public function processOrderPayment(Order $order, Payment $payment, ?string $customerMobile, ?string $cnic = null): array
    {
        // Force MWallet v2.0 per JazzCash integration guidance (Hassan).
        return $this->chargeOrderViaMwalletV2($order, $payment, $customerMobile, $cnic);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    protected function shouldFallbackToPortal(array $result): bool
    {
        if (($result['payment_ok'] ?? false) === true) {
            return false;
        }
        if (($result['payment_status'] ?? '') === 'pending') {
            return false;
        }

        $code = (string) ($result['response_code'] ?? '');
        $message = strtolower((string) ($result['message'] ?? ''));

        if ($code === '999' && (str_contains($message, 'access denied') || str_contains($message, 'not permitted'))) {
            return true;
        }

        // Merchant/product not enabled for REST MWALLET
        if (str_contains($message, 'access denied') || str_contains($message, 'not permitted')) {
            return true;
        }

        return false;
    }

    /**
     * @return array{success: bool, payment_ok: bool, payment_status: string, message: string, response_code: ?string, mode: string, checkout_url: string, checkout_method: string, checkout_params: array<string, string>}
     */
    protected function portalCheckoutResult(Order $order, Payment $payment, ?string $customerMobile): array
    {
        $checkout = $this->getCheckoutData($order, $payment, $customerMobile);
        if (!$checkout) {
            return [
                'success' => false,
                'payment_ok' => false,
                'payment_status' => 'failed',
                'message' => 'JazzCash Payment Portal is not configured.',
                'response_code' => null,
                'mode' => self::MODE_PORTAL,
            ];
        }

        return [
            'success' => true,
            'payment_ok' => false,
            'payment_status' => 'pending',
            'message' => 'Redirect to JazzCash payment',
            'response_code' => null,
            'mode' => self::MODE_PORTAL,
            'checkout_url' => $checkout['url'],
            'checkout_method' => $checkout['method'],
            'checkout_params' => $checkout['params'],
        ];
    }

    /**
     * Charge order via MWallet REST v1.1 (POST /api/v1/rest/payments/m-wallet).
     *
     * @return array{success: bool, payment_ok: bool, payment_status: string, message: string, response_code: ?string, mode: string}
     */
    public function chargeOrderViaMwallet(Order $order, Payment $payment, ?string $customerMobile): array
    {
        $base = [
            'success' => false,
            'payment_ok' => false,
            'payment_status' => 'failed',
            'message' => 'JazzCash payment failed.',
            'response_code' => null,
            'mode' => 'mwallet_v1.1',
        ];

        if (Setting::get('jazzcash_enabled') === '0') {
            $base['message'] = 'JazzCash is disabled.';

            return $base;
        }

        $creds = $this->credentials();
        if (!$creds || empty($creds['integrity_salt'])) {
            $base['message'] = 'JazzCash is not configured (merchant credentials / integrity salt).';

            return $base;
        }

        $mobile = self::normalizeMobile($customerMobile);
        if ($mobile === null) {
            $base['message'] = 'A valid JazzCash mobile number is required (03XXXXXXXXX).';

            return $base;
        }

        $txnRefNo = $this->generateTxnRefNo((string) $order->id);
        $txnDateTime = now()->format('YmdHis');
        $params = $this->buildMwalletV11Params(
            $creds,
            $txnRefNo,
            $txnDateTime,
            (float) $payment->amount,
            $order->order_number,
            'Tijaar Order ' . $order->order_number,
            $mobile
        );

        $payment->update([
            'gateway_reference' => $txnRefNo,
            'gateway_response' => [
                'mode' => 'mwallet_v1.1',
                'txn_ref' => $txnRefNo,
                'txn_datetime' => $txnDateTime,
                'params_sent' => array_diff_key($params, ['pp_Password' => true]),
            ],
        ]);

        $apiResult = $this->postMwallet($params, self::MODE_MWALLET_V1);

        return $this->interpretMwalletApiResult($order, $payment, $apiResult, $txnRefNo, 'mwallet_v1.1', $base);
    }

    /**
     * Charge order via MWallet REST v2.0 (POST /api/v2/rest/payments/m-wallet).
     *
     * @return array{success: bool, payment_ok: bool, payment_status: string, message: string, response_code: ?string, mode: string}
     */
    public function chargeOrderViaMwalletV2(Order $order, Payment $payment, ?string $customerMobile, ?string $cnic): array
    {
        $base = [
            'success' => false,
            'payment_ok' => false,
            'payment_status' => 'failed',
            'message' => 'JazzCash payment failed.',
            'response_code' => null,
            'mode' => 'mwallet_v2.0',
        ];

        if (Setting::get('jazzcash_enabled') === '0') {
            $base['message'] = 'JazzCash is disabled.';

            return $base;
        }

        $creds = $this->credentials();
        if (!$creds || empty($creds['integrity_salt'])) {
            $base['message'] = 'JazzCash is not configured (merchant credentials / integrity salt).';

            return $base;
        }

        $mobile = self::normalizeMobile($customerMobile);
        if ($mobile === null) {
            $base['message'] = 'A valid JazzCash mobile number is required (03XXXXXXXXX).';

            return $base;
        }

        $cnicNorm = self::normalizeCnic($cnic);
        if ($cnicNorm === null) {
            $base['message'] = 'CNIC is required for JazzCash MWallet v2 (at least last 6 digits).';

            return $base;
        }

        $base['mobile_sent'] = $mobile;
        $base['cnic_sent'] = $cnicNorm;

        $txnRefNo = $this->generateTxnRefNo((string) $order->id);
        $txnDateTime = now()->format('YmdHis');
        $params = $this->buildMwalletV2Params(
            $creds,
            $txnRefNo,
            $txnDateTime,
            (float) $payment->amount,
            $order->order_number,
            'Tijaar Order ' . $order->order_number,
            $mobile,
            $cnicNorm
        );

        $payment->update([
            'gateway_reference' => $txnRefNo,
            'gateway_response' => [
                'mode' => 'mwallet_v2.0',
                'txn_ref' => $txnRefNo,
                'txn_datetime' => $txnDateTime,
                'params_sent' => array_diff_key($params, ['pp_Password' => true]),
            ],
        ]);

        $apiResult = $this->postMwallet($params, self::MODE_MWALLET_V2);

        return $this->interpretMwalletApiResult($order, $payment, $apiResult, $txnRefNo, 'mwallet_v2.0', $base);
    }

    /**
     * @param  array{http_status: ?int, body: array<string, mixed>, error: ?string}  $apiResult
     * @param  array{success: bool, payment_ok: bool, payment_status: string, message: string, response_code: ?string, mode: string}  $base
     * @return array{success: bool, payment_ok: bool, payment_status: string, message: string, response_code: ?string, mode: string}
     */
    protected function interpretMwalletApiResult(
        Order $order,
        Payment $payment,
        array $apiResult,
        string $txnRefNo,
        string $modeLabel,
        array $base
    ): array {
        $body = $apiResult['body'];
        $code = (string) ($body['pp_ResponseCode'] ?? '');

        $payment->update([
            'gateway_response' => array_merge(
                is_array($payment->gateway_response) ? $payment->gateway_response : [],
                ['http_status' => $apiResult['http_status'], 'api_response' => $body, 'mode' => $modeLabel]
            ),
        ]);

        $base['mode'] = $modeLabel;

        if ($apiResult['http_status'] === null) {
            $base['message'] = $apiResult['error'] ?? 'Could not reach JazzCash MWallet API.';

            return $base;
        }

        $base['response_code'] = $code !== '' ? $code : null;
        $message = (string) ($body['pp_ResponseMessage'] ?? '');

        if ($code === '000' || $code === '121') {
            $this->processNotification(array_merge($body, [
                'pp_TxnRefNo' => $body['pp_TxnRefNo'] ?? $txnRefNo,
                'pp_ResponseCode' => $code,
            ]), 'mwallet');
            $order->refresh();
            $status = (string) $order->payment_status;

            // Hassan: prove Status Inquiry API after successful charge.
            $inquiryRef = (string) ($body['pp_TxnRefNo'] ?? $txnRefNo);
            try {
                $this->inquireStatus($inquiryRef, $payment->gateway_response['txn_datetime'] ?? null);
            } catch (\Throwable $e) {
                Log::warning('JazzCash status inquiry after charge failed', [
                    'ref' => $inquiryRef,
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'success' => true,
                'payment_ok' => true,
                'payment_status' => $status !== '' ? $status : 'paid',
                'message' => $message !== '' ? $message : 'JazzCash payment completed.',
                'response_code' => $code,
                'mode' => $modeLabel,
                'mobile_sent' => $base['mobile_sent'] ?? null,
                'cnic_sent' => $base['cnic_sent'] ?? null,
            ];
        }

        if (in_array($code, self::PENDING_RESPONSE_CODES, true)) {
            return [
                'success' => true,
                'payment_ok' => false,
                'payment_status' => 'pending',
                'message' => $message !== ''
                    ? $message
                    : 'JazzCash payment is pending. Approve in the JazzCash app if prompted; we will confirm shortly.',
                'response_code' => $code,
                'mode' => $modeLabel,
                'mobile_sent' => $base['mobile_sent'] ?? null,
                'cnic_sent' => $base['cnic_sent'] ?? null,
            ];
        }

        // Mark payment failed so order is not treated as awaiting JazzCash success.
        try {
            $payment->update(['status' => 'failed']);
        } catch (\Throwable $e) {
            // ignore
        }

        $base['message'] = $message !== '' ? $message : 'JazzCash declined the payment (code ' . ($code !== '' ? $code : 'unknown') . ').';
        if ($code === '156') {
            $sentMobile = $base['mobile_sent'] ?? null;
            $sentCnic = $base['cnic_sent'] ?? null;
            $base['message'] = 'JazzCash rejected this wallet (code 156): mobile and CNIC do not match their records, '
                . 'or this number has no JazzCash wallet. '
                . ($sentMobile && $sentCnic
                    ? "We sent mobile {$sentMobile} with CNIC last-6 {$sentCnic}. "
                    : '')
                . 'Open the JazzCash app → Profile and confirm the registered number and CNIC, then try again. '
                . ($message !== '' ? "({$message})" : '');
        }
        if ($code === '999' || str_contains(strtolower($message), 'access denied') || str_contains(strtolower($message), 'not permitted')) {
            $base['message'] = 'JazzCash MWallet Access denied (999): Merchant ID is not permitted for Mobile Wallet API. '
                . 'Ask JazzCash to enable MWallet for this MID or provide a Sandbox Merchant ID. '
                . ($message !== '' ? '(' . $message . ')' : '');
        }

        return $base;
    }

    /**
     * Unified wallet / listing-fee payment — same path as order Pay Now (MWallet REST v2.0).
     *
     * @return array<string, mixed>
     */
    public function processWalletDeposit(WalletDeposit $deposit, ?string $customerMobile, ?string $cnic = null): array
    {
        // Match order payments: force MWallet v2.0 (mobile + CNIC). Portal returns
        // "insufficient merchant information" for this MID / product mix.
        $result = $this->chargeWalletDepositViaMwalletV2($deposit, $customerMobile, $cnic);

        $paymentStatus = (string) ($result['payment_status'] ?? '');
        if ($paymentStatus === 'failed') {
            $deposit->refresh();
            $deposit->markFailed([
                'failure_message' => $result['message'] ?? null,
                'response_code' => $result['response_code'] ?? null,
            ]);
        }

        return $result;
    }

    /**
     * Wallet deposit via MWallet REST v1.1.
     *
     * @return array{success: bool, payment_ok: bool, payment_status: string, message: string, response_code: ?string, mode: string, deposit_id: int}
     */
    public function chargeWalletDepositViaMwallet(WalletDeposit $deposit, ?string $customerMobile): array
    {
        $base = [
            'success' => false,
            'payment_ok' => false,
            'payment_status' => 'failed',
            'message' => 'JazzCash deposit failed.',
            'response_code' => null,
            'mode' => 'mwallet_v1.1',
            'deposit_id' => (int) $deposit->id,
        ];

        if (Setting::get('jazzcash_enabled') === '0') {
            $base['message'] = 'JazzCash is disabled.';

            return $base;
        }

        $creds = $this->credentials();
        if (!$creds || empty($creds['integrity_salt'])) {
            $base['message'] = 'JazzCash is not configured.';

            return $base;
        }

        $mobile = self::normalizeMobile($customerMobile);
        if ($mobile === null) {
            $base['message'] = 'A valid JazzCash mobile number is required (03XXXXXXXXX).';

            return $base;
        }

        $txnRefNo = $this->generateTxnRefNo('W' . $deposit->id);
        $txnDateTime = now()->format('YmdHis');
        $amountRaw = (float) $deposit->amount;
        $params = $this->buildMwalletV11Params(
            $creds,
            $txnRefNo,
            $txnDateTime,
            $amountRaw,
            'WALLET-' . $deposit->id,
            'Wallet deposit ' . number_format($amountRaw, 0) . ' PKR',
            $mobile
        );

        $deposit->update([
            'gateway_reference' => $txnRefNo,
            'gateway_response' => [
                'mode' => 'mwallet_v1.1',
                'txn_ref' => $txnRefNo,
                'txn_datetime' => $txnDateTime,
            ],
        ]);

        $apiResult = $this->postMwallet($params, self::MODE_MWALLET_V1);
        $body = $apiResult['body'];
        $code = (string) ($body['pp_ResponseCode'] ?? '');

        $deposit->update([
            'gateway_response' => array_merge(
                is_array($deposit->gateway_response) ? $deposit->gateway_response : [],
                ['http_status' => $apiResult['http_status'], 'api_response' => $body]
            ),
        ]);

        $base['response_code'] = $code !== '' ? $code : null;
        $message = (string) ($body['pp_ResponseMessage'] ?? '');

        if ($apiResult['http_status'] === null) {
            $base['message'] = $apiResult['error'] ?? 'Could not reach JazzCash MWallet API.';

            return $base;
        }

        if ($code === '000' || $code === '121') {
            $this->processNotification(array_merge($body, [
                'pp_TxnRefNo' => $body['pp_TxnRefNo'] ?? $txnRefNo,
                'pp_ResponseCode' => $code,
            ]), 'mwallet');

            return [
                'success' => true,
                'payment_ok' => true,
                'payment_status' => 'paid',
                'message' => $message !== '' ? $message : 'Wallet deposit completed via JazzCash.',
                'response_code' => $code,
                'mode' => 'mwallet_v1.1',
                'deposit_id' => (int) $deposit->id,
            ];
        }

        if (in_array($code, self::PENDING_RESPONSE_CODES, true)) {
            return [
                'success' => true,
                'payment_ok' => false,
                'payment_status' => 'pending',
                'message' => $message !== ''
                    ? $message
                    : 'Deposit is pending confirmation from JazzCash.',
                'response_code' => $code,
                'mode' => 'mwallet_v1.1',
                'deposit_id' => (int) $deposit->id,
            ];
        }

        $base['message'] = $message !== '' ? $message : 'JazzCash declined the deposit.';

        return $base;
    }

    /**
     * @return array{success: bool, payment_ok: bool, payment_status: string, message: string, response_code: ?string, mode: string, deposit_id: int}
     */
    public function chargeWalletDepositViaMwalletV2(WalletDeposit $deposit, ?string $customerMobile, ?string $cnic): array
    {
        $base = [
            'success' => false,
            'payment_ok' => false,
            'payment_status' => 'failed',
            'message' => 'JazzCash deposit failed.',
            'response_code' => null,
            'mode' => 'mwallet_v2.0',
            'deposit_id' => (int) $deposit->id,
        ];

        if (Setting::get('jazzcash_enabled') === '0') {
            $base['message'] = 'JazzCash is disabled.';

            return $base;
        }

        $creds = $this->credentials();
        if (!$creds || empty($creds['integrity_salt'])) {
            $base['message'] = 'JazzCash is not configured.';

            return $base;
        }

        $mobile = self::normalizeMobile($customerMobile);
        if ($mobile === null) {
            $base['message'] = 'A valid JazzCash mobile number is required (03XXXXXXXXX).';

            return $base;
        }

        $cnicNorm = self::normalizeCnic($cnic);
        if ($cnicNorm === null) {
            $base['message'] = 'CNIC is required for JazzCash MWallet v2 (at least last 6 digits).';

            return $base;
        }

        $txnRefNo = $this->generateTxnRefNo('W' . $deposit->id);
        $txnDateTime = now()->format('YmdHis');
        $amountRaw = (float) $deposit->amount;
        $meta = is_array($deposit->gateway_response) ? $deposit->gateway_response : [];
        $isListingFee = ($meta['purpose'] ?? null) === 'listing_fee';
        $productId = (int) ($meta['product_id'] ?? 0);
        $billRef = $isListingFee && $productId > 0
            ? 'LISTING-' . $productId
            : 'WALLET-' . $deposit->id;
        $description = $isListingFee && $productId > 0
            ? 'Tijaar listing fee #' . $productId
            : 'Wallet deposit ' . number_format($amountRaw, 0) . ' PKR';

        $params = $this->buildMwalletV2Params(
            $creds,
            $txnRefNo,
            $txnDateTime,
            $amountRaw,
            $billRef,
            $description,
            $mobile,
            $cnicNorm
        );

        $deposit->update([
            'gateway_reference' => $txnRefNo,
            'gateway_response' => array_merge($meta, [
                'mode' => 'mwallet_v2.0',
                'txn_ref' => $txnRefNo,
                'txn_datetime' => $txnDateTime,
                'params_sent' => array_diff_key($params, ['pp_Password' => true]),
            ]),
        ]);

        $apiResult = $this->postMwallet($params, self::MODE_MWALLET_V2);
        $body = $apiResult['body'];
        $code = (string) ($body['pp_ResponseCode'] ?? '');

        $deposit->update([
            'gateway_response' => array_merge(
                is_array($deposit->gateway_response) ? $deposit->gateway_response : [],
                ['http_status' => $apiResult['http_status'], 'api_response' => $body]
            ),
        ]);

        $base['response_code'] = $code !== '' ? $code : null;
        $message = (string) ($body['pp_ResponseMessage'] ?? '');

        if ($apiResult['http_status'] === null) {
            $base['message'] = $apiResult['error'] ?? 'Could not reach JazzCash MWallet API.';

            return $base;
        }

        if ($code === '000' || $code === '121') {
            $this->processNotification(array_merge($body, [
                'pp_TxnRefNo' => $body['pp_TxnRefNo'] ?? $txnRefNo,
                'pp_ResponseCode' => $code,
            ]), 'mwallet');

            return [
                'success' => true,
                'payment_ok' => true,
                'payment_status' => 'paid',
                'message' => $message !== '' ? $message : 'Wallet deposit completed via JazzCash.',
                'response_code' => $code,
                'mode' => 'mwallet_v2.0',
                'deposit_id' => (int) $deposit->id,
            ];
        }

        if (in_array($code, self::PENDING_RESPONSE_CODES, true)) {
            return [
                'success' => true,
                'payment_ok' => false,
                'payment_status' => 'pending',
                'message' => $message !== '' ? $message : 'Deposit is pending confirmation from JazzCash.',
                'response_code' => $code,
                'mode' => 'mwallet_v2.0',
                'deposit_id' => (int) $deposit->id,
            ];
        }

        $base['message'] = $message !== '' ? $message : 'JazzCash declined the deposit.';

        return $base;
    }

    /**
     * Legacy hosted Payment Portal form params (kept for admin tests / fallback tools).
     */
    public function getCheckoutData(Order $order, Payment $payment, ?string $customerMobile = null): ?array
    {
        if (Setting::get('jazzcash_enabled') === '0') {
            return null;
        }

        $creds = $this->credentials();
        if (!$creds) {
            return null;
        }

        $txnRefNo = $this->generateTxnRefNo((string) $order->id);
        $txnDateTime = now()->format('YmdHis');
        $params = $this->buildCheckoutParams(
            $creds,
            $txnRefNo,
            $txnDateTime,
            (float) $payment->amount,
            $order->order_number,
            'Tijaar Order ' . $order->order_number,
            $customerMobile
        );

        $payment->update([
            'gateway_reference' => $txnRefNo,
            'gateway_response' => [
                'txn_ref' => $txnRefNo,
                'txn_datetime' => $txnDateTime,
                'params_sent' => array_diff_key($params, ['pp_Password' => 1]),
            ],
        ]);

        return [
            'url' => $this->checkoutUrl(),
            'method' => 'POST',
            'params' => $params,
        ];
    }

    public function getWalletDepositCheckoutData(WalletDeposit $deposit, ?string $customerMobile = null): ?array
    {
        if (Setting::get('jazzcash_enabled') === '0') {
            return null;
        }

        $creds = $this->credentials();
        if (!$creds) {
            return null;
        }

        $txnRefNo = $this->generateTxnRefNo('W' . $deposit->id);
        $txnDateTime = now()->format('YmdHis');
        $amountRaw = (float) $deposit->amount;
        $params = $this->buildCheckoutParams(
            $creds,
            $txnRefNo,
            $txnDateTime,
            $amountRaw,
            'WALLET-' . $deposit->id,
            'Wallet deposit ' . number_format($amountRaw, 0) . ' PKR',
            $customerMobile
        );

        $deposit->update([
            'gateway_reference' => $txnRefNo,
            'gateway_response' => array_merge(
                is_array($deposit->gateway_response) ? $deposit->gateway_response : [],
                ['txn_ref' => $txnRefNo, 'txn_datetime' => $txnDateTime]
            ),
        ]);

        return [
            'url' => $this->checkoutUrl(),
            'method' => 'POST',
            'params' => $params,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{success: bool, message: string}
     */
    public function testConnection(array $overrides = []): array
    {
        $merchantId = trim((string) ($overrides['merchant_id'] ?? Setting::get('jazzcash_merchant_id') ?: config('services.jazzcash.merchant_id') ?? ''));
        $password = trim((string) ($overrides['password'] ?? Setting::get('jazzcash_password') ?: config('services.jazzcash.password') ?? ''));
        $integritySalt = trim((string) ($overrides['integrity_salt'] ?? Setting::get('jazzcash_integrity_salt') ?: config('services.jazzcash.integrity_salt') ?? ''));

        if ($merchantId === '' || $password === '') {
            return ['success' => false, 'message' => 'JazzCash Merchant ID and Password are required. Enter both fields, then test again.'];
        }

        if ($integritySalt === '') {
            return ['success' => false, 'message' => 'Integrity Salt is required to sign payment requests and verify callbacks.'];
        }

        $returnUrl = trim((string) ($overrides['return_url'] ?? Setting::get('jazzcash_return_url') ?: config('services.jazzcash.return_url') ?? ''));
        if ($returnUrl === '') {
            $returnUrl = self::recommendedReturnUrl();
        }

        if (str_contains(strtolower($returnUrl), 'simulators/return-url')) {
            return [
                'success' => false,
                'message' => 'Return URL must be your Tijaar callback, not JazzCash\'s test simulator. Use: ' . self::recommendedReturnUrl(),
            ];
        }

        $returnNote = '';
        if (!self::urlsMatch($returnUrl, self::recommendedReturnUrl())) {
            $returnNote = ' Return URL differs from recommended Tijaar callback — register this exact URL in JazzCash portal.';
        }

        $checkoutUrl = trim((string) ($overrides['checkout_url'] ?? Setting::get('jazzcash_checkout_url') ?: config('services.jazzcash.checkout_url') ?? ''));
        if ($checkoutUrl === '') {
            $checkoutUrl = self::DEFAULT_CHECKOUT_URL;
        }

        $urlWarning = '';
        try {
            $response = Http::timeout(15)->post($checkoutUrl, ['pp_Version' => '1.1']);
            $status = $response->status();
            if (!in_array($status, [200, 302, 301, 405, 403], true)) {
                $urlWarning = ' Checkout URL returned HTTP ' . $status . ' — confirm URL in JazzCash portal Credentials tab.';
            }
        } catch (\Throwable $e) {
            $urlWarning = ' Could not reach checkout URL: ' . $e->getMessage();
        }

        $sampleParams = [
            'pp_Version' => '1.1',
            'pp_TxnType' => 'MWALLET',
            'pp_Language' => 'EN',
            'pp_MerchantID' => $merchantId,
            'pp_SubMerchantID' => '',
            'pp_Password' => $password,
            'pp_BankID' => '',
            'pp_ProductID' => '',
            'pp_TxnRefNo' => self::TXN_REF_PREFIX . now()->format('YmdHis') . 'TS',
            'pp_Amount' => '10000',
            'pp_TxnCurrency' => 'PKR',
            'pp_TxnDateTime' => now()->format('YmdHis'),
            'pp_BillReference' => 'TIJAAR-TEST',
            'pp_Description' => 'Tijaar configuration test',
            'pp_TxnExpiryDateTime' => now()->addHour()->format('YmdHis'),
            'pp_ReturnURL' => $returnUrl,
            'pp_SecureHash' => '',
            'ppmpf_1' => '',
            'ppmpf_2' => '',
            'ppmpf_3' => '',
            'ppmpf_4' => '',
            'ppmpf_5' => '',
        ];
        $hash = $this->computeSecureHash($sampleParams, $integritySalt);
        if ($hash === '') {
            return ['success' => false, 'message' => 'Could not generate SecureHash. Check Integrity Salt.'];
        }

        $maskedMerchant = strlen($merchantId) > 6
            ? substr($merchantId, 0, 4) . '…' . substr($merchantId, -4)
            : $merchantId;
        $env = str_contains(strtolower($checkoutUrl), 'sandbox') ? 'Sandbox' : 'New portal';

        return [
            'success' => true,
            'message' => sprintf(
                'JazzCash credentials OK (%s). Merchant %s, SecureHash generated. Checkout: %s. Callback: %s. IPN: %s.%s%s Place a small test order to confirm.',
                $env,
                $maskedMerchant,
                $checkoutUrl,
                $returnUrl,
                self::recommendedIpnUrl(),
                $returnNote,
                $urlWarning
            ),
        ];
    }

    public static function recommendedReturnUrl(): string
    {
        return rtrim((string) config('app.url'), '/') . self::CALLBACK_PATH;
    }

    public static function recommendedIpnUrl(): string
    {
        return rtrim((string) config('app.url'), '/') . self::IPN_PATH;
    }

    /**
     * Handle browser return (pp_ReturnURL) after customer pays on JazzCash page.
     */
    public function handleCallback(array $data): bool
    {
        return $this->processNotification($data, 'callback');
    }

    /**
     * Handle server IPN from JazzCash (mandatory for onboarding).
     *
     * @return string JazzCash acknowledgment body (response code 000)
     */
    public function handleIpn(array $data): string
    {
        $ref = (string) ($data['pp_TxnRefNo'] ?? '');
        $code = (string) ($data['pp_ResponseCode'] ?? '');
        $this->logVerification("IPN received ref={$ref} code={$code}");

        $this->processNotification($data, 'ipn');
        $ack = '000Thank you for Using JazzCash, your operation successfully completed.';
        $this->logVerification("IPN ack sent ref={$ref}");

        return $ack;
    }

    /**
     * Query JazzCash for a pending transaction status.
     *
     * @return array<string, mixed>|null
     */
    public function inquireStatus(string $txnRefNo, ?string $txnDateTime = null): ?array
    {
        $creds = $this->credentials();
        if (!$creds) {
            $this->logVerification("Status Inquiry skipped (no credentials) ref={$txnRefNo}");

            return null;
        }

        // Status Inquiry v1.0 — only these fields (official OpenAPI).
        $params = [
            'pp_TxnRefNo' => $txnRefNo,
            'pp_MerchantID' => $creds['merchant_id'],
            'pp_Password' => $creds['password'],
            'pp_SecureHash' => '',
        ];
        $params['pp_SecureHash'] = $this->computeSecureHash($params, $creds['integrity_salt']);

        $url = $this->statusInquiryUrl();
        $this->logVerification("Status Inquiry request ref={$txnRefNo}");

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->asJson()
                ->post($url, $params);

            if (!$response->successful()) {
                $this->logVerification("Status Inquiry HTTP {$response->status()} ref={$txnRefNo}");

                return null;
            }

            $body = $response->json();
            if (!is_array($body)) {
                $this->logVerification("Status Inquiry invalid JSON ref={$txnRefNo}");

                return ['raw' => $response->body()];
            }

            $payCode = (string) ($body['pp_PaymentResponseCode'] ?? '');
            $status = (string) ($body['pp_Status'] ?? '');
            $respCode = (string) ($body['pp_ResponseCode'] ?? '');
            $this->logVerification(
                "Status Inquiry response ref={$txnRefNo} inquiry={$respCode} payment={$payCode} status={$status}"
            );

            return $body;
        } catch (\Throwable $e) {
            $this->logVerification("Status Inquiry failed ref={$txnRefNo}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Reconcile pending JazzCash payments via Status Inquiry API.
     */
    public function syncPendingPayments(int $limit = 50): int
    {
        $payments = Payment::query()
            ->where('gateway', 'jazzcash')
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $updated = 0;
        foreach ($payments as $payment) {
            $meta = is_array($payment->gateway_response) ? $payment->gateway_response : [];
            $ref = (string) ($payment->gateway_reference ?? $meta['txn_ref'] ?? '');
            if ($ref === '') {
                continue;
            }

            $inquiry = $this->inquireStatus($ref, $meta['txn_datetime'] ?? null);
            if ($inquiry === null || isset($inquiry['raw'])) {
                continue;
            }

            $creds = $this->credentials();
            // Verify hash on the ORIGINAL JazzCash body (do not add fields first —
            // inquiry responses often omit pp_TxnRefNo; adding it breaks the signature).
            if ($creds && !empty($inquiry['pp_SecureHash'])) {
                if (!$this->verifySecureHash($inquiry, $creds['integrity_salt'])) {
                    Log::warning('JazzCash hash verification failed', [
                        'ref' => $ref,
                        'source' => 'inquiry',
                        'expected' => $this->computeSecureHash($inquiry, $creds['integrity_salt']),
                        'received' => strtoupper(trim((string) $inquiry['pp_SecureHash'])),
                    ]);

                    continue;
                }
            }

            if (!$this->inquiryIndicatesPaid($inquiry)) {
                continue;
            }

            $paymentCode = (string) ($inquiry['pp_PaymentResponseCode'] ?? '');
            $completeCode = in_array($paymentCode, ['000', '121'], true) ? $paymentCode : '000';

            $payload = array_merge($inquiry, [
                'pp_TxnRefNo' => $inquiry['pp_TxnRefNo'] ?? $ref,
                'pp_ResponseCode' => $completeCode,
            ]);
            // Already verified above; avoid re-hash with injected pp_TxnRefNo.
            unset($payload['pp_SecureHash']);

            if ($this->processNotification($payload, 'inquiry')) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Status Inquiry: pp_ResponseCode 000 = inquiry OK; payment result is pp_Status / pp_PaymentResponseCode.
     *
     * @param  array<string, mixed>  $inquiry
     */
    protected function inquiryIndicatesPaid(array $inquiry): bool
    {
        $status = strtolower(trim((string) ($inquiry['pp_Status'] ?? '')));
        if ($status === 'completed') {
            return true;
        }

        $paymentCode = (string) ($inquiry['pp_PaymentResponseCode'] ?? '');
        if (in_array($paymentCode, ['000', '121'], true)) {
            return true;
        }

        return false;
    }

    public function generateTxnRefNo(?string $suffix = null): string
    {
        $base = self::TXN_REF_PREFIX . now()->format('YmdHis');
        if ($suffix === null || $suffix === '') {
            return $base . strtoupper(Str::random(2));
        }

        $clean = preg_replace('/[^A-Za-z0-9]/', '', $suffix) ?? '';
        $tail = strtoupper(substr($clean, -4));

        return $base . ($tail !== '' ? $tail : strtoupper(Str::random(2)));
    }

    protected function credentials(): ?array
    {
        $merchantId = Setting::get('jazzcash_merchant_id') ?: config('services.jazzcash.merchant_id');
        $password = Setting::get('jazzcash_password') ?: config('services.jazzcash.password');
        $integritySalt = Setting::get('jazzcash_integrity_salt') ?: config('services.jazzcash.integrity_salt');

        if (!$merchantId || !$password) {
            return null;
        }

        return [
            'merchant_id' => $merchantId,
            'password' => $password,
            'integrity_salt' => $integritySalt,
        ];
    }

    protected function checkoutUrl(): string
    {
        $url = Setting::get('jazzcash_checkout_url') ?: config('services.jazzcash.checkout_url');

        return $url ?: self::DEFAULT_CHECKOUT_URL;
    }

    protected function mwalletUrl(?string $mode = null): string
    {
        $mode = $mode ?: self::checkoutMode();
        if ($mode === self::MODE_MWALLET_V2) {
            $url = Setting::get('jazzcash_mwallet_v2_url') ?: config('services.jazzcash.mwallet_v2_url');

            return $url ?: self::DEFAULT_MWALLET_V2_URL;
        }

        $url = Setting::get('jazzcash_mwallet_url') ?: config('services.jazzcash.mwallet_url');

        return $url ?: self::DEFAULT_MWALLET_URL;
    }

    protected function statusInquiryUrl(): string
    {
        $url = Setting::get('jazzcash_status_inquiry_url') ?: config('services.jazzcash.status_inquiry_url');

        return $url ?: self::DEFAULT_STATUS_INQUIRY_URL;
    }

    /**
     * @param  array<string, string>  $params
     * @return array{http_status: ?int, body: array<string, mixed>, error: ?string}
     */
    protected function postMwallet(array $params, ?string $mode = null): array
    {
        $url = $this->mwalletUrl($mode);

        try {
            $response = Http::timeout(60)
                ->acceptJson()
                ->asJson()
                ->post($url, $params);

            $body = $response->json();
            if (!is_array($body)) {
                $body = ['raw' => $response->body(), 'pp_ResponseMessage' => 'Invalid JazzCash response.'];
            }

            Log::info('JazzCash MWallet response', [
                'url' => $url,
                'mode' => $mode,
                'http' => $response->status(),
                'code' => $body['pp_ResponseCode'] ?? null,
                'message' => $body['pp_ResponseMessage'] ?? null,
            ]);

            return [
                'http_status' => $response->status(),
                'body' => $body,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('JazzCash MWallet request failed: ' . $e->getMessage(), ['url' => $url]);

            return [
                'http_status' => null,
                'body' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * MWallet Payment v1.1 JSON body (official Mobile Wallets API).
     *
     * @param  array{merchant_id: string, password: string, integrity_salt: ?string}  $creds
     * @return array<string, string>
     */
    protected function buildMwalletV11Params(
        array $creds,
        string $txnRefNo,
        string $txnDateTime,
        float $amountRaw,
        string $billReference,
        string $description,
        string $customerMobile,
    ): array {
        $baseUrl = config('app.url');
        $returnUrl = Setting::get('jazzcash_return_url')
            ?: config('services.jazzcash.return_url', $baseUrl . self::CALLBACK_PATH);

        // Amount in paisa (last 2 digits = fraction), e.g. 100.00 PKR → 10000
        $amount = (int) round($amountRaw * 100);

        $params = [
            'pp_Version' => '1.1',
            'pp_TxnType' => 'MWALLET',
            'pp_Language' => 'EN',
            'pp_MerchantID' => $creds['merchant_id'],
            'pp_SubMerchantID' => '',
            'pp_Password' => $creds['password'],
            'pp_BankID' => '',
            'pp_ProductID' => '',
            'pp_TxnRefNo' => $txnRefNo,
            'pp_Amount' => (string) $amount,
            'pp_TxnCurrency' => 'PKR',
            'pp_TxnDateTime' => $txnDateTime,
            'pp_BillReference' => substr($billReference, 0, 50),
            'pp_Description' => substr($description, 0, 200),
            'pp_TxnExpiryDateTime' => now()->addDays(1)->format('YmdHis'),
            'pp_ReturnURL' => $returnUrl,
            'pp_SecureHash' => '',
            'ppmpf_1' => $customerMobile,
            'ppmpf_2' => '',
            'ppmpf_3' => '',
            'ppmpf_4' => '',
            'ppmpf_5' => '',
        ];

        $params['pp_SecureHash'] = $this->computeSecureHash($params, $creds['integrity_salt']);

        return $params;
    }

    /**
     * MWallet Payment v2.0 — mobile + CNIC (no ReturnURL in official sample).
     *
     * @param  array{merchant_id: string, password: string, integrity_salt: ?string}  $creds
     * @return array<string, string>
     */
    protected function buildMwalletV2Params(
        array $creds,
        string $txnRefNo,
        string $txnDateTime,
        float $amountRaw,
        string $billReference,
        string $description,
        string $customerMobile,
        string $cnic,
    ): array {
        $amount = (int) round($amountRaw * 100);

        $params = [
            'pp_Language' => 'EN',
            'pp_MerchantID' => $creds['merchant_id'],
            'pp_SubMerchantID' => '',
            'pp_Password' => $creds['password'],
            'pp_TxnRefNo' => $txnRefNo,
            'pp_MobileNumber' => $customerMobile,
            'pp_CNIC' => $cnic,
            'pp_Amount' => (string) $amount,
            'pp_DiscountedAmount' => '',
            'pp_TxnCurrency' => 'PKR',
            'pp_TxnDateTime' => $txnDateTime,
            'pp_BillReference' => substr($billReference, 0, 50),
            'pp_Description' => substr($description, 0, 200),
            'pp_TxnExpiryDateTime' => now()->addDays(1)->format('YmdHis'),
            'pp_SecureHash' => '',
            'ppmpf_1' => '',
            'ppmpf_2' => '',
            'ppmpf_3' => '',
            'ppmpf_4' => '',
            'ppmpf_5' => '',
        ];

        $params['pp_SecureHash'] = $this->computeSecureHash($params, $creds['integrity_salt']);

        return $params;
    }

    /**
     * Hosted Payment Portal form params (legacy).
     *
     * @param  array{merchant_id: string, password: string, integrity_salt: ?string}  $creds
     * @return array<string, string>
     */
    protected function buildCheckoutParams(
        array $creds,
        string $txnRefNo,
        string $txnDateTime,
        float $amountRaw,
        string $billReference,
        string $description,
        ?string $customerMobile = null,
    ): array {
        $baseUrl = config('app.url');
        $returnUrl = Setting::get('jazzcash_return_url')
            ?: config('services.jazzcash.return_url', $baseUrl . self::CALLBACK_PATH);

        $amount = (int) round($amountRaw * 100);
        $mobile = self::normalizeMobile($customerMobile) ?? '';

        // Payment Portal: set MWALLET so JazzCash opens wallet flow (asks for mobile / MPIN).
        // Official docs: pp_TxnType optional; if set, page opens only that product.
        $params = [
            'pp_Version' => '1.1',
            'pp_TxnType' => 'MWALLET',
            'pp_Language' => 'EN',
            'pp_MerchantID' => $creds['merchant_id'],
            'pp_SubMerchantID' => '',
            'pp_Password' => $creds['password'],
            'pp_BankID' => '',
            'pp_ProductID' => '',
            'pp_TxnRefNo' => $txnRefNo,
            'pp_Amount' => (string) $amount,
            'pp_TxnCurrency' => 'PKR',
            'pp_TxnDateTime' => $txnDateTime,
            'pp_BillReference' => $billReference,
            'pp_Description' => $description,
            'pp_TxnExpiryDateTime' => now()->addDays(3)->format('YmdHis'),
            'pp_ReturnURL' => $returnUrl,
            'pp_SecureHash' => '',
            'ppmpf_1' => $mobile,
            'ppmpf_2' => '',
            'ppmpf_3' => '',
            'ppmpf_4' => '',
            'ppmpf_5' => '',
        ];

        $params['pp_SecureHash'] = $this->computeSecureHash($params, $creds['integrity_salt']);

        return $params;
    }

    protected function processNotification(array $data, string $source = 'callback'): bool
    {
        $txnRefNo = $data['pp_TxnRefNo'] ?? null;
        $responseCode = $data['pp_ResponseCode'] ?? null;

        if (!$txnRefNo) {
            return false;
        }

        $creds = $this->credentials();
        if ($creds && isset($data['pp_SecureHash']) && $data['pp_SecureHash'] !== '') {
            if (!$this->verifySecureHash($data, $creds['integrity_salt'])) {
                Log::warning('JazzCash hash verification failed', ['ref' => $txnRefNo, 'source' => $source]);

                return false;
            }
        }

        if ((string) $responseCode !== '000' && (string) $responseCode !== '121') {
            return true;
        }

        if ($this->completeWalletDeposit($txnRefNo, $data)) {
            return true;
        }

        return $this->completeOrderPayment($txnRefNo, $data);
    }

    /**
     * Short one-line logs for Hassan (Status Inquiry + IPN only).
     */
    public function logVerification(string $message): void
    {
        Log::info('JAZZCASH VERIFY | ' . $message);
    }

    protected function completeWalletDeposit(string $txnRefNo, array $data): bool
    {
        $deposit = WalletDeposit::query()
            ->where('gateway', 'jazzcash')
            ->where('gateway_reference', $txnRefNo)
            ->first();

        if (!$deposit || $deposit->status !== 'pending') {
            return false;
        }

        $deposit->update(['gateway_response' => array_merge($deposit->gateway_response ?? [], $data)]);
        $wallet = Wallet::getOrCreateForUser($deposit->user_id, $deposit->currency);
        WalletLedgerService::recordDeposit(
            $wallet,
            (float) $deposit->amount,
            (int) $deposit->id,
            'Payment Added to Wallet via JazzCash',
            ['gateway' => 'jazzcash', 'deposit_id' => $deposit->id]
        );
        $deposit->markCompleted($txnRefNo);

        ListingFeeService::applyAfterDeposit($deposit->fresh());

        return true;
    }

    protected function completeOrderPayment(string $txnRefNo, array $data): bool
    {
        $payment = Payment::query()
            ->where('gateway', 'jazzcash')
            ->where('gateway_reference', $txnRefNo)
            ->first();

        if (!$payment || $payment->status === 'completed') {
            return $payment !== null;
        }

        $order = $payment->order;
        if (!$order) {
            return false;
        }

        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
            'gateway_response' => array_merge($payment->gateway_response ?? [], $data),
        ]);

        OrderWorkflowService::markPaymentSuccess($order->fresh(), 'Payment completed via JazzCash');

        return true;
    }

/**
 * JazzCash HMAC-SHA256 secure hash (Payment Portal + REST).
 *
 * Official OpenAPI docs:
 * Algorithm: HMAC-SHA256 | Key: Integrity Salt | Message encoding: ISO-8859-1 (Latin-1) | Output: uppercase hex
 * 1. pp* params except pp_SecureHash, non-empty (not null/blank)
 * 2. Sort parameter names ordinal (byte-wise)
 * 3. {IntegritySalt}&{value1}&{value2}&...
 * 4. HMAC-SHA256 → uppercase hex
 */
private function computeSecureHash(array $params, ?string $salt): string
{
    if ($salt === null || trim($salt) === '') {
        return '';
    }
    $salt = trim($salt);
    $filtered = [];
    foreach ($params as $key => $value) {
        if (!is_string($key) || !str_starts_with($key, 'pp')) {
            continue;
        }
        if (strcasecmp($key, 'pp_SecureHash') === 0) {
            continue;
        }
        if ($value === null || !is_scalar($value)) {
            continue;
        }
        $strValue = trim((string) $value);
        if ($strValue === '') {
            continue;
        }
        $filtered[$key] = $strValue;
    }
    ksort($filtered, SORT_STRING);
    $values = array_values($filtered);
    $concatenated = count($values) > 0
        ? $salt . '&' . implode('&', $values)
        : $salt;

    if (function_exists('mb_convert_encoding')) {
        $concatenated = mb_convert_encoding($concatenated, 'ISO-8859-1', 'UTF-8');
        $saltKey = mb_convert_encoding($salt, 'ISO-8859-1', 'UTF-8');
    } else {
        $saltKey = $salt;
    }

    return strtoupper(hash_hmac('sha256', $concatenated, $saltKey));
}
private function verifySecureHash(array $data, ?string $salt): bool
{
    $received = strtoupper(trim((string) ($data['pp_SecureHash'] ?? '')));
    if ($received === '') {
        return true;
    }
    $expected = $this->computeSecureHash($data, $salt);
    if ($expected === '') {
        return false;
    }
    return hash_equals($expected, $received);
}

    protected static function urlsMatch(string $a, string $b): bool
    {
        return rtrim(strtolower(trim($a)), '/') === rtrim(strtolower(trim($b)), '/');
    }
}
