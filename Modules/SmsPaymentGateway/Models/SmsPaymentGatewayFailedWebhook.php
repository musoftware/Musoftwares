<?php

namespace Modules\SmsPaymentGateway\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsPaymentGatewayFailedWebhook extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'sms_payment_gateway_failed_webhooks';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'webhook_id',
        'payload',
        'error_message',
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'failed_at' => 'datetime',
    ];

    /**
     * Get the webhook configuration that this failure belongs to
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(SmsPaymentGatewayWebhook::class, 'webhook_id');
    }

    /**
     * Get the user that owns this failed webhook
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
