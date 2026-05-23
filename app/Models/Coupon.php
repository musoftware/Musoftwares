<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'discount_amount',
        'discount_percentage',
        'currency',
        'min_purchase_amount',
        'max_uses_per_user',
        'max_total_uses',
        'current_uses',
        'starts_at',
        'expires_at',
        'is_active',
        'admin_notes',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:10',
        'discount_percentage' => 'decimal:2',
        'min_purchase_amount' => 'decimal:10',
        'max_uses_per_user' => 'integer',
        'max_total_uses' => 'integer',
        'current_uses' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function currencyRelation()
    {
        return $this->belongsTo(Currency::class, 'currency');
    }

    public function redemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    // Helper methods
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        if ($this->max_total_uses && $this->current_uses >= $this->max_total_uses) {
            return false;
        }

        return true;
    }

    public function canBeUsedByUser(User $user, $purchaseAmount = null): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->min_purchase_amount && $purchaseAmount && $purchaseAmount < $this->min_purchase_amount) {
            return false;
        }

        if ($this->max_uses_per_user) {
            $userRedemptions = $this->redemptions()
                ->where('user_id', $user->id)
                ->count();

            if ($userRedemptions >= $this->max_uses_per_user) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscount($purchaseAmount): float
    {
        if ($this->type === 'percentage' && $this->discount_percentage) {
            $discount = $purchaseAmount * ($this->discount_percentage / 100);
            return min($discount, $purchaseAmount); // Don't exceed purchase amount
        }

        return min((float) $this->discount_amount, $purchaseAmount); // Don't exceed purchase amount
    }

    public static function findByCode(string $code): ?self
    {
        return self::where('code', strtoupper($code))->first();
    }
}

