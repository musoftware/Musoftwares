<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyRule extends Model
{
    protected $fillable = [
        'event_type',
        'base_points',
        'conditions_payload',
        'is_active',
    ];

    protected $casts = [
        'base_points'         => 'integer',
        'conditions_payload'  => 'array',
        'is_active'           => 'boolean',
    ];

    /**
     * Fetch the active rule for a given event type. Returns null if none found or inactive.
     */
    public static function forEvent(string $eventType): ?self
    {
        return self::where('event_type', $eventType)
            ->where('is_active', true)
            ->first();
    }
}
