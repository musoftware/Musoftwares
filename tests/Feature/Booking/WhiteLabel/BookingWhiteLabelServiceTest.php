<?php

namespace Tests\Feature\Booking\WhiteLabel;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Modules\Booking\app\Features\WhiteLabel\Services\BookingWhiteLabelService;
use Modules\Booking\app\Features\WhiteLabel\Models\WhiteLabelSetting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;

class BookingWhiteLabelServiceTest extends TestCase
{
    use DatabaseTransactions;

    private BookingWhiteLabelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BookingWhiteLabelService::class);
    }

    public function test_it_creates_default_settings_if_none_exist()
    {
        $tenantId = 1;

        $settings = $this->service->getSettings($tenantId);

        $this->assertInstanceOf(WhiteLabelSetting::class, $settings);
        $this->assertEquals($tenantId, $settings->tenant_id);
        $this->assertEquals('#000000', $settings->primary_color);
        $this->assertFalse($settings->is_active);
    }

    public function test_it_updates_settings_and_flushes_cache()
    {
        Event::fake();
        
        $tenantId = 1;
        $this->service->getSettings($tenantId);

        $this->service->updateSettings($tenantId, [
            'primary_color' => '#ff0000',
            'is_active' => true,
        ]);

        $settings = $this->service->getSettings($tenantId);

        $this->assertEquals('#ff0000', $settings->primary_color);
        $this->assertTrue($settings->is_active);

        Event::assertDispatched(\Modules\Booking\app\Features\WhiteLabel\Events\WhiteLabelThemeUpdated::class);
    }
}
