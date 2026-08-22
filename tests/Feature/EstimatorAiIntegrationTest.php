<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimatorAiIntegrationTest extends TestCase
{
    use RefreshDatabase;
    public function test_estimator_page_is_publicly_accessible()
    {
        $response = $this->get('/estimator');
        $response->assertStatus(200);
    }

    public function test_estimator_page_serves_json_when_format_json_requested()
    {
        $response = $this->get('/estimator?format=json');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'platforms' => [
                    'web',
                    'mobile',
                    'desktop',
                ],
                'modules' => [
                    'web',
                    'mobile',
                    'desktop',
                ],
            ]);
    }

    public function test_estimator_data_service_includes_khamsat_market_modules()
    {
        $service = app(\App\Services\ProjectEstimatorDataService::class);
        $data = $service->getEstimatorData(50.0);

        $this->assertArrayHasKey('modules', $data);
        $webModules = collect($data['modules']['web'])->keyBy('id');

        $this->assertTrue($webModules->has('web_whatsapp_channels'));
        $this->assertEquals(35, $webModules['web_whatsapp_channels']['price_usd']);
        $this->assertEquals(1750, $webModules['web_whatsapp_channels']['price_egp']);

        $this->assertTrue($webModules->has('web_telegram_bot'));
        $this->assertEquals(30, $webModules['web_telegram_bot']['price_usd']);
        $this->assertEquals(1500, $webModules['web_telegram_bot']['price_egp']);

        $this->assertTrue($webModules->has('web_telegram_channel_collector'));
        $this->assertEquals(25, $webModules['web_telegram_channel_collector']['price_usd']);
        $this->assertEquals(1250, $webModules['web_telegram_channel_collector']['price_egp']);

        $this->assertTrue($webModules->has('web_whatsapp_channel_collector'));
        $this->assertEquals(30, $webModules['web_whatsapp_channel_collector']['price_usd']);
        $this->assertEquals(1500, $webModules['web_whatsapp_channel_collector']['price_egp']);

        $this->assertTrue($webModules->has('web_auto_data_jobs'));
        $this->assertEquals(35, $webModules['web_auto_data_jobs']['price_usd']);
        $this->assertEquals(1750, $webModules['web_auto_data_jobs']['price_egp']);

        $this->assertTrue($webModules->has('web_content_deduplication'));
        $this->assertEquals(25, $webModules['web_content_deduplication']['price_usd']);
        $this->assertEquals(1250, $webModules['web_content_deduplication']['price_egp']);

        $this->assertTrue($webModules->has('web_metaapi_mt5'));
        $this->assertEquals(60, $webModules['web_metaapi_mt5']['price_usd']);
        $this->assertEquals(3000, $webModules['web_metaapi_mt5']['price_egp']);

        $this->assertTrue($webModules->has('web_tradingview_chart'));
        $this->assertEquals(50, $webModules['web_tradingview_chart']['price_usd']);
        $this->assertEquals(2500, $webModules['web_tradingview_chart']['price_egp']);

        $this->assertTrue($webModules->has('web_autotrade_engine'));
        $this->assertEquals(60, $webModules['web_autotrade_engine']['price_usd']);
        $this->assertEquals(3000, $webModules['web_autotrade_engine']['price_egp']);

        $this->assertTrue($webModules->has('web_loss_limits_closure'));
        $this->assertEquals(40, $webModules['web_loss_limits_closure']['price_usd']);
        $this->assertEquals(2000, $webModules['web_loss_limits_closure']['price_egp']);

        $this->assertTrue($webModules->has('web_signal_analytics'));
        $this->assertEquals(35, $webModules['web_signal_analytics']['price_usd']);
        $this->assertEquals(1750, $webModules['web_signal_analytics']['price_egp']);

        $this->assertTrue($webModules->has('web_channel_mirror'));
        $this->assertEquals(45, $webModules['web_channel_mirror']['price_usd']);
        $this->assertEquals(2250, $webModules['web_channel_mirror']['price_egp']);
    }

    public function test_component_benchmark_rates_includes_new_features()
    {
        $components = \App\Services\AI\ComponentBenchmarkRates::getDefaultComponents();

        $this->assertArrayHasKey('whatsapp_channels', $components);
        $this->assertArrayHasKey('telegram_bot', $components);
        $this->assertArrayHasKey('telegram_channel_collector', $components);
        $this->assertArrayHasKey('whatsapp_channel_collector', $components);
        $this->assertArrayHasKey('auto_data_jobs', $components);
        $this->assertArrayHasKey('content_deduplication', $components);
        $this->assertArrayHasKey('metaapi_mt5', $components);
        $this->assertArrayHasKey('tradingview_chart', $components);
        $this->assertArrayHasKey('autotrade_engine', $components);
        $this->assertArrayHasKey('loss_limits_closure', $components);
        $this->assertArrayHasKey('signal_analytics', $components);
        $this->assertArrayHasKey('channel_mirror', $components);
        $this->assertArrayHasKey('upload_google_play', $components);
        $this->assertArrayHasKey('upload_apple_store', $components);
    }
}
