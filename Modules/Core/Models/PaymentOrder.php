<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mobile_number',
        'amount',
        'currency',
        'status',
        'verified_at',
        'transaction_id',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns this order
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transaction that verified this order
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(AutoSmsTransaction::class, 'transaction_id');
    }

    /**
     * Check if order is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if order is verified
     */
    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    /**
     * Check if order is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
