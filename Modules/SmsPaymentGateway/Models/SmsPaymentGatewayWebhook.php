<?php

namespace Modules\SmsPaymentGateway\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsPaymentGatewayWebhook extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'sms_payment_gateway_webhooks';
    protected $fillable = [
        'tenant_id',
        'user_id',
        'webhook_url',
        'webhook_secret',
        'is_active',
        'success_count',
        'failure_count',
        'last_triggered_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    /**
     * Get the user that owns this webhook
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
