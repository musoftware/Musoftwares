<?php

namespace Modules\PaymentGateway\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GatewayPayment extends Model
{
    use HasFactory;

    protected $table = 'gateway_payments';

    protected $fillable = [
        'client_id',
        'internal_order_id',
        'external_order_id',
        'amount',
        'currency',
        'commission_rate',
        'commission_amount',
        'net_amount',
        'description',
        'customer_name',
        'customer_email',
        'customer_phone',
        'success_url',
        'failure_url',
        'webhook_url',
        'metadata',
        'status',
        'kashier_payment_url',
        'kashier_transaction_id',
        'webhook_sent_at',
        'webhook_response',
    ];

    protected $casts = [
        'amount'            => 'decimal:2',
        'commission_rate'   => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount'        => 'decimal:2',
        'metadata'          => 'array',
        'webhook_sent_at'   => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GatewayClient::class, 'client_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }


    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'success'   => ['label' => 'Success',   'color' => 'green'],
            'failed'    => ['label' => 'Failed',    'color' => 'red'],
            'cancelled' => ['label' => 'Cancelled', 'color' => 'gray'],
            default     => ['label' => 'Pending',   'color' => 'yellow'],
        };
    }
}
