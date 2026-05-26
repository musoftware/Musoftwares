<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldLivePrice;

/**
 * Fired after a successful WebSocket broadcast.
 * Used for analytics tracking (broadcast latency, frequency, etc.).
 */
class GoldRealtimeBroadcasted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int           $tenantId,
        public readonly GoldLivePrice $livePrice,
    ) {}
}
