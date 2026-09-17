<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyTier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'min_lifetime_points',
        'discount_percentage',
        'ticket_priority_level',
        'badge_color',
        'perks_payload',
        'order_index',
        'is_active',
    ];

    protected $casts = [
        'min_lifetime_points' => 'integer',
        'discount_percentage' => 'decimal:2',
        'order_index'         => 'integer',
        'is_active'           => 'boolean',
        'perks_payload'       => 'array',
    ];

    protected $appends = [
        'badge_image',
        'badge_svg',
    ];

    public function getBadgeImageAttribute(): string
    {
        $slug = strtolower($this->slug ?: $this->name);
        return "/images/tiers/{$slug}.png";
    }

    public function getBadgeSvgAttribute(): string
    {
        $slug = strtolower($this->slug ?: $this->name);
        return "/images/tiers/{$slug}.svg";
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'loyalty_tier_id');
    }

    /**
     * Find the tier a user qualifies for given their lifetime points.
     */
    public static function resolveForPoints(int $points): ?self
    {
        return self::where('is_active', true)
            ->where('min_lifetime_points', '<=', $points)
            ->orderByDesc('min_lifetime_points')
            ->first();
    }

    /**
     * Find the next tier above the given one.
     */
    public static function nextAfter(self $tier): ?self
    {
        return self::where('is_active', true)
            ->where('min_lifetime_points', '>', $tier->min_lifetime_points)
            ->orderBy('min_lifetime_points')
            ->first();
    }
}
