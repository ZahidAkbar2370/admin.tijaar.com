<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Seller extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'status',
        'kyc_status',
        'kyc_document_path',
        'kyc_document_type',
        'kyc_id_number',
        'kyc_id_front_path',
        'kyc_id_back_path',
        'tax_id',
        'bank_account_holder',
        'bank_account_number',
        'bank_name',
        'bank_swift_code',
        'vacation_mode',
        'vacation_mode_until',
        'approved_at',
        'approved_by',
        'rejection_reason',
    ];

    protected $casts = [
        'vacation_mode' => 'boolean',
        'vacation_mode_until' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): HasOne
    {
        return $this->hasOne(Store::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
