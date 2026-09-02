<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    protected $fillable = [
        'action_by',
        'target_table',
        'action_type',
        'action_on',
        'description',
        'device',
        'ip_address',
        'location',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by');
    }

    /** Common action types for filters / docs. */
    public static function actionTypes(): array
    {
        return [
            'create',
            'update',
            'delete',
            'login',
            'logout',
            'register',
            'verify_email',
            'place_order',
            'cancel_order',
            'payment_success',
            'payment_failed',
            'approve',
            'reject',
            'suspend',
            'ban',
            'settings_update',
            'other',
        ];
    }
}
