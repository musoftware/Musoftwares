<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Providers;

use Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource;

/**
 * Contract that every gold market provider driver MUST implement.
 * Future: can be renamed to MetalProviderDriver for multi-metal support.
 */
interface GoldProviderDriver
{
    /**
     * Fetch the current price from this provider.
     * Must return a normalized GoldPricePayload.
     *
     * @throws \Modules\GoldSavers\app\Features\LivePrices\Exceptions\GoldProviderException
     */
    public function fetch(): GoldPricePayload;

    /**
     * Ping the provider to check if it is reachable.
     */
    public function isHealthy(): bool;

    /**
     * The market key this driver serves (e.g. "egypt_local", "global_xau").
     */
    public function getMarketKey(): string;

    /**
     * The driver type slug (e.g. "api", "websocket", "manual", "vendor").
     */
    public function getDriverType(): string;

    /**
     * The last measured latency in milliseconds.
     */
    public function getLatencyMs(): int;

    /**
     * Inject the source model so the driver can read credentials and config.
     */
    public function configure(GoldMarketSource $source): static;
}
