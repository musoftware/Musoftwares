<?php

namespace Modules\SmsPaymentGateway\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsPaymentGatewayOrderLink extends Model
{
    use HasFactory;

    protected $table = 'sms_payment_gateway_order_links';
    protected $fillable = [
        'tenant_id',
        'user_id',
        'order_id',
        'phone_number',
        'status',
    ];

    /**
     * Get the user that owns this order link
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
