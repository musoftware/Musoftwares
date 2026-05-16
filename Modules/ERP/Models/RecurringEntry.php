<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringEntry extends TenantAwareModel
{
    protected $fillable = [
        'tenant_id',
        'type',
        'description',
        'amount',
        'currency_code',
        'frequency',
        'day_of_week',
        'day_of_month',
        'month_of_year',
        'next_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'next_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(RecurringEntryLog::class);
    }
}
