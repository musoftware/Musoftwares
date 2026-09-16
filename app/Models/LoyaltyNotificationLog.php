<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyNotificationLog extends Model
{
    protected $fillable = [
        'user_id',
        'notification_type',
        'deduplication_fingerprint',
        'status',
        'sent_at',
        'metadata',
    ];

    protected $casts = [
        'sent_at'  => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a notification with this fingerprint was already sent.
     */
    public static function alreadySent(string $fingerprint): bool
    {
        return self::where('deduplication_fingerprint', $fingerprint)->exists();
    }
}
