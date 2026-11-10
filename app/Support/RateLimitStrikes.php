<?php

namespace App\Support;

use App\Models\BlockedIp;
use Illuminate\Support\Facades\Cache;

/**
 * Counts how often one address runs into a rate limit. More than STRIKES refusals in an hour blocks the address
 * for a day, not forever: on mobile data many customers share one address, and one noisy visitor must not shut
 * them all out for good.
 */
final class RateLimitStrikes
{
    private const STRIKES = 5;

    private const WINDOW_SECONDS = 3600;

    private const BLOCK_HOURS = 24;

    public static function record(string $ip): void
    {
        $key = "throttle_strikes:{$ip}";
        Cache::add($key, 0, self::WINDOW_SECONDS);
        if (Cache::increment($key) <= self::STRIKES) {
            return;
        }

        BlockedIp::firstOrCreate(
            ['ip_address' => $ip],
            ['reason' => 'Persistent rate limiting (Spam)', 'blocked_until' => now()->addHours(self::BLOCK_HOURS)]
        );
        Cache::forget("blocked_ip:{$ip}");
    }
}
