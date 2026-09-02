<?php

namespace App\Services;

use App\Models\Setting;

class PartialPaymentService
{
    public const MODE_COD = 'cod';
    public const MODE_JAZZCASH_FULL = 'jazzcash';
    public const MODE_JAZZCASH_PARTIAL = 'jazzcash_partial';

    public static function isPartialEnabled(): bool
    {
        return (string) Setting::get('partial_payment_enabled', '1') !== '0';
    }

    public static function onlinePercent(): int
    {
        $pct = (int) Setting::get('partial_payment_online_percent', 50);
        return max(1, min(99, $pct));
    }

    /**
     * Grand total payable (order price + shipping + buyer fees).
     */
    public static function payableTotal(
        float $subtotal,
        float $shipping,
        float $discount = 0,
        float $marketplaceFee = 0,
        float $onlineTransactionFee = 0
    ): float {
        $orderPrice = max(0, $subtotal - $discount);
        return max(0, round($orderPrice + $shipping + $marketplaceFee + $onlineTransactionFee, 2));
    }

    /**
     * @return array{online_amount: float, cod_amount: float, partial_payment_percent: ?int, mode: string}
     */
    public static function split(
        string $paymentMethod,
        float $subtotal,
        float $shipping,
        float $discount = 0,
        float $marketplaceFee = 0,
        float $onlineTransactionFee = 0
    ): array {
        $payable = self::payableTotal($subtotal, $shipping, $discount, $marketplaceFee, $onlineTransactionFee);
        $method = strtolower(trim($paymentMethod));

        if ($method === self::MODE_COD) {
            return [
                'online_amount' => 0.0,
                'cod_amount' => $payable,
                'partial_payment_percent' => null,
                'mode' => self::MODE_COD,
            ];
        }

        if ($method === self::MODE_JAZZCASH_FULL || $method === 'jazzcash') {
            return [
                'online_amount' => $payable,
                'cod_amount' => 0.0,
                'partial_payment_percent' => null,
                'mode' => self::MODE_JAZZCASH_FULL,
            ];
        }

        if ($method === self::MODE_JAZZCASH_PARTIAL) {
            $pct = self::onlinePercent();
            $online = round($payable * $pct / 100, 2);
            $cod = round($payable - $online, 2);

            return [
                'online_amount' => $online,
                'cod_amount' => $cod,
                'partial_payment_percent' => $pct,
                'mode' => self::MODE_JAZZCASH_PARTIAL,
            ];
        }

        return [
            'online_amount' => $payable,
            'cod_amount' => 0.0,
            'partial_payment_percent' => null,
            'mode' => $method,
        ];
    }

    public static function preview(float $subtotal, float $shipping, float $discount = 0, ?string $paymentMethod = null): array
    {
        $feesCod = MarketplaceFeeService::customerTotal($subtotal, $shipping, $discount, 'cod');
        $feesOnline = MarketplaceFeeService::customerTotal($subtotal, $shipping, $discount, $paymentMethod ?: 'jazzcash');

        $partial = self::split(
            self::MODE_JAZZCASH_PARTIAL,
            $subtotal,
            $shipping,
            $discount,
            $feesOnline['marketplace_fee'],
            $feesOnline['online_transaction_fee']
        );
        $fullJazz = self::split(
            self::MODE_JAZZCASH_FULL,
            $subtotal,
            $shipping,
            $discount,
            $feesOnline['marketplace_fee'],
            $feesOnline['online_transaction_fee']
        );
        $cod = self::split(
            self::MODE_COD,
            $subtotal,
            $shipping,
            $discount,
            $feesCod['marketplace_fee'],
            0
        );

        $pct = self::onlinePercent();

        return [
            'payable_total' => $feesOnline['total'],
            'payable_total_cod' => $feesCod['total'],
            'fees' => [
                'order_price' => $feesOnline['order_price'],
                'marketplace_fee' => $feesOnline['marketplace_fee'],
                'marketplace_fee_type' => $feesOnline['marketplace_fee_type'],
                'marketplace_fee_value' => $feesOnline['marketplace_fee_value'],
                'online_transaction_fee' => $feesOnline['online_transaction_fee'],
                'online_transaction_fee_type' => $feesOnline['online_transaction_fee_type'],
                'online_transaction_fee_value' => $feesOnline['online_transaction_fee_value'],
                'online_transaction_fee_cod' => 0,
                'config' => MarketplaceFeeService::publicConfig(),
            ],
            'partial_payment_enabled' => self::isPartialEnabled(),
            'partial_payment_online_percent' => $pct,
            'partial_payment_label' => sprintf('Pay %d%% online, %d%% on delivery', $pct, 100 - $pct),
            'options' => array_values(array_filter([
                [
                    'value' => self::MODE_JAZZCASH_FULL,
                    'label' => 'Jazzcash / Mobicash Account',
                    'online_amount' => $fullJazz['online_amount'],
                    'cod_amount' => $fullJazz['cod_amount'],
                ],
                self::isPartialEnabled() ? [
                    'value' => self::MODE_JAZZCASH_PARTIAL,
                    'label' => sprintf('Pay %d%% online, %d%% on delivery', $pct, 100 - $pct),
                    'online_amount' => $partial['online_amount'],
                    'cod_amount' => $partial['cod_amount'],
                ] : null,
                [
                    'value' => self::MODE_COD,
                    'label' => 'Cash on Delivery (100%)',
                    'online_amount' => $cod['online_amount'],
                    'cod_amount' => $cod['cod_amount'],
                ],
            ])),
        ];
    }
}
