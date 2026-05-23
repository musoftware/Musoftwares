<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Voucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'spend_amount',
        'spend_currency',
        'reward_amount',
        'reward_currency',
        'type',
        'reward_percentage',
        'max_uses_per_user',
        'max_total_uses',
        'current_uses',
        'starts_at',
        'expires_at',
        'is_active',
        'admin_notes',
    ];

    protected $casts = [
        'spend_amount' => 'decimal:10',
        'reward_amount' => 'decimal:10',
        'reward_percentage' => 'decimal:2',
        'max_uses_per_user' => 'integer',
        'max_total_uses' => 'integer',
        'current_uses' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function spendCurrency()
    {
        return $this->belongsTo(Currency::class, 'spend_currency');
    }

    public function rewardCurrency()
    {
        return $this->belongsTo(Currency::class, 'reward_currency');
    }

    public function redemptions()
    {
        return $this->hasMany(VoucherRedemption::class);
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

    public function canBeUsedByUser(User $user): bool
    {
        if (!$this->isActive()) {
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

    public function calculateReward($spentAmount): float
    {
        if ($this->type === 'percentage' && $this->reward_percentage) {
            return $spentAmount * ($this->reward_percentage / 100);
        }

        return (float) $this->reward_amount;
    }
}

