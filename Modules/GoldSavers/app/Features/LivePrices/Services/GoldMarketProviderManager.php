<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Services;

use Illuminate\Support\Collection;
use Modules\GoldSavers\app\Features\LivePrices\Events\GoldMarketProviderFailed;
use Modules\GoldSavers\app\Features\LivePrices\Exceptions\GoldProviderException;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldPriceEvent;
use Modules\GoldSavers\app\Features\LivePrices\Providers\GoldPricePayload;
use Modules\GoldSavers\app\Features\LivePrices\Providers\GoldProviderDriver;
use Modules\GoldSavers\app\Features\LivePrices\Providers\Drivers\GoldApiDriver;
use Modules\GoldSavers\app\Features\LivePrices\Providers\Drivers\GoldManualDriver;
use Modules\GoldSavers\app\Features\LivePrices\Providers\Drivers\GoldVendorDriver;

/**
 * Manages the provider pool per market key.
 * Handles: priority ordering, health checks, failover, quarantine & recovery.
 */
class GoldMarketProviderManager
{
    /** Driver map: driver slug → class */
    protected array $driverMap = [
        'api'     => GoldApiDriver::class,
        'manual'  => GoldManualDriver::class,
        'vendor'  => GoldVendorDriver::class,
    ];

    /**
     * Fetch price for a market using the healthiest available provider.
     * Auto-fails over through priority-ordered healthy providers.
     *
     * @param  int    $tenantId
     * @param  string $marketKey
     * @return GoldPricePayload
     * @throws GoldProviderException If all providers fail
     */
    public function fetchForMarket(int $tenantId, string $marketKey): GoldPricePayload
    {
        $sources = $this->getHealthySources($tenantId, $marketKey);

        foreach ($sources as $source) {
            try {
                $driver  = $this->resolveDriver($source);
                $payload = $driver->fetch();

                // Mark provider as healthy
                $source->markHealthy($driver->getLatencyMs());

                return $payload;

            } catch (GoldProviderException $e) {
                // Mark as failed and try next
                $source->markFailed();

                GoldPriceEvent::logEvent(
                    $tenantId,
                    'provider_failed',
                    ['source' => $source->name, 'error' => $e->getMessage()],
                    'critical',
                    $source->id,
                );

                event(new GoldMarketProviderFailed($tenantId, $source, $e->getMessage()));

            } catch (\Throwable $e) {
                $source->markFailed();
                event(new GoldMarketProviderFailed($tenantId, $source, $e->getMessage()));
            }
        }

        throw new \RuntimeException("All providers for market [{$marketKey}] are unavailable.");
    }

    /**
     * Check health of all providers for a given tenant/market.
     */
    public function healthCheck(int $tenantId, string $marketKey): Collection
    {
        return GoldMarketSource::forTenantOrGlobal($tenantId)
            ->active()
            ->forMarket($marketKey)
            ->orderBy('priority')
            ->get()
            ->map(function (GoldMarketSource $source) {
                $driver   = $this->resolveDriver($source);
                $healthy  = $driver->isHealthy();
                $source->update(['is_healthy' => $healthy]);

                return ['source' => $source->name, 'healthy' => $healthy];
            });
    }

    /**
     * All market keys that have at least one healthy source.
     */
    public function availableMarkets(int $tenantId): Collection
    {
        return GoldMarketSource::forTenantOrGlobal($tenantId)
            ->active()
            ->healthy()
            ->get()
            ->pluck('market_key')
            ->unique()
            ->values();
    }

    // ─── Internal ───────────────────────────────────────────────────────────────

    protected function getHealthySources(int $tenantId, string $marketKey): Collection
    {
        return GoldMarketSource::forTenantOrGlobal($tenantId)
            ->active()
            ->forMarket($marketKey)
            ->orderBy('priority')
            ->get();
        // Note: we include unhealthy sources too — markFailed() sets is_healthy=false,
        // but we still try them in order. This allows recovery attempts.
    }

    protected function resolveDriver(GoldMarketSource $source): GoldProviderDriver
    {
        $driverClass = $this->driverMap[$source->driver] ?? GoldManualDriver::class;

        return app($driverClass)->configure($source);
    }

    /**
     * Register a custom driver type (extensible for future plugins).
     */
    public function registerDriver(string $slug, string $driverClass): void
    {
        $this->driverMap[$slug] = $driverClass;
    }
}
