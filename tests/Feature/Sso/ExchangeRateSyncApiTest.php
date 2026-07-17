<?php

namespace Tests\Feature\Sso;

use App\Models\Currency;
use App\Models\CurrenciesExchange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeRateSyncApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.goldsaversys.shared_secret' => 'test-secret-key-123']);
    }

    public function test_it_rejects_requests_without_headers(): void
    {
        $response = $this->postJson('/api/sso/exchange-rates/sync', [
            'module' => 'goldsaversys',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'missing_signature_headers']);
    }

    public function test_it_rejects_requests_with_invalid_signature(): void
    {
        $timestamp = (string) now()->timestamp;
        $response = $this->withHeaders([
            'X-GoldSaver-Signature' => 'invalid-sig',
            'X-GoldSaver-Timestamp' => $timestamp,
            'X-GoldSaver-System' => 'goldsaversys',
        ])->postJson('/api/sso/exchange-rates/sync', [
            'module' => 'goldsaversys',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'invalid_signature']);
    }

    public function test_it_returns_currencies_and_exchange_rates_when_valid_signature_provided(): void
    {
        // Set up currencies in DB
        $usd = Currency::query()->firstOrCreate(['currency' => 'USD'], ['symbol' => '$', 'string_format' => '$%01.2f']);
        $egp = Currency::query()->firstOrCreate(['currency' => 'EGP'], ['symbol' => 'e£', 'string_format' => 'e£%01.2f']);

        // Set up exchange rates
        CurrenciesExchange::query()->create([
            'date_string' => now()->toDateString(),
            'currency1' => $usd->id,
            'currency2' => $egp->id,
            'rate' => 48.5,
        ]);

        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.exchange-rates-sync', 'test-secret-key-123');

        $response = $this->withHeaders([
            'X-GoldSaver-Signature' => $signature,
            'X-GoldSaver-Timestamp' => $timestamp,
            'X-GoldSaver-System' => 'goldsaversys',
        ])->postJson('/api/sso/exchange-rates/sync', [
            'module' => 'goldsaversys',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'currencies' => [
                    '*' => [
                        'code',
                        'name',
                        'symbol',
                        'current_usd_rate',
                        'is_active',
                    ],
                ],
                'rates' => [
                    '*' => [
                        'from_currency',
                        'to_currency',
                        'rate',
                        'date',
                        'source',
                    ],
                ],
            ],
        ]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($data['currencies']));
        $this->assertCount(1, $data['rates']);
        
        $egpCurrency = collect($data['currencies'])->firstWhere('code', 'EGP');
        $this->assertNotNull($egpCurrency);
        $this->assertEquals('Egyptian Pound', $egpCurrency['name']);
        $this->assertEquals('48.50000000', $egpCurrency['current_usd_rate']);
        $this->assertEquals('48.50000000', $data['rates'][0]['rate']);
    }
}
