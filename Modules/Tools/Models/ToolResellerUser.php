<?php

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class ToolResellerUser extends Model
{
    protected $fillable = [
        'reseller_id', 'user_id', 'status', 'joined_at',
        'sharing_check_enabled', 'is_sharing_flagged',
        'flagged_ips', 'sharing_flagged_at',
    ];

    protected $casts = [
        'joined_at'             => 'datetime',
        'sharing_flagged_at'    => 'datetime',
        'sharing_check_enabled' => 'boolean',
        'is_sharing_flagged'    => 'boolean',
        'flagged_ips'           => 'array',
    ];

    /**
     * The window in minutes within which we consider two sessions "concurrent".
     * Dynamic IPs reconnect within seconds, so 5 minutes is safely above that
     * while catching real concurrent sharing.
     */
    const CONCURRENT_WINDOW_MINUTES = 5;

    // status values: active | suspended | suspended_by_reseller | sharing_flagged
    public static array $statuses = ['active', 'suspended', 'suspended_by_reseller', 'sharing_flagged'];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(ToolReseller::class, 'reseller_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ResellerUserSession::class, 'reseller_user_id');
    }

    // ─── Status Helpers ───────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSharingFlagged(): bool
    {
        return $this->status === 'sharing_flagged' || $this->is_sharing_flagged;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSuspended($query)
    {
        return $query->whereIn('status', ['suspended', 'suspended_by_reseller']);
    }

    public function scopeSharingFlagged($query)
    {
        return $query->where('is_sharing_flagged', true);
    }

    // ─── Concurrent Session / Anti-Sharing Detection ─────────────────────────

    /**
     * Record a heartbeat for the current IP and check for concurrent sessions.
     *
     * How it works:
     *  1. Upsert a session row for (this user, current IP) with last_seen_at = now().
     *  2. Count DISTINCT IPs that have been active within CONCURRENT_WINDOW_MINUTES.
     *  3. If > 1 distinct IPs are concurrently active → sharing detected → flag.
     *
     * Dynamic IPs are safe because: by the time the user reconnects with a new
     * IP, their old session heartbeat has expired (> 5 min ago), so only ONE
     * IP is ever "active" for a legitimate single user.
     *
     * @return bool  true = access allowed, false = sharing detected & blocked
     */
    public function recordHeartbeatAndCheck(string $ip, string $userAgent = ''): bool
    {
        // Already flagged — keep blocking
        if ($this->isSharingFlagged()) {
            return false;
        }

        // Check disabled for this user — always allow
        if (!$this->sharing_check_enabled) {
            $this->touchSession($ip, $userAgent);
            return true;
        }

        // Upsert the current session heartbeat
        $this->touchSession($ip, $userAgent);

        // Count distinct IPs active within the concurrent window
        $cutoff = now()->subMinutes(self::CONCURRENT_WINDOW_MINUTES);

        $activeSessions = ResellerUserSession::where('reseller_user_id', $this->id)
            ->where('last_seen_at', '>=', $cutoff)
            ->get(['ip_address', 'last_seen_at']);

        $distinctIps = $activeSessions->pluck('ip_address')->unique()->values();

        if ($distinctIps->count() > 1) {
            // 🚨 Sharing detected — two or more IPs active simultaneously
            $this->flagAsSharing($distinctIps->all());
            return false;
        }

        return true;
    }

    /**
     * Upsert a session heartbeat row for this (user, IP) pair.
     */
    private function touchSession(string $ip, string $userAgent): void
    {
        ResellerUserSession::updateOrCreate(
            [
                'reseller_user_id' => $this->id,
                'ip_address'       => $ip,
            ],
            [
                'user_agent'  => substr($userAgent, 0, 512),
                'last_seen_at' => now(),
            ]
        );
    }

    /**
     * Flag this sub-user as a sharing violator.
     */
    public function flagAsSharing(array $concurrentIps): void
    {
        $this->update([
            'status'              => 'sharing_flagged',
            'is_sharing_flagged'  => true,
            'flagged_ips'         => $concurrentIps,
            'sharing_flagged_at'  => now(),
        ]);
    }

    /**
     * Admin clears the sharing flag and resets all sessions.
     */
    public function clearSharingFlag(): void
    {
        // Wipe all old sessions so the user starts fresh
        $this->sessions()->delete();

        $this->update([
            'status'             => 'active',
            'is_sharing_flagged' => false,
            'flagged_ips'        => null,
            'sharing_flagged_at' => null,
        ]);
    }

    /**
     * Admin resets sessions only (e.g., device change) without changing status.
     */
    public function resetSessions(): void
    {
        $this->sessions()->delete();
    }
}
