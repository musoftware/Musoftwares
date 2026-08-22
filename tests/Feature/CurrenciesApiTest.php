<?php

namespace Tests\Feature;

use App\Helpers\CurrencyHelper;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrenciesApiTest extends TestCase
{
    use RefreshDatabase;
    public function test_currencies_endpoint_returns_success_and_valid_structure(): void
    {
        $response = $this->getJson('/api/currencies');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'currencies',
            'usd_rates',
            'gold_world_price',
        ]);
    }

    public function test_currency_helper_prepare_does_not_crash_on_api_failure(): void
    {
        CurrencyHelper::$rates = [];
        $rates = CurrencyHelper::prepare(date('Y-m-d'));

        $this->assertIsArray($rates);
        $this->assertArrayHasKey('USD', $rates);
        $this->assertEquals(1.0, $rates['USD']);
    }
}
