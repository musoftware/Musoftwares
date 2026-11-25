<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One month of a client system: the monthly fee (paid ahead) plus the share of sales from before it. */
class ClientSystemBill extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_system_id', 'invoice_id', 'period_start', 'period_end', 'monthly_fee',
        'sales_total', 'sales_percent', 'sales_fee', 'due_at', 'lock_at',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'due_at' => 'datetime',
        'lock_at' => 'datetime',
        'monthly_fee' => 'decimal:2',
        'sales_total' => 'decimal:2',
        'sales_percent' => 'decimal:2',
        'sales_fee' => 'decimal:2',
    ];

    /** Invoice states that still block the system. Cancelled and paid invoices do not. */
    public const OPEN_INVOICE_STATUSES = ['unpaid', 'partially_paid'];

    public function system(): BelongsTo
    {
        return $this->belongsTo(ClientSystem::class, 'client_system_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(ClientSystemSale::class);
    }
}
