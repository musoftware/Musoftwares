<?php

namespace Modules\Booking\app\Features\BookingRules\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingAdvancedRule extends Model
{
    use SoftDeletes;

    protected $table = 'booking_advanced_rules';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'event_trigger',
        'priority',
        'is_active',
        'valid_from',
        'valid_until',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function conditions(): HasMany
    {
        return $this->hasMany(BookingAdvancedRuleCondition::class, 'rule_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(BookingAdvancedRuleAction::class, 'rule_id')->orderBy('order');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(BookingAdvancedRuleExecution::class, 'rule_id');
    }
}
