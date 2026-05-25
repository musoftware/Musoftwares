<?php

namespace Modules\SmsPaymentGateway\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SmsPaymentGatewayTransaction extends Model
{
    use HasFactory;

    protected $table = 'sms_payment_gateway_transactions';
    protected $fillable = [
        'tenant_id',
        'device_id',
        'user_id',
        'amount',
        'balance',
        'currency',
        'sender',
        'phone_number',
        'reference_number',
        'sender_name',
        'transaction_date',
        'sms_message',
        'message_id',
        'sms_timestamp',
        'status',
        'is_spoofed',
        'spoofing_reason',
        'metadata',
        'is_test',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'transaction_date' => 'datetime',
        'is_spoofed' => 'boolean',
        'is_test' => 'boolean',
        'metadata' => 'array',
    ];

    protected $appends = [
        'reference_number',
    ];

    /**
     * Get the device that received this transaction
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(SmsPaymentGatewayDevice::class, 'device_id');
    }

    /**
     * Get the user associated with this transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment order associated with this transaction
     */
    public function paymentOrder(): HasOne
    {
        return $this->hasOne(PaymentOrder::class, 'transaction_id');
    }

    /**
     * Get reference number from attribute or metadata fallback
     */
    public function getReferenceNumberAttribute($value)
    {
        if ($value) {
            return $value;
        }

        return $this->metadata['reference_number'] ?? null;
    }
}
