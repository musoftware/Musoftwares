<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ERP\Models\TenantClient;

use Illuminate\Database\Eloquent\SoftDeletes;

class Withdrawal extends TenantModel
{
    use SoftDeletes;

    protected $table = 'erp_withdrawals';

    protected $fillable = [
        'tenant_id', 'client_id', 'payment_method_id', 'amount', 'currency_id',
        'business_amount', 'business_currency_id', 'exchange_rate', 'exchange_rate_date',
        'balance_at_request', 'status', 'rejection_note', 'admin_notes', 'reference', 'proof_path',
        'reviewed_by', 'reviewed_at', 'paid_by', 'paid_at'
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

    public function client(): BelongsTo
    {
        // M8 fix: use TenantClient — not Client (alias with different methods)
        return $this->belongsTo(TenantClient::class, 'client_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    public function businessCurrency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'business_currency_id');
    }
}
