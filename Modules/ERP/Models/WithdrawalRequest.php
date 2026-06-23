<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

use Illuminate\Database\Eloquent\SoftDeletes;

class WithdrawalRequest extends TenantAwareModel
{
    use SoftDeletes;

    use Searchable;
    protected $table = 'erp_withdrawal_requests';

    protected $fillable = [
        'tenant_id', 'client_id', 'payment_method_id', 'status',
        'amount', 'amount_currency', 'business_amount', 'business_currency',
        'exchange_rate', 'exchange_rate_date', 'balance_at_request',
        'reviewed_by', 'reviewed_at', 'rejection_note', 'paid_by', 'paid_at',
        'payment_reference', 'payment_proof', 'admin_note'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'business_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'exchange_rate_date' => 'date',
        'balance_at_request' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'client_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'paid_by');
    }
}
