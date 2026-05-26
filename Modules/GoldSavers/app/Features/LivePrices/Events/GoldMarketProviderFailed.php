<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource;

/**
 * Fired when a provider returns an error or becomes unreachable.
 * Triggers: admin notification, retry job, audit log entry.
 */
class GoldMarketProviderFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int              $tenantId,
        public readonly GoldMarketSource $source,
        public readonly string           $reason,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("gold-prices.tenant.{$this->tenantId}")];
    }

    public function broadcastAs(): string
    {
        return 'gold.provider.failed';
    }

    public function broadcastWith(): array
    {
        return [
            'source_id'   => $this->source->id,
            'source_name' => $this->source->name,
            'market_key'  => $this->source->market_key,
            'reason'      => $this->reason,
            'failed_at'   => now()->toISOString(),
        ];
    }
}
