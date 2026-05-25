<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource;
use Modules\GoldSavers\app\Features\LivePrices\Services\GoldMarketProviderManager;

/**
 * Retries a failed provider with exponential backoff.
 * Dispatched by GoldMarketProviderFailed listener.
 */
class RetryProviderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 5;
    public int $timeout = 15;

    public function __construct(
        public readonly int    $tenantId,
        public readonly int    $sourceId,
    ) {
        $this->onQueue('gold-retry');
    }

    public function handle(GoldMarketProviderManager $manager): void
    {
        $source = GoldMarketSource::find($this->sourceId);
        if (!$source || !$source->is_active) return;

        $driver  = app(\Modules\GoldSavers\app\Features\LivePrices\Providers\Drivers\GoldApiDriver::class)
            ->configure($source);

        if ($driver->isHealthy()) {
            $source->update([
                'is_healthy'      => true,
                'failure_count'   => 0,
                'last_success_at' => now(),
            ]);

            \Modules\GoldSavers\app\Features\LivePrices\Models\GoldPriceEvent::logEvent(
                $this->tenantId,
                'provider_recovered',
                ['source' => $source->name],
                'info',
                $source->id,
            );
        }
    }

    public function backoff(): array
    {
        return [30, 60, 120, 300, 600]; // exponential backoff: 30s, 1m, 2m, 5m, 10m
    }
}
