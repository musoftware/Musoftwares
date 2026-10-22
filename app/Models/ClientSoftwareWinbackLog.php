<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientSoftwareWinbackLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'reminder_number',
        'status',
        'coupon_code',
        'sent_at',
        'recovered_at',
        'recovery_event_type',
        'metadata',
    ];

    protected $casts = [
        'reminder_number' => 'integer',
        'sent_at'         => 'datetime',
        'recovered_at'    => 'datetime',
        'metadata'        => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a specific reminder number was already sent to the user in the active cycle.
     */
    public static function alreadySent(int $userId, int $reminderNumber): bool
    {
        return static::where('user_id', $userId)
            ->where('reminder_number', $reminderNumber)
            ->whereIn('status', ['sent', 'opened', 'clicked'])
            ->exists();
    }

    /**
     * Mark all active sent logs for a user as recovered when they return.
     */
    public static function markRecovered(int $userId, string $recoveryEventType): void
    {
        static::where('user_id', $userId)
            ->whereIn('status', ['sent', 'opened', 'clicked'])
            ->update([
                'status'              => 'recovered',
                'recovered_at'        => now(),
                'recovery_event_type' => $recoveryEventType,
            ]);
    }

    /**
     * Get the latest log entry for the user.
     */
    public static function latestForUser(int $userId): ?self
    {
        return static::where('user_id', $userId)
            ->orderByDesc('reminder_number')
            ->first();
    }
}
