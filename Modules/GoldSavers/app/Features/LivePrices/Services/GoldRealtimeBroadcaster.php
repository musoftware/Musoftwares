<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Services;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast;
use Modules\GoldSavers\app\Features\LivePrices\Events\GoldPriceUpdated;
use Modules\GoldSavers\app\Features\LivePrices\Events\GoldRealtimeBroadcasted;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldLivePrice;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldPriceEvent;

/**
 * Wraps Laravel broadcasting with tenant-scoped private channels.
 * Protects against cross-tenant leakage via PrivateChannel authorization.
 */
class GoldRealtimeBroadcaster
{
    /**
     * Broadcast a price update to all relevant tenant channels.
     */
    public function broadcast(int $tenantId, GoldLivePrice $livePrice): void
    {
        event(new GoldPriceUpdated($tenantId, $livePrice));

        // Mark the live price as broadcasted
        $livePrice->update(['broadcasted_at' => now()]);

        // Audit log
        GoldPriceEvent::logEvent($tenantId, 'broadcast_sent', [
            'market_key' => $livePrice->market_key,
            'price'      => $livePrice->price_gram_24k,
        ]);

        event(new GoldRealtimeBroadcasted($tenantId, $livePrice));
    }

    /**
     * Get all broadcast channels for a tenant.
     * Used by GoldPriceUpdated::broadcastOn().
     */
    public static function channelsFor(int $tenantId, string $marketKey): array
    {
        return [
            new PrivateChannel("gold-prices.tenant.{$tenantId}"),
            new PrivateChannel("gold-prices.tenant.{$tenantId}.market.{$marketKey}"),
        ];
    }

    /**
     * Add a watchlist-specific channel for targeted subscriptions.
     */
    public static function watchlistChannel(int $tenantId, int $watchlistId): PrivateChannel
    {
        return new PrivateChannel("gold-prices.tenant.{$tenantId}.watchlist.{$watchlistId}");
    }
}
