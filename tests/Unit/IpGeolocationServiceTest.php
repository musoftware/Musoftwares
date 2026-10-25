<?php

namespace Tests\Unit;

use App\Services\IpGeolocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Country detection uses the offline MaxMind database in storage/app/geoip.mmdb.
 * The file is not in git (58 MB), so these tests skip where it is missing.
 */
class IpGeolocationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! is_file(storage_path('app/geoip.mmdb'))) {
            $this->markTestSkipped('storage/app/geoip.mmdb is not present.');
        }
    }

    public function test_detects_country_currency_and_language_from_the_local_database(): void
    {
        $service = app(IpGeolocationService::class);

        $this->assertSame('US', $service->getCountryFromIp('8.8.8.8'));
        $this->assertSame('EG', $service->getCountryFromIp('41.33.0.1'));
        $this->assertSame('EGP', $service->getCurrencyCodeForIp('41.33.0.1'));
        $this->assertSame('ar', $service->getLanguageForIp('2.89.0.1'));
        $this->assertSame('en', $service->getLanguageForIp('81.2.69.142'));
    }

    public function test_private_and_unknown_addresses_return_null(): void
    {
        $service = app(IpGeolocationService::class);

        $this->assertNull($service->getCountryFromIp('127.0.0.1'));
        $this->assertNull($service->getCountryFromIp('10.0.0.5'));
    }
}
