<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\WalletDeposit;
use App\Services\EasypaisaService;
use App\Services\JazzCashService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    public function stripeWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        (new StripeService())->handleWebhook($payload, $signature);

        return response('', 200);
    }

    /**
     * JazzCash callback - receives POST from JazzCash after user completes payment.
     * Redirects user to frontend success page.
     */
    public function jazzcashCallback(Request $request)
    {
        (new JazzCashService())->handleCallback($request->all());
        $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:3001'), '/');
        $ref = (string) ($request->input('pp_TxnRefNo') ?? '');

        $deposit = WalletDeposit::query()
            ->where('gateway', 'jazzcash')
            ->where('gateway_reference', $ref)
            ->first();
        if ($deposit) {
            $meta = is_array($deposit->gateway_response) ? $deposit->gateway_response : [];
            if (($meta['purpose'] ?? null) === 'listing_fee') {
                $pid = (int) ($meta['product_id'] ?? 0);
                $qs = $pid > 0 ? "?paid=1&product_id={$pid}" : '?paid=1';

                return redirect("{$frontendUrl}/customer/listings{$qs}");
            }

            return redirect("{$frontendUrl}/seller/wallet/deposit/success");
        }

        $payment = Payment::query()
            ->where('gateway', 'jazzcash')
            ->where('gateway_reference', $ref)
            ->first();
        if ($payment?->order_id) {
            return redirect("{$frontendUrl}/customer/orders/{$payment->order_id}?paid=1");
        }

        if (preg_match('/^T(\d+)_/', $ref, $m)) {
            return redirect("{$frontendUrl}/customer/orders/{$m[1]}?paid=1");
        }

        return redirect("{$frontendUrl}/customer/orders");
    }

    /**
     * JazzCash IPN — server-to-server payment notification (register in JazzCash portal).
     */
    public function jazzcashIpn(Request $request): Response
    {
        $ack = (new JazzCashService())->handleIpn($request->all());

        return response($ack, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Easypaisa callback - receives POST from Easypaisa after user completes payment.
     * Redirects user to frontend success page.
     */
    public function easypaisaCallback(Request $request)
    {
        (new EasypaisaService())->handleCallback($request->all());
        $frontendUrl = config('app.frontend_url', 'http://localhost:3001');
        $ref = (string) ($request->input('orderId') ?? $request->input('orderRefNum') ?? '');
        if (preg_match('/^WD\d+_/', $ref)) {
            $deposit = WalletDeposit::query()
                ->where('gateway', 'easypaisa')
                ->where('gateway_reference', $ref)
                ->first();
            if ($deposit) {
                $meta = is_array($deposit->gateway_response) ? $deposit->gateway_response : [];
                if (($meta['purpose'] ?? null) === 'listing_fee') {
                    $pid = (int) ($meta['product_id'] ?? 0);
                    $qs = $pid > 0 ? "?paid=1&product_id={$pid}" : '?paid=1';

                    return redirect("{$frontendUrl}/customer/listings{$qs}");
                }
            }

            return redirect("{$frontendUrl}/seller/wallet/deposit/success");
        }
        if (preg_match('/^EP(\d+)_/', $ref, $m)) {
            return redirect("{$frontendUrl}/customer/orders/{$m[1]}?paid=1");
        }
        return redirect("{$frontendUrl}/customer/orders");
    }
}
