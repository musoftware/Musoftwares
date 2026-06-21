<?php

namespace Modules\Booking\app\Features\BookingRules\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAdvancedRuleLog extends Model
{
    use SoftDeletes;

    protected $table = 'booking_advanced_rule_logs';

    protected $fillable = [
        'tenant_id',
        'execution_id',
        'level',
        'message',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function execution(): BelongsTo
    {
        return $this->belongsTo(BookingAdvancedRuleExecution::class, 'execution_id');
    }
}
