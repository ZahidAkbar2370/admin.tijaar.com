<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletDeposit;
use App\Models\WalletTransaction;
use App\Services\EasypaisaService;
use Illuminate\Support\Facades\DB;
use App\Services\JazzCashService;
use App\Services\PayoutService;
use App\Services\StripeService;
use App\Services\WalletLedgerService;
use App\Support\WalletTransactionLabel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * Create a wallet deposit intent and return payment gateway redirect (JazzCash, Easypaisa, Stripe).
     */
    public function createDeposit(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:100|max:500000',
            'gateway' => 'required|string|in:jazzcash,easypaisa,stripe',
            'payment_phone' => 'nullable|string|max:20',
            'payment_cnic' => 'nullable|string|max:20',
        ]);

        $user = $request->user();
        $amount = (float) $request->input('amount');
        $gateway = $request->input('gateway');
        $currency = 'PKR';

        if ($gateway === 'jazzcash') {
            // Hosted portal — mobile/CNIC not required (collected on JazzCash page).
        }

        $deposit = WalletDeposit::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => $currency,
            'gateway' => $gateway,
            'status' => 'pending',
        ]);

        $frontendUrl = config('app.frontend_url', 'http://localhost:3001');
        $successUrl = $frontendUrl . '/seller/wallet/deposit/success';
        $cancelUrl = $frontendUrl . '/seller/wallet/deposit';

        if ($gateway === 'stripe') {
            $url = (new StripeService())->createWalletDepositSession($deposit, $successUrl, $cancelUrl);
            if ($url) {
                return response()->json([
                    'success' => true,
                    'checkout_url' => $url,
                    'deposit_id' => $deposit->id,
                ]);
            }
            $this->creditWalletForDeposit($deposit, $gateway);
            return response()->json([
                'success' => true,
                'message' => number_format($amount, 0) . ' PKR added to your wallet.',
            ]);
        }

        if ($gateway === 'jazzcash') {
            $jazzPhone = JazzCashService::normalizeMobile($request->input('payment_phone') ?: $user->phone);
            $result = (new JazzCashService())->processWalletDeposit(
                $deposit,
                $jazzPhone,
                $request->input('payment_cnic')
            );

            $payload = [
                'success' => (bool) ($result['success'] ?? false) || !empty($result['checkout_url']),
                'payment_ok' => (bool) ($result['payment_ok'] ?? false),
                'payment_status' => $result['payment_status'] ?? 'failed',
                'message' => $result['message'] ?? 'JazzCash deposit processed.',
                'response_code' => $result['response_code'] ?? null,
                'jazzcash_mode' => $result['mode'] ?? JazzCashService::checkoutMode(),
                'deposit_id' => $deposit->id,
            ];
            if (!empty($result['checkout_url'])) {
                $payload['checkout_url'] = $result['checkout_url'];
                $payload['checkout_method'] = $result['checkout_method'] ?? 'POST';
                $payload['checkout_params'] = $result['checkout_params'] ?? [];
            }

            $okHttp = ($result['payment_ok'] ?? false)
                || ($result['payment_status'] ?? '') === 'pending'
                || !empty($result['checkout_url']);

            return response()->json($payload, $okHttp ? 200 : 422);
        }

        if ($gateway === 'easypaisa') {
            $epPhone = JazzCashService::normalizeMobile($request->input('payment_phone') ?: $user->phone);
            $data = (new EasypaisaService())->getWalletDepositCheckoutData($deposit, $epPhone, $user->email);
            if ($data) {
                return response()->json([
                    'success' => true,
                    'checkout_url' => $data['url'],
                    'checkout_method' => $data['method'],
                    'checkout_params' => $data['params'],
                    'deposit_id' => $deposit->id,
                ]);
            }
            $this->creditWalletForDeposit($deposit, $gateway);
            return response()->json([
                'success' => true,
                'message' => number_format($amount, 0) . ' PKR added to your wallet.',
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid gateway.'], 422);
    }

    /**
     * Credit user wallet for a deposit when gateway is not configured (e.g. development).
     */
    private function creditWalletForDeposit(WalletDeposit $deposit, string $gateway): void
    {
        DB::transaction(function () use ($deposit, $gateway) {
            $wallet = Wallet::getOrCreateForUser($deposit->user_id, $deposit->currency ?? 'PKR');
            $amount = (float) $deposit->amount;
            WalletLedgerService::recordDeposit(
                $wallet,
                $amount,
                (int) $deposit->id,
                'Payment Added to Wallet (' . $gateway . ')',
                ['gateway' => $gateway, 'deposit_id' => $deposit->id]
            );
            $deposit->markCompleted('dev');
        });
    }

    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallet = Wallet::getOrCreateForUser($user->id);
        $balance = (float) $wallet->balance;

        $payload = [
            'success' => true,
            'balance' => $balance,
            'currency' => $wallet->currency,
        ];

        // For sellers: wallet is already credited when orders are delivered, so spendable = wallet balance only (adding availableEarnings would double-count).
        $sellerType = $user->role === 'seller' ? 'business' : ($user->is_private_seller ? 'private' : null);
        if ($sellerType !== null) {
            $earnings = $user->role === 'seller' && !$user->seller?->store
                ? ['net' => 0]
                : PayoutService::getEarningsForUser($user, $sellerType);
            $payload['available_earnings'] = (float) ($earnings['net'] ?? 0);
            $payload['spendable_balance'] = $balance;
        }

        return response()->json($payload);
    }

    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallet = Wallet::getOrCreateForUser($user->id);

        $typeFilter = $request->input('type');
        $statusFilter = strtolower((string) $request->input('status', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $purpose = strtolower(trim((string) $request->input('purpose', '')));
        $buyerPurchasesOnly = in_array($purpose, ['buyer_purchases', 'buyer', 'purchases'], true);

        // 1) Wallet ledger entries (already applied → success)
        $wq = $wallet->transactions();
        if ($buyerPurchasesOnly) {
            // Buyer order payments from wallet only (not seller delivery credits / fees / payouts).
            $wq->where('type', 'order_payment')
                ->where('reference_type', 'order')
                ->where('amount', '<', 0);
        } elseif ($typeFilter) {
            $wq->where('type', $typeFilter);
        }
        if ($dateFrom) {
            $wq->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $wq->whereDate('created_at', '<=', $dateTo);
        }
        $walletTxnModels = $wq->orderByDesc('created_at')->get();

        $depositIds = $walletTxnModels
            ->filter(fn ($t) => $t->reference_type === 'wallet_deposit' && $t->reference_id)
            ->pluck('reference_id')
            ->unique()
            ->values();
        $depositsById = $depositIds->isEmpty()
            ? collect()
            : WalletDeposit::whereIn('id', $depositIds)->get()->keyBy('id');

        $walletOrderIds = $walletTxnModels
            ->filter(fn ($t) => $t->reference_type === 'order' && $t->reference_id)
            ->pluck('reference_id')
            ->unique()
            ->values();
        $walletOrdersById = $walletOrderIds->isEmpty()
            ? collect()
            : Order::whereIn('id', $walletOrderIds)->get()->keyBy('id');

        $ledgerBuyerOrderIds = $walletTxnModels
            ->filter(fn ($t) => $t->type === 'order_payment'
                && $t->reference_type === 'order'
                && (float) $t->amount < 0)
            ->pluck('reference_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $walletTxns = $walletTxnModels->map(function ($t) use ($depositsById, $walletOrdersById) {
            $amount = (float) $t->amount;
            $meta = is_array($t->meta) ? $t->meta : [];
            $title = WalletTransactionLabel::title((string) $t->type, $amount, $meta);
            $row = [
                'id' => $t->id,
                'type' => $t->type,
                'title' => $title,
                'amount' => $amount,
                'signed_amount' => WalletTransactionLabel::signedAmount($amount),
                'balance_after' => $t->balance_after !== null ? (float) $t->balance_after : null,
                'description' => $t->description,
                'reference_type' => $t->reference_type,
                'reference_id' => $t->reference_id,
                'created_at' => $t->created_at,
                'source' => 'wallet',
                'status' => 'success',
                'payment_method' => $this->resolveWalletTxnPaymentMethod($t, $depositsById),
            ];
            if ($t->reference_type === 'order' && $t->reference_id) {
                $order = $walletOrdersById->get((int) $t->reference_id);
                if ($order) {
                    $row = array_merge($row, $this->buyerOrderPaymentMeta($order));
                }
            }

            return $row;
        });

        // 2) Online order payments (success / pending / failed)
        $orderPayments = collect();
        if ($buyerPurchasesOnly || ! $typeFilter || $typeFilter === 'order_payment') {
            $oq = Order::where('user_id', $user->id)
                ->whereIn('payment_method', ['jazzcash', 'jazzcash_partial', 'stripe', 'paypal', 'easypaisa'])
                ->whereIn('payment_status', ['paid', 'partial_paid', 'pending', 'failed']);
            if ($dateFrom) {
                $oq->whereDate('updated_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $oq->whereDate('updated_at', '<=', $dateTo);
            }
            $orderPayments = $oq->orderByDesc('updated_at')->get()
                ->filter(fn ($o) => ! $ledgerBuyerOrderIds->has((int) $o->id))
                ->map(function ($o) {
                $online = round((float) $o->total - (float) ($o->cod_amount ?? 0), 2);
                $label = $this->formatPaymentMethodLabel((string) $o->payment_method);
                $ps = (string) $o->payment_status;
                $status = match ($ps) {
                    'paid', 'partial_paid' => 'success',
                    'pending' => 'pending',
                    'failed' => 'failed',
                    default => $ps !== '' ? $ps : 'pending',
                };

                $amount = -abs($online); // buyer payment (debit)
                return array_merge([
                    'id' => 'order-' . $o->id,
                    'type' => 'order_payment',
                    'title' => WalletTransactionLabel::title('order_payment', $amount),
                    'amount' => $amount,
                    'signed_amount' => WalletTransactionLabel::signedAmount($amount),
                    'balance_after' => null,
                    'description' => "Order Payment — #{$o->order_number}",
                    'reference_type' => 'order',
                    'reference_id' => $o->id,
                    'created_at' => $o->updated_at ?? $o->created_at,
                    'source' => 'order',
                    'status' => $status,
                    'payment_method' => $label,
                ], $this->buyerOrderPaymentMeta($o));
            });
        }

        // 3) Gateway deposits that are still pending or failed (completed ones appear as wallet credits)
        $depositRows = collect();
        if (! $buyerPurchasesOnly && (! $typeFilter || in_array($typeFilter, ['deposit', 'listing_fee'], true))) {
            $dq = WalletDeposit::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'failed']);
            if ($dateFrom) {
                $dq->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $dq->whereDate('created_at', '<=', $dateTo);
            }
            $depositRows = $dq->orderByDesc('created_at')->get()->map(function (WalletDeposit $d) use ($typeFilter) {
                $meta = is_array($d->gateway_response) ? $d->gateway_response : [];
                $isListingFee = ($meta['purpose'] ?? null) === 'listing_fee';
                $type = $isListingFee ? 'listing_fee' : 'deposit';
                if ($typeFilter && $typeFilter !== $type) {
                    return null;
                }
                $gatewayLabel = $this->formatPaymentMethodLabel((string) $d->gateway);
                $productId = (int) ($meta['product_id'] ?? 0);
                $amount = (float) $d->amount;
                $title = WalletTransactionLabel::title($type, $amount);
                $description = $isListingFee
                    ? ($productId > 0
                        ? "{$title} ({$gatewayLabel}) for product #{$productId}"
                        : "{$title} ({$gatewayLabel})")
                    : "{$title} ({$gatewayLabel})";
                if (!empty($meta['failure_message'])) {
                    $description .= ' — ' . $meta['failure_message'];
                } elseif (!empty($meta['api_response']['pp_ResponseMessage'])) {
                    $description .= ' — ' . $meta['api_response']['pp_ResponseMessage'];
                }

                return [
                    'id' => 'deposit-' . $d->id,
                    'type' => $type,
                    'title' => $title,
                    'amount' => $amount,
                    'signed_amount' => WalletTransactionLabel::signedAmount($amount),
                    'balance_after' => null,
                    'description' => $description,
                    'reference_type' => 'wallet_deposit',
                    'reference_id' => $d->id,
                    'created_at' => $d->created_at,
                    'source' => 'deposit',
                    'status' => $d->paymentStatusLabel(),
                    'gateway' => $d->gateway,
                    'payment_method' => $gatewayLabel,
                ];
            })->filter()->values();
        }

        $merged = $walletTxns->concat($orderPayments)->concat($depositRows)
            ->sortByDesc(fn ($r) => $r['created_at'] ? $r['created_at']->timestamp : 0)
            ->values();

        if (in_array($statusFilter, ['success', 'pending', 'failed'], true)) {
            $merged = $merged->filter(fn ($r) => ($r['status'] ?? '') === $statusFilter)->values();
        }

        $paymentMethodFilter = strtolower(trim((string) $request->input('payment_method', '')));
        if ($paymentMethodFilter !== '' && $paymentMethodFilter !== 'all') {
            $merged = $merged->filter(function ($r) use ($paymentMethodFilter) {
                $pm = strtolower(trim((string) ($r['payment_method'] ?? '')));
                if ($pm === '' || $pm === '—') {
                    return false;
                }
                // Match exact label or gateway key (e.g. jazzcash / JazzCash / JazzCash (partial))
                return $pm === $paymentMethodFilter
                    || str_contains($pm, $paymentMethodFilter)
                    || str_contains(str_replace([' ', '(', ')'], ['', '', ''], $pm), str_replace([' ', '_'], '', $paymentMethodFilter));
            })->values();
        }

        // Optional status sort: pending → success → failed (then newest)
        $sort = strtolower(trim((string) $request->input('sort', '')));
        if ($sort === 'status') {
            $rank = ['pending' => 0, 'success' => 1, 'failed' => 2];
            $merged = $merged->sort(function ($a, $b) use ($rank) {
                $ra = $rank[strtolower((string) ($a['status'] ?? 'success'))] ?? 9;
                $rb = $rank[strtolower((string) ($b['status'] ?? 'success'))] ?? 9;
                if ($ra !== $rb) {
                    return $ra <=> $rb;
                }
                $ta = $a['created_at'] ? $a['created_at']->timestamp : 0;
                $tb = $b['created_at'] ? $b['created_at']->timestamp : 0;

                return $tb <=> $ta;
            })->values();
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 10)));
        $page = max(1, (int) $request->input('page', 1));
        $total = $merged->count();
        $items = $merged->forPage($page, $perPage)->map(function ($r) {
            $r['created_at'] = $r['created_at'] ? $r['created_at']->toISOString() : null;
            return $r;
        })->values();

        return response()->json([
            'success' => true,
            'transactions' => $items,
            'pagination' => [
                'current_page' => $page,
                'last_page' => (int) max(1, ceil(max(1, $total) / $perPage)),
                'total' => $total,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallet = Wallet::getOrCreateForUser($user->id);
        $purpose = strtolower(trim((string) $request->input('purpose', '')));
        $buyerPurchasesOnly = in_array($purpose, ['buyer_purchases', 'buyer', 'purchases'], true);
        $typeFilter = $request->input('type');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = $wallet->transactions();
        if ($buyerPurchasesOnly) {
            $query->where('type', 'order_payment')
                ->where('reference_type', 'order')
                ->where('amount', '<', 0);
        } elseif ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $txns = $query->orderByDesc('created_at')->get();

        $columns = ['id', 'type', 'title', 'amount', 'signed_amount', 'balance_after', 'description', 'status', 'payment_method', 'reference_type', 'reference_id', 'created_at'];
        $depositIds = $txns
            ->filter(fn ($t) => $t->reference_type === 'wallet_deposit' && $t->reference_id)
            ->pluck('reference_id')
            ->unique()
            ->values();
        $depositsById = $depositIds->isEmpty()
            ? collect()
            : WalletDeposit::whereIn('id', $depositIds)->get()->keyBy('id');

        $rows = $txns->map(function ($t) use ($depositsById) {
            $amount = (float) $t->amount;
            $meta = is_array($t->meta) ? $t->meta : [];
            $title = WalletTransactionLabel::title((string) $t->type, $amount, $meta);

            return [
                'id' => $t->id,
                'type' => $t->type,
                'title' => $title,
                'amount' => $t->amount,
                'signed_amount' => WalletTransactionLabel::signedAmount($amount),
                'balance_after' => $t->balance_after ?? '',
                'description' => $t->description ?? '',
                'status' => 'success',
                'payment_method' => $this->resolveWalletTxnPaymentMethod($t, $depositsById),
                'reference_type' => $t->reference_type ?? '',
                'reference_id' => $t->reference_id ?? '',
                'created_at' => $t->created_at?->format('Y-m-d H:i:s') ?? '',
            ];
        });

        // Online gateway order payments (same scope as transactions list)
        if ($buyerPurchasesOnly || ! $typeFilter || $typeFilter === 'order_payment') {
            $oq = Order::where('user_id', $user->id)
                ->whereIn('payment_method', ['jazzcash', 'jazzcash_partial', 'stripe', 'paypal', 'easypaisa'])
                ->whereIn('payment_status', ['paid', 'partial_paid', 'pending', 'failed']);
            if ($dateFrom) {
                $oq->whereDate('updated_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $oq->whereDate('updated_at', '<=', $dateTo);
            }
            foreach ($oq->orderByDesc('updated_at')->get() as $o) {
                $online = round((float) $o->total - (float) ($o->cod_amount ?? 0), 2);
                $ps = (string) $o->payment_status;
                $status = match ($ps) {
                    'paid', 'partial_paid' => 'success',
                    'pending' => 'pending',
                    'failed' => 'failed',
                    default => $ps !== '' ? $ps : 'pending',
                };
                $amount = -abs($online);
                $rows->push([
                    'id' => 'order-' . $o->id,
                    'type' => 'order_payment',
                    'title' => WalletTransactionLabel::title('order_payment', $amount),
                    'amount' => $amount,
                    'signed_amount' => WalletTransactionLabel::signedAmount($amount),
                    'balance_after' => '',
                    'description' => "Order Payment — #{$o->order_number}",
                    'status' => $status,
                    'payment_method' => $this->formatPaymentMethodLabel((string) $o->payment_method),
                    'reference_type' => 'order',
                    'reference_id' => $o->id,
                    'created_at' => ($o->updated_at ?? $o->created_at)?->format('Y-m-d H:i:s') ?? '',
                ]);
            }
        }

        // Pending/failed deposits — not buyer purchase payments
        if (! $buyerPurchasesOnly) {
            $deposits = WalletDeposit::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'failed'])
                ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
                ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
                ->orderByDesc('created_at')
                ->get();

            foreach ($deposits as $d) {
                $meta = is_array($d->gateway_response) ? $d->gateway_response : [];
                $isListingFee = ($meta['purpose'] ?? null) === 'listing_fee';
                $type = $isListingFee ? 'listing_fee' : 'deposit';
                if ($request->filled('type') && $request->type !== $type) {
                    continue;
                }
                $amount = (float) $d->amount;
                $title = WalletTransactionLabel::title($type, $amount);
                $rows->push([
                    'id' => 'deposit-' . $d->id,
                    'type' => $type,
                    'title' => $title,
                    'amount' => $d->amount,
                    'signed_amount' => WalletTransactionLabel::signedAmount($amount),
                    'balance_after' => '',
                    'description' => $title . ' (' . $d->gateway . ')',
                    'status' => $d->paymentStatusLabel(),
                    'payment_method' => $this->formatPaymentMethodLabel((string) $d->gateway),
                    'reference_type' => 'wallet_deposit',
                    'reference_id' => $d->id,
                    'created_at' => $d->created_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }
        }

        $statusFilter = strtolower((string) $request->input('status', ''));
        if (in_array($statusFilter, ['success', 'pending', 'failed'], true)) {
            $rows = $rows->filter(fn ($r) => ($r['status'] ?? '') === $statusFilter)->values();
        }

        $paymentMethodFilter = strtolower(trim((string) $request->input('payment_method', '')));
        if ($paymentMethodFilter !== '' && $paymentMethodFilter !== 'all') {
            $rows = $rows->filter(function ($r) use ($paymentMethodFilter) {
                $pm = strtolower(trim((string) ($r['payment_method'] ?? '')));
                if ($pm === '' || $pm === '—') {
                    return false;
                }

                return $pm === $paymentMethodFilter
                    || str_contains($pm, $paymentMethodFilter)
                    || str_contains(str_replace([' ', '(', ')'], ['', '', ''], $pm), str_replace([' ', '_'], '', $paymentMethodFilter));
            })->values();
        }

        $rows = $rows->sortByDesc('created_at')->values();

        return response()->json(['success' => true, 'rows' => $rows, 'columns' => $columns]);
    }

    /**
     * Buyer-facing order payment meta + line breakdown for Transactions UI.
     */
    private function buyerOrderPaymentMeta(Order $o): array
    {
        $orderPayment = (float) ($o->subtotal ?? 0);
        $shipping = (float) ($o->shipping_cost ?? 0);
        $discount = (float) ($o->discount_amount ?? 0);
        $marketplace = (float) ($o->marketplace_fee ?? 0);
        $onlineFee = (float) ($o->online_transaction_fee ?? 0);
        $tax = (float) ($o->tax_amount ?? 0);
        $cod = (float) ($o->cod_amount ?? 0);
        $onlinePaid = (float) ($o->online_amount ?? 0);
        if ($onlinePaid <= 0) {
            $onlinePaid = max(0, (float) $o->total - $cod);
        }

        $lines = [];
        if ($orderPayment > 0) {
            $lines[] = ['key' => 'order_payment', 'label' => 'Order payment', 'amount' => $orderPayment];
        }
        if ($shipping > 0) {
            $lines[] = ['key' => 'shipping', 'label' => 'Shipping', 'amount' => $shipping];
        }
        if ($discount > 0) {
            $lines[] = ['key' => 'discount', 'label' => 'Discount', 'amount' => -$discount];
        }
        if ($marketplace > 0) {
            $label = 'Marketplace fee';
            if (($o->marketplace_fee_type ?? '') === 'percentage' && $o->marketplace_fee_rate !== null) {
                $label .= ' ('.$o->marketplace_fee_rate.'%)';
            }
            $lines[] = ['key' => 'marketplace_fee', 'label' => $label, 'amount' => $marketplace];
        }
        if ($onlineFee > 0) {
            $label = 'Online transaction fee';
            if (($o->online_transaction_fee_type ?? '') === 'percentage' && $o->online_transaction_fee_rate !== null) {
                $label .= ' ('.$o->online_transaction_fee_rate.'%)';
            }
            $lines[] = ['key' => 'online_transaction_fee', 'label' => $label, 'amount' => $onlineFee];
        }
        if ($tax > 0) {
            $lines[] = ['key' => 'tax', 'label' => 'Tax', 'amount' => $tax];
        }
        if ($cod > 0) {
            $lines[] = ['key' => 'cod_amount', 'label' => 'Due on delivery (COD)', 'amount' => $cod];
        }
        if ($onlinePaid > 0 && $cod > 0) {
            $lines[] = ['key' => 'online_amount', 'label' => 'Paid online', 'amount' => $onlinePaid];
        }

        return [
            'order_number' => $o->order_number,
            'breakdown' => [
                'order_payment' => $orderPayment,
                'shipping' => $shipping,
                'discount' => $discount,
                'marketplace_fee' => $marketplace,
                'online_transaction_fee' => $onlineFee,
                'tax' => $tax,
                'cod_amount' => $cod,
                'online_amount' => $onlinePaid,
                'total' => (float) ($o->total ?? 0),
                'lines' => $lines,
            ],
        ];
    }

    /**
     * Deduction from wallet → "Wallet"; gateway deposit credit → gateway name; else "—".
     *
     * @param  \Illuminate\Support\Collection<int, WalletDeposit>  $depositsById
     */
    private function resolveWalletTxnPaymentMethod(WalletTransaction $t, $depositsById): string
    {
        $amount = (float) $t->amount;

        $meta = is_array($t->meta) ? $t->meta : [];

        // Buyer order payment via online gateway (ledger only — wallet balance unchanged)
        if ($t->type === 'order_payment' && $amount < 0 && ! empty($meta['gateway'])) {
            return $this->formatPaymentMethodLabel((string) $meta['gateway']);
        }

        // Money left the wallet (fee, order, package, payout request)
        if ($amount < 0) {
            return 'Wallet';
        }

        // Money entered via payment gateway deposit
        if ($t->reference_type === 'wallet_deposit' && $t->reference_id) {
            $deposit = $depositsById->get((int) $t->reference_id);
            if ($deposit && $deposit->gateway) {
                return $this->formatPaymentMethodLabel((string) $deposit->gateway);
            }
            if (stripos((string) $t->description, 'jazzcash') !== false) {
                return 'JazzCash';
            }
            if (stripos((string) $t->description, 'easypaisa') !== false) {
                return 'Easypaisa';
            }
            if (stripos((string) $t->description, 'stripe') !== false) {
                return 'Stripe';
            }
        }

        return '—';
    }

    private function formatPaymentMethodLabel(string $method): string
    {
        $key = strtolower(trim($method));
        return match ($key) {
            'jazzcash', 'jazzcash_partial' => 'JazzCash',
            'easypaisa' => 'Easypaisa',
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'wallet' => 'Wallet',
            'cod' => 'COD',
            '' => '—',
            default => ucwords(str_replace('_', ' ', $key)),
        };
    }
}
