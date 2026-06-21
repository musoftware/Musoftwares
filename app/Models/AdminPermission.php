<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminPermission extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'user_id',
        'can_access_trading',
        'can_access_backtest',
        'is_admin',
    ];

    protected $casts = [
        'can_access_trading' => 'boolean',
        'can_access_backtest' => 'boolean',
        'is_admin' => 'boolean',
    ];

    /**
     * Get the user that owns the permission.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user can access trading features.
     */
    public function canTrade(): bool
    {
        return $this->can_access_trading || $this->is_admin;
    }

    /**
     * Check if user can access backtesting features.
     */
    public function canBacktest(): bool
    {
        return $this->can_access_backtest || $this->is_admin;
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }
}
