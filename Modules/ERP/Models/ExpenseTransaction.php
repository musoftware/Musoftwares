<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseTransaction extends TenantModel
{
    use SoftDeletes;

    protected $table = 'erp_expense_transactions';

    protected $fillable = [
        'tenant_id', 'invoice_cost_id', 'invoice_id', 'client_id',
        'amount', 'currency_id', 'business_amount', 'business_currency_id',
        'exchange_rate', 'exchange_rate_date', 'balance_before', 'balance_after',
        'note', 'created_by'
    ];

    const UPDATED_AT = null;

    protected $casts = [
        'amount' => 'decimal:2',
        'business_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'exchange_rate_date' => 'date',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoiceCost(): BelongsTo
    {
        return $this->belongsTo(InvoiceCost::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'client_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
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
