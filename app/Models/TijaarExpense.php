<?php

namespace App\Models;

use App\Support\UploadHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TijaarExpense extends Model
{
    protected $fillable = [
        'title',
        'category',
        'amount',
        'expense_date',
        'proof_image',
        'description',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public const CATEGORIES = [
        'operations' => 'Operations',
        'marketing' => 'Marketing',
        'technology' => 'Technology & Software',
        'salaries' => 'Salaries & Payroll',
        'office' => 'Office & Utilities',
        'shipping' => 'Courier & Shipping',
        'payment_fees' => 'Payment Gateway Fees',
        'legal' => 'Legal & Compliance',
        'other' => 'Other',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucwords(str_replace('_', ' ', (string) $this->category));
    }

    public function getProofImageUrlAttribute(): ?string
    {
        return UploadHelper::url($this->proof_image);
    }
}
