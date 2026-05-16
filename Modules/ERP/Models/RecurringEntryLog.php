<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringEntryLog extends Model
{
    protected $fillable = [
        'recurring_entry_id',
        'amount',
        'status',
    ];

    public function recurringEntry(): BelongsTo
    {
        return $this->belongsTo(RecurringEntry::class);
    }
}
