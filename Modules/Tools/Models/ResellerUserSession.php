<?php

namespace Modules\Tools\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerUserSession extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reseller_user_id', 'ip_address', 'user_agent', 'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function resellerUser(): BelongsTo
    {
        return $this->belongsTo(ToolResellerUser::class, 'reseller_user_id');
    }

    /**
     * Prune sessions older than the concurrent window (run periodically via scheduler).
     */
    public static function pruneStale(int $olderThanMinutes = 30): int
    {
        return static::where('last_seen_at', '<', now()->subMinutes($olderThanMinutes))->delete();
    }
}
