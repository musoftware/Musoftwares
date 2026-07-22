<?php

namespace Modules\Marketplace\Helpers;

use Carbon\Carbon;
use App\Models\ServiceOrder;
use Modules\Marketplace\Models\Service;

class MarketplaceHelper
{
    /**
     * Calculate average response time for a seller in hours.
     */
    public static function calculateAverageResponseTime(int $sellerId): int
    {
        // Default to 1 hour fallback or calculate from workspace chat activity
        return 1;
    }

    /**
     * Generate a unique serial key.
     */
    public static function generateSerialKey(string $prefix = 'MS'): string
    {
        return sprintf('%s-%s-%s-%s', $prefix, strtoupper(\Illuminate\Support\Str::random(4)), strtoupper(\Illuminate\Support\Str::random(4)), strtoupper(\Illuminate\Support\Str::random(4)));
    }

    /**
     * Check if a Hijri promotional window (e.g., Ramadan, Eid) is currently active.
     */
    public static function isHijriPromoActive(string $season = 'ramadan'): bool
    {
        $now = Carbon::now('Africa/Cairo');
        // Simple logic for active seasonal promo flags or date window check
        if ($season === 'ramadan') {
            return $now->month === 3 || $now->month === 4;
        }
        return false;
    }

    /**
     * Format currency amount for display.
     */
    public static function formatPrice(float $amount, string $currencySymbol = '$'): string
    {
        return $currencySymbol . number_format($amount, 2);
    }
}
