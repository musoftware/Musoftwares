<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Invoice extends TenantModel
    use Searchable;
{
    protected $fillable = [
        'tenant_id', 'invoice_number', 'client_id', 'status',
        'amount', 'amount_currency', 'business_amount', 'business_currency',
        'exchange_rate', 'exchange_rate_date', 'discount_amount', 'tax_rate', 'tax_amount',
        'due_date', 'issued_at', 'paid_at', 'notes', 'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'business_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'exchange_rate_date' => 'date',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'due_date' => 'date',
        'issued_at' => 'datetime',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function costs(): HasMany
    {
        return $this->hasMany(InvoiceCost::class);
    }
}
