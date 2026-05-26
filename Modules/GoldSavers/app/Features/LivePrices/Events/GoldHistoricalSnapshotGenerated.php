<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldPriceSnapshot;

/**
 * Fired after a price snapshot is persisted.
 * Triggers: analytics aggregation job, webhook dispatch.
 */
class GoldHistoricalSnapshotGenerated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int               $tenantId,
        public readonly GoldPriceSnapshot $snapshot,
    ) {}
}
