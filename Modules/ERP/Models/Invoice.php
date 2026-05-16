<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends TenantAwareModel
{
    protected $fillable = [
        'tenant_id', 'client_id', 'number', 'issue_date', 'due_date',
        'status', 'subtotal', 'tax_amount', 'total', 'currency_code'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
