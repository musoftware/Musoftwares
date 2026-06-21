<?php

namespace Modules\SmsPaymentGateway\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsPaymentGatewayWallet extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'sms_payment_gateway_wallets';
    protected $fillable = [
        'tenant_id',
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
