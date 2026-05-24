<?php

namespace Modules\TextPaymentGateway\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TextPaymentGatewayWallet extends Model
{
    use HasFactory;

    protected $table = 'auto_sms_wallets';
protected $fillable = [
        'user_id',
        'payment_type',
        'phone_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns this wallet
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
