<?php

namespace Modules\Booking\app\Features\BookingRules\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAdvancedRuleCondition extends Model
{
    use SoftDeletes;

    protected $table = 'booking_advanced_rule_conditions';

    protected $fillable = [
        'tenant_id',
        'rule_id',
        'group_id',
        'type',
        'operator',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(BookingAdvancedRule::class, 'rule_id');
    }
}
