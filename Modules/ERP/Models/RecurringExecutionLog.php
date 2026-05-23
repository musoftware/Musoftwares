<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringExecutionLog extends Model
{
    protected $table = 'erp_recurring_execution_logs';

    protected $fillable = [
        'recurring_entry_id', 'executed_at', 'amount', 'amount_currency',
        'business_amount', 'business_currency', 'exchange_rate', 'exchange_rate_date',
        'status', 'note'
    ];

    const UPDATED_AT = null;

    protected $casts = [
        'executed_at' => 'datetime',
        'amount' => 'decimal:2',
        'business_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'exchange_rate_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function recurringEntry(): BelongsTo
    {
        return $this->belongsTo(RecurringEntry::class);
    }
}
