<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldLivePrice;
use Modules\GoldSavers\app\Features\LivePrices\Services\GoldRealtimeBroadcaster;

/**
 * Fired after a validated price update is persisted.
 * Broadcasts to tenant-scoped private channels.
 */
class GoldPriceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int           $tenantId,
        public readonly GoldLivePrice $livePrice,
    ) {}

    public function broadcastOn(): array
    {
        return GoldRealtimeBroadcaster::channelsFor(
            $this->tenantId,
            $this->livePrice->market_key
        );
    }

    public function broadcastAs(): string
    {
        return 'gold.price.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'market_key'     => $this->livePrice->market_key,
            'price_24k'      => $this->livePrice->price_gram_24k,
            'price_21k'      => $this->livePrice->price_gram_21k,
            'price_18k'      => $this->livePrice->price_gram_18k,
            'price_14k'      => $this->livePrice->price_gram_14k,
            'buy_price'      => $this->livePrice->buy_price,
            'sell_price'     => $this->livePrice->sell_price,
            'spread'         => $this->livePrice->spread,
            'price_delta'    => $this->livePrice->price_delta,
            'price_delta_pct' => $this->livePrice->price_delta_pct,
            'direction'      => $this->livePrice->direction,
            'currency'       => $this->livePrice->currency,
            'fetched_at'     => $this->livePrice->fetched_at?->toISOString(),
        ];
    }
}
