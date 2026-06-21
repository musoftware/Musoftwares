<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TenantUsage extends Model
{
    use SoftDeletes;

    protected $table = 'tenant_usages';

    protected $fillable = [
        'tenant_id',
        'usage_key',
        'used_amount',
        'limit_amount',
        'reset_frequency',
        'last_reset_at',
    ];

    protected $casts = [
        'used_amount' => 'integer',
        'limit_amount' => 'integer',
        'last_reset_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function isUnlimited(): bool
    {
        return is_null($this->limit_amount);
    }

    public function hasAvailable(int $amount = 1): bool
    {
        if ($this->isUnlimited()) {
            return true;
        }

        return ($this->used_amount + $amount) <= $this->limit_amount;
    }

    public function getRemaining(): ?int
    {
        if ($this->isUnlimited()) {
            return null;
        }

        return max(0, $this->limit_amount - $this->used_amount);
    }

    public function getPercentageUsed(): float
    {
        if ($this->isUnlimited() || $this->limit_amount === 0) {
            return 0;
        }

        return ($this->used_amount / $this->limit_amount) * 100;
    }

    public function needsReset(): bool
    {
        if ($this->reset_frequency === 'never') {
            return false;
        }

        $now = Carbon::now();
        $lastReset = $this->last_reset_at;

        switch ($this->reset_frequency) {
            case 'daily':
                return $now->diffInDays($lastReset) >= 1 || $now->day !== $lastReset->day;
            case 'monthly':
                return $now->diffInMonths($lastReset) >= 1 || $now->month !== $lastReset->month;
            case 'yearly':
                return $now->diffInYears($lastReset) >= 1 || $now->year !== $lastReset->year;
        }

        return false;
    }
}
