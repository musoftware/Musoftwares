<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralEarning extends Model
{
    protected $fillable = [
        'referrer_id', 'referred_user_id', 'reference_type', 'reference_id',
        'level', 'amount', 'currency'
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'referrer_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'referred_user_id');
    }
}
