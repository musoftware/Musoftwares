<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\GoldSavers\app\Features\LivePrices\Services\GoldHistoricalPriceService;

/**
 * Aggregates price snapshots into OHLCV history candles.
 * Dispatched on a schedule per interval type.
 */
class AggregateHistoricalPricesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(
        public readonly int    $tenantId,
        public readonly string $marketKey,
        public readonly string $interval,   // minute, hour, day, week, month
        public readonly int    $karat = 24,
    ) {
        $this->onQueue('gold-history');
    }

    public function handle(GoldHistoricalPriceService $historyService): void
    {
        $referenceTime = now();
        [$periodStart, $periodEnd] = $historyService->getPeriodBounds($this->interval, $referenceTime);

        // Aggregate for all standard karats
        foreach ([24, 21, 18, 14] as $karat) {
            $historyService->aggregate(
                $this->tenantId,
                $this->marketKey,
                $this->interval,
                $periodStart,
                $periodEnd,
                $karat,
            );
        }
    }
}
