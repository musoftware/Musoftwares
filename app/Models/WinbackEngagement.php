<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WinbackEngagement extends Model
{
    protected $fillable = [
        'user_id',
        'stage_id',
        'status',
        'idempotency_key',
        'sent_at',
        'recovered_at',
        'recovery_event_type',
        'metadata',
    ];

    protected $casts = [
        'sent_at'       => 'datetime',
        'recovered_at'  => 'datetime',
        'metadata'      => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(WinbackStage::class, 'stage_id');
    }

    /**
     * Check if a specific stage was already sent to a user in the current inactivity cycle.
     */
    public static function alreadySent(int $userId, int $stageId): bool
    {
        return self::where('user_id', $userId)
            ->where('stage_id', $stageId)
            ->whereIn('status', ['sent', 'opened', 'clicked'])
            ->exists();
    }

    /**
     * Mark all open engagements for a user as recovered.
     */
    public static function markRecovered(int $userId, string $recoveryEventType): void
    {
        self::where('user_id', $userId)
            ->whereIn('status', ['sent', 'opened', 'clicked'])
            ->update([
                'status'              => 'recovered',
                'recovered_at'        => now(),
                'recovery_event_type' => $recoveryEventType,
            ]);
    }
}
