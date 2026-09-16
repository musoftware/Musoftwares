<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WinbackStage extends Model
{
    protected $fillable = [
        'stage_slug',
        'days_inactive',
        'channel',
        'incentive_rule_id',
        'is_active',
        'order_index',
    ];

    protected $casts = [
        'days_inactive' => 'integer',
        'is_active'     => 'boolean',
        'order_index'   => 'integer',
    ];

    public function incentiveRule(): BelongsTo
    {
        return $this->belongsTo(LoyaltyRule::class, 'incentive_rule_id');
    }

    public function engagements(): HasMany
    {
        return $this->hasMany(WinbackEngagement::class, 'stage_id');
    }

    /**
     * Return all active stages ordered by inactivity days ascending.
     */
    public static function activeOrdered()
    {
        return self::where('is_active', true)->orderBy('days_inactive')->get();
    }
}
