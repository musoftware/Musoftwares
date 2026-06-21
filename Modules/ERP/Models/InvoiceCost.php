<?php

namespace Modules\ERP\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceCost extends TenantModel
{
    use SoftDeletes;

    protected $table = 'erp_invoice_costs';

    protected $fillable = [
        'invoice_id', 'tenant_id', 'title', 'description',
        'amount', 'currency_id', 'business_amount', 'business_currency_id',
        'exchange_rate', 'exchange_rate_date', 'payment_status', 'payment_source',
        'paid_at', 'paid_by', 'note'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'business_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'exchange_rate_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'paid_by');
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
