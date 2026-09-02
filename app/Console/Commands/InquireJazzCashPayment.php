<?php

namespace App\Console\Commands;

use App\Services\JazzCashService;
use Illuminate\Console\Command;

class InquireJazzCashPayment extends Command
{
    protected $signature = 'jazzcash:inquire {txnRef : JazzCash pp_TxnRefNo}';

    protected $description = 'Call JazzCash Status Inquiry API for a txn ref and write clear verification logs';

    public function handle(JazzCashService $jazzCash): int
    {
        $ref = (string) $this->argument('txnRef');
        $this->info("Inquiring JazzCash status for {$ref} ...");
        $body = $jazzCash->inquireStatus($ref);
        if ($body === null) {
            $this->error('Inquiry failed (see laravel.log JAZZCASH VERIFY lines).');

            return self::FAILURE;
        }

        $this->table(
            ['Field', 'Value'],
            [
                ['pp_ResponseCode', (string) ($body['pp_ResponseCode'] ?? '')],
                ['pp_PaymentResponseCode', (string) ($body['pp_PaymentResponseCode'] ?? '')],
                ['pp_Status', (string) ($body['pp_Status'] ?? '')],
                ['pp_ResponseMessage', (string) ($body['pp_ResponseMessage'] ?? '')],
                ['pp_TxnRefNo', (string) ($body['pp_TxnRefNo'] ?? $ref)],
            ]
        );
        $this->info('Logged under JAZZCASH VERIFY | STATUS INQUIRY in storage/logs/laravel.log');

        return self::SUCCESS;
    }
}
