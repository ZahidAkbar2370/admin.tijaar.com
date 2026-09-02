<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletDeposit extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'gateway',
        'status',
        'gateway_reference',
        'gateway_response',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markCompleted(?string $gatewayRef = null): void
    {
        $this->update([
            'status' => 'completed',
            'gateway_reference' => $gatewayRef ?? $this->gateway_reference,
            'paid_at' => now(),
        ]);
    }

    public function markFailed(?array $extra = null): void
    {
        if ($this->status === 'completed') {
            return;
        }

        $payload = [
            'status' => 'failed',
        ];
        if ($extra !== null) {
            $payload['gateway_response'] = array_merge(
                is_array($this->gateway_response) ? $this->gateway_response : [],
                $extra
            );
        }
        $this->update($payload);
    }

    /** Map deposit DB status to user-facing payment status. */
    public function paymentStatusLabel(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'pending' => 'pending',
            'failed' => 'failed',
            default => (string) $this->status,
        };
    }
}
