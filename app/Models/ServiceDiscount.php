<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceDiscount extends Model
{
    use HasFactory;

    public const TYPE_DATE_RANGE = 'date_range';
    public const TYPE_HIJRI_RANGE = 'hijri_range';
    public const TYPE_DAYS_FROM_NOW = 'days_from_now';
    public const TYPE_NEW_USERS_ONLY = 'new_users_only';

    public const DISCOUNT_PERCENT = 'percent';
    public const DISCOUNT_FIXED = 'fixed';

    protected $fillable = [
        'service_id',
        'name',
        'type',
        'start_date',
        'end_date',
        'hijri_start',
        'hijri_end',
        'days_from_now',
        'new_users_only',
        'discount_type',
        'discount_value',
        'min_price_until',
        'active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'new_users_only' => 'boolean',
        'active' => 'boolean',
        'discount_value' => 'decimal:2',
        'min_price_until' => 'decimal:2',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Whether this discount is currently valid (caller may pass user for new_users_only check and request for days_from_now).
     */
    public function isValidFor(?string $hijriDate = null, bool $isNewUser = false): bool
    {
        if (!$this->active) {
            return false;
        }
        if ($this->type === self::TYPE_NEW_USERS_ONLY) {
            return $this->new_users_only && $isNewUser;
        }
        if ($this->type === self::TYPE_DATE_RANGE && $this->start_date && $this->end_date) {
            $today = now()->startOfDay();
            return $today->between($this->start_date, $this->end_date);
        }
        if ($this->type === self::TYPE_HIJRI_RANGE && $this->hijri_start && $this->hijri_end && $hijriDate !== null) {
            return strcmp($hijriDate, $this->hijri_start) >= 0 && strcmp($hijriDate, $this->hijri_end) <= 0;
        }
        if ($this->type === self::TYPE_DAYS_FROM_NOW && $this->days_from_now !== null) {
            // Valid for X days from creation or from first use - simplified: from creation
            $until = $this->created_at->addDays($this->days_from_now);
            return now()->lte($until);
        }
        return false;
    }

    /**
     * Apply discount to a price (in service currency). Returns the discounted price, not below min_price_until.
     */
    public function applyToPrice(float $price): float
    {
        if ($this->discount_type === self::DISCOUNT_PERCENT) {
            $reduced = $price * (1 - (float) $this->discount_value / 100);
        } else {
            $reduced = $price - (float) $this->discount_value;
        }
        $floor = $this->min_price_until !== null ? (float) $this->min_price_until : 0;
        return max(round($reduced, 2), $floor);
    }
}
