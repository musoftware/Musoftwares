<?php

namespace Modules\Booking\app\Features\BookingRules\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAdvancedRuleAction extends Model
{
    protected $table = 'booking_advanced_rule_actions';

    protected $fillable = [
        'tenant_id',
        'rule_id',
        'type',
        'parameters',
        'order',
    ];

    protected $casts = [
        'parameters' => 'array',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(BookingAdvancedRule::class, 'rule_id');
    }
}
