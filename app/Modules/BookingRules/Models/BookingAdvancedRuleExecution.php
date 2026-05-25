<?php

namespace App\Modules\BookingRules\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingAdvancedRuleExecution extends Model
{
    protected $table = 'booking_advanced_rule_executions';

    protected $fillable = [
        'tenant_id',
        'rule_id',
        'booking_id',
        'status',
        'execution_time_ms',
        'is_dry_run',
    ];

    protected $casts = [
        'is_dry_run' => 'boolean',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(BookingAdvancedRule::class, 'rule_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BookingAdvancedRuleLog::class, 'execution_id');
    }
}
