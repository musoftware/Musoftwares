<?php

use Modules\GoldSavers\app\Features\LivePrices\Services\GoldLivePriceService;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource;
use Modules\GoldSavers\app\Features\LivePrices\Providers\GoldPricePayload;
use Modules\GoldSavers\app\Features\LivePrices\Events\GoldPriceUpdated;
use Modules\GoldSavers\app\Features\LivePrices\Providers\GoldProviderDriver;
use Modules\GoldSavers\app\Features\LivePrices\Services\GoldMarketProviderManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
    $this->service = app(GoldLivePriceService::class);
});

it('fetches, validates, snapshots, caches, and broadcasts the live price', function () {
    $source = GoldMarketSource::factory()->create([
        'tenant_id' => null,
        'market_key' => 'egypt_local',
        'driver' => 'api',
        'is_active' => true,
    ]);

    $payload = new GoldPricePayload('egypt_local', 2000, 4000, 3500, 3000, 2333, 4000, 3950, 2, 50, 100);

    // Mock Provider Manager to return our payload
    $mockManager = Mockery::mock(GoldMarketProviderManager::class);
    $mockManager->shouldReceive('fetchForMarket')->with(1, 'egypt_local')->andReturn($payload);
    
    // Inject mock
    $this->app->instance(GoldMarketProviderManager::class, $mockManager);
    $service = app(GoldLivePriceService::class);

    $livePrice = $service->fetchAndUpdate(1, 'egypt_local');

    // Asserts
    expect((float) $livePrice->price_gram_24k)->toBe(4000.0)
        ->and($livePrice->direction)->toBe('up') // Since previous was 0
        ->and($livePrice->tenant_id)->toBe(1);

    // Verify Cache
    $cached = Cache::get("gold.live.1.egypt_local");
    expect((float) $cached['price_gram_24k'])->toBe(4000.0);

    // Verify DB
    $this->assertDatabaseHas('gold_live_prices', [
        'tenant_id' => 1,
        'market_key' => 'egypt_local',
        'price_gram_24k' => 4000,
    ]);

    $this->assertDatabaseHas('gold_price_snapshots', [
        'tenant_id' => 1,
        'market_key' => 'egypt_local',
        'price_gram_24k' => 4000,
    ]);

    $this->assertDatabaseHas('gold_price_events', [
        'tenant_id' => 1,
        'event_type' => 'price_updated',
    ]);
});

it('does not update the live row if the price is an anomaly', function () {
    // Let's create an existing price of 4000
    \Modules\GoldSavers\app\Features\LivePrices\Models\GoldLivePrice::factory()->create([
        'tenant_id' => 1,
        'market_key' => 'egypt_local',
        'price_gram_24k' => 4000,
        'fetched_at' => now()->subMinutes(5),
    ]);

    // Anomaly payload (drop to 10 EGP)
    $payload = new GoldPricePayload('egypt_local', 2000, 10, 8, 7, 5, 10, 9, 2, 50, 100);

    $mockManager = Mockery::mock(GoldMarketProviderManager::class);
    $mockManager->shouldReceive('fetchForMarket')->with(1, 'egypt_local')->andReturn($payload);
    $this->app->instance(GoldMarketProviderManager::class, $mockManager);

    $service = app(GoldLivePriceService::class);
    $livePrice = $service->fetchAndUpdate(1, 'egypt_local');

    // The live price should remain 4000
    expect((float) $livePrice->price_gram_24k)->toBe(4000.0);

    // However, the snapshot SHOULD be saved (for audit) but flagged as anomaly
    $this->assertDatabaseHas('gold_price_snapshots', [
        'tenant_id' => 1,
        'market_key' => 'egypt_local',
        'price_gram_24k' => 10.0, // The anomaly value
        'anomaly_detected' => true,
    ]);
});
