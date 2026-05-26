<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\GoldSavers\app\Features\LivePrices\Services\GoldLivePriceLimitsService;
use Modules\GoldSavers\app\Features\LivePrices\Services\GoldLivePriceService;

/**
 * Scheduled job: fetch live price from provider and run the full pipeline.
 * Dispatched once per market key per active tenant, every minute.
 */
class FetchLiveGoldPriceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries    = 3;
    public int $timeout  = 30;

    public function __construct(
        public readonly int    $tenantId,
        public readonly string $marketKey,
    ) {
        $this->onQueue('gold-prices');
    }

    public function handle(
        GoldLivePriceService     $priceService,
        GoldLivePriceLimitsService $limitsService,
    ): void {
        // Feature gate — skip silently if not enabled for this tenant
        if (!$limitsService->canUse($this->tenantId)) {
            return;
        }

        try {
            $priceService->fetchAndUpdate($this->tenantId, $this->marketKey);
        } catch (\Throwable $e) {
            Log::channel('gold-prices')->error('FetchLiveGoldPriceJob failed', [
                'tenant_id'  => $this->tenantId,
                'market_key' => $this->marketKey,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function backoff(): array
    {
        return [10, 30, 60]; // seconds between retries
    }
}
