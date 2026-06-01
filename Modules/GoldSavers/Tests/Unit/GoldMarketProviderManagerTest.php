<?php

use Modules\GoldSavers\app\Features\LivePrices\Services\GoldMarketProviderManager;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource;
use Modules\GoldSavers\app\Features\LivePrices\Providers\GoldProviderDriver;
use Modules\GoldSavers\app\Features\LivePrices\Exceptions\GoldProviderException;
use Modules\GoldSavers\app\Features\LivePrices\Providers\GoldPricePayload;
use Modules\GoldSavers\app\Features\LivePrices\Events\GoldMarketProviderFailed;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(Tests\TestCase::class, DatabaseTransactions::class);

beforeEach(function () {
    Event::fake();
    $this->manager = app(GoldMarketProviderManager::class);
});

it('fetches from the healthiest provider and marks it healthy', function () {
    $source = GoldMarketSource::factory()->create([
        'tenant_id' => null,
        'market_key' => 'egypt_local',
        'driver' => 'api',
        'priority' => 1,
        'is_active' => true,
    ]);

    $mockDriver = Mockery::mock(GoldProviderDriver::class, function (MockInterface $mock) use ($source) {
        $mock->shouldReceive('configure')->with(Mockery::on(fn($arg) => $arg->id === $source->id))->andReturnSelf();
        
        $payload = new GoldPricePayload('egypt_local', 2000, 4000, 3500, 3000, 2333, 4000, 3950, 2, 50, 150);
        $mock->shouldReceive('fetch')->once()->andReturn($payload);
        $mock->shouldReceive('getLatencyMs')->andReturn(150);
    });

    // We must bind the mocked driver slug so the manager resolves our mock
    $this->manager->registerDriver('api', 'api_driver_mock_fetch');
    app()->instance('api_driver_mock_fetch', $mockDriver);

    $payload = $this->manager->fetchForMarket(1, 'egypt_local');

    expect($payload->priceLocalGram24k)->toBe(4000.0);
    expect($source->fresh()->is_healthy)->toBeTrue();
});

it('fails over to the next provider if the first one throws an exception', function () {
    $primarySource = GoldMarketSource::factory()->create([
        'tenant_id' => null,
        'market_key' => 'egypt_local',
        'driver' => 'api',
        'name' => 'Primary',
        'priority' => 1,
        'is_active' => true,
    ]);

    $secondarySource = GoldMarketSource::factory()->create([
        'tenant_id' => null,
        'market_key' => 'egypt_local',
        'driver' => 'vendor',
        'name' => 'Secondary',
        'priority' => 2,
        'is_active' => true,
    ]);

    $primaryMock = Mockery::mock(GoldProviderDriver::class, function (MockInterface $mock) use ($primarySource) {
        $mock->shouldReceive('configure')->andReturnSelf();
        $mock->shouldReceive('fetch')->once()->andThrow(new GoldProviderException("Connection timeout", $primarySource));
    });

    $secondaryMock = Mockery::mock(GoldProviderDriver::class, function (MockInterface $mock) {
        $mock->shouldReceive('configure')->andReturnSelf();
        $payload = new GoldPricePayload('egypt_local', 2000, 4050, 3540, 3030, 2350, 4050, 4000, 2, 50, 200);
        $mock->shouldReceive('fetch')->once()->andReturn($payload);
        $mock->shouldReceive('getLatencyMs')->andReturn(200);
    });

    $this->manager->registerDriver('api', 'api_driver_mock_failover_1');
    app()->instance('api_driver_mock_failover_1', $primaryMock);

    $this->manager->registerDriver('vendor', 'api_driver_mock_failover_2');
    app()->instance('api_driver_mock_failover_2', $secondaryMock);

    $payload = $this->manager->fetchForMarket(1, 'egypt_local');

    expect($payload->priceLocalGram24k)->toBe(4050.0);

    // Verify state updates
    expect($primarySource->fresh()->failure_count)->toBe(1);
    expect($secondarySource->fresh()->is_healthy)->toBeTrue();

    Event::assertDispatched(GoldMarketProviderFailed::class, function ($event) use ($primarySource) {
        return $event->source->id === $primarySource->id;
    });
});

it('throws exception if all providers fail', function () {
    $source = GoldMarketSource::factory()->create([
        'market_key' => 'egypt_local',
        'driver' => 'api',
        'priority' => 1,
        'is_active' => true,
    ]);

    $mockDriver = Mockery::mock(GoldProviderDriver::class, function (MockInterface $mock) use ($source) {
        $mock->shouldReceive('configure')->andReturnSelf();
        $mock->shouldReceive('fetch')->andThrow(new GoldProviderException("Down", $source));
    });

    $this->manager->registerDriver('api', 'api_driver_mock_fail_all');
    app()->instance('api_driver_mock_fail_all', $mockDriver);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('All providers for market [egypt_local] are unavailable.');

    $this->manager->fetchForMarket(1, 'egypt_local');
});
