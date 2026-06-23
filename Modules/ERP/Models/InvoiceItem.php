<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceItem extends TenantAwareModel
{
    use SoftDeletes;

    protected $table = 'erp_invoice_items';

    protected $fillable = [
        'invoice_id', 'tenant_id', 'type', 'title', 'description',
        'unit_price', 'quantity', 'total', 'sort_order', 'product_id'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'total' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function timerSessions(): HasMany
    {
        return $this->hasMany(TimerSession::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
