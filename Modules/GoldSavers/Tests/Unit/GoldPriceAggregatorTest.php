<?php

use Modules\GoldSavers\app\Features\LivePrices\Services\GoldPriceAggregator;
use Modules\GoldSavers\app\Features\LivePrices\Providers\GoldPricePayload;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldLivePrice;
use Modules\GoldSavers\app\Features\LivePrices\Events\GoldPriceAnomalyDetected;
use Illuminate\Support\Facades\Event;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->aggregator = new GoldPriceAggregator();
    Event::fake();
});

it('rejects zero or negative prices', function () {
    $payload = new GoldPricePayload(
        marketKey: 'egypt_local',
        priceUsdOz: 2000.0,
        priceLocalGram24k: 0.0,
        priceLocalGram21k: 0.0,
        priceLocalGram18k: 0.0,
        priceLocalGram14k: 0.0,
        buyPrice: 0.0,
        sellPrice: 0.0,
        currencyId: 1,
        exchangeRate: 50.0,
        latencyMs: 100
    );

    $result = $this->aggregator->validate($payload, null, 1);

    expect($result->passed)->toBeFalse()
        ->and($result->isAnomaly)->toBeFalse()
        ->and($result->failureCode)->toBe('price_zero');
});

it('detects massive price spikes as anomalies', function () {
    $existing = new GoldLivePrice([
        'price_gram_24k' => 4000.0,
        'fetched_at' => now()->subMinutes(5)
    ]);

    // A 50% spike
    $payload = new GoldPricePayload(
        marketKey: 'egypt_local',
        priceUsdOz: 2000.0,
        priceLocalGram24k: 6000.0, 
        priceLocalGram21k: 5200.0,
        priceLocalGram18k: 4500.0,
        priceLocalGram14k: 3500.0,
        buyPrice: 6000.0,
        sellPrice: 5900.0,
        currencyId: 1,
        exchangeRate: 50.0,
        latencyMs: 100
    );

    $result = $this->aggregator->validate($payload, $existing, 1);

    expect($result->passed)->toBeFalse()
        ->and($result->isAnomaly)->toBeTrue()
        ->and($result->failureCode)->toBe('spike_detected');

    Event::assertDispatched(GoldPriceAnomalyDetected::class, function ($event) {
        return $event->tenantId === 1 && $event->anomalyType === 'spike_detected';
    });
});

it('rejects out of range prices', function () {
    $payload = new GoldPricePayload(
        marketKey: 'egypt_local',
        priceUsdOz: 2000.0,
        priceLocalGram24k: 999999.0, // Above max threshold
        priceLocalGram21k: 5200.0,
        priceLocalGram18k: 4500.0,
        priceLocalGram14k: 3500.0,
        buyPrice: 6000.0,
        sellPrice: 5900.0,
        currencyId: 1,
        exchangeRate: 50.0,
        latencyMs: 100
    );

    $result = $this->aggregator->validate($payload, null, 1);

    expect($result->passed)->toBeFalse()
        ->and($result->isAnomaly)->toBeTrue()
        ->and($result->failureCode)->toBe('price_out_of_range');
});

it('skips duplicate prices within 30 seconds', function () {
    $existing = new GoldLivePrice([
        'price_gram_24k' => 4000.0,
        'fetched_at' => now()->subSeconds(10)
    ]);

    $payload = new GoldPricePayload(
        marketKey: 'egypt_local',
        priceUsdOz: 2000.0,
        priceLocalGram24k: 4000.0, // Same price
        priceLocalGram21k: 3500.0,
        priceLocalGram18k: 3000.0,
        priceLocalGram14k: 2333.0,
        buyPrice: 4000.0,
        sellPrice: 3950.0,
        currencyId: 1,
        exchangeRate: 50.0,
        latencyMs: 100
    );

    $result = $this->aggregator->validate($payload, $existing, 1);

    expect($result->isDuplicate)->toBeTrue();
});

it('marks prices stale after 30 minutes', function () {
    $stalePrice = new GoldLivePrice([
        'fetched_at' => now()->subHours(2)
    ]);

    $freshPrice = new GoldLivePrice([
        'fetched_at' => now()->subMinutes(5)
    ]);

    expect($this->aggregator->isStale($stalePrice))->toBeTrue()
        ->and($this->aggregator->isStale($freshPrice))->toBeFalse()
        ->and($this->aggregator->isStale(null))->toBeTrue();
});
