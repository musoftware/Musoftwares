<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringEntry extends TenantModel
{
    protected $table = 'erp_recurring_entries';

    protected $fillable = [
        'tenant_id', 'type', 'title', 'description',
        'amount', 'amount_currency', 'business_amount', 'business_currency',
        'exchange_rate', 'exchange_rate_date', 'frequency', 'frequency_day', 'frequency_month',
        'starts_at', 'ends_at', 'next_run_at', 'last_run_at', 'next_date', 'end_date',
        'status', 'is_active', 'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'business_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'exchange_rate_date' => 'date',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'next_run_at' => 'date',
        'last_run_at' => 'date',
        'next_date' => 'date',
        'end_date' => 'date',
        'frequency_day' => 'integer',
        'frequency_month' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function executionLogs(): HasMany
    {
        return $this->hasMany(RecurringExecutionLog::class);
    }
}
