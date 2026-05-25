<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\GoldSavers\app\Features\LivePrices\Providers\GoldPricePayload;

/**
 * Fired when a price spike or anomaly is detected.
 * Price is NOT applied to live_prices. Snapshot is stored with anomaly_detected=true.
 */
class GoldPriceAnomalyDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int              $tenantId,
        public readonly GoldPricePayload $payload,
        public readonly float            $changePct,
        public readonly string           $anomalyType,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("gold-prices.tenant.{$this->tenantId}")];
    }

    public function broadcastAs(): string
    {
        return 'gold.anomaly.detected';
    }

    public function broadcastWith(): array
    {
        return [
            'market_key'   => $this->payload->marketKey,
            'price_24k'    => $this->payload->priceLocalGram24k,
            'change_pct'   => $this->changePct,
            'anomaly_type' => $this->anomalyType,
            'detected_at'  => now()->toISOString(),
        ];
    }
}
