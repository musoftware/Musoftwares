<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\CurrenciesExchange;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Project;
use App\Models\Invoice;
use App\Models\AdminSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CurrencyStrictnessTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure AdminSettings are available
        AdminSettings::updateOrCreate(['key' => 'business_currency'], ['value' => '1']); // Assume 1 is USD
        AdminSettings::updateOrCreate(['key' => 'exchange_update_date'], ['value' => now()->toDateString()]);

        // Create base currencies
        Currency::firstOrCreate(['id' => 1], ['currency' => 'USD', 'symbol' => '$']);
        Currency::firstOrCreate(['id' => 2], ['currency' => 'EGP', 'symbol' => 'EGP']);

        // Create an exchange rate: 1 USD = 50 EGP
        CurrenciesExchange::create([
            'currency1' => 1,
            'currency2' => 2,
            'rate' => 50,
            'updated_at' => now(),
            'created_at' => now(),
        ]);
        
        // And reverse: 1 EGP = 0.02 USD
        CurrenciesExchange::create([
            'currency1' => 2,
            'currency2' => 1,
            'rate' => 0.02,
            'updated_at' => now(),
            'created_at' => now(),
        ]);
    }

    public function test_transaction_fails_loudly_without_currency()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User'); // Part of "User {id} is missing a currency_id"

        $user = User::factory()->create(['currency_id' => null]);
        
        $transaction = new Transaction([
            'user_id' => $user->id,
            'amount' => 100,
            'reason' => 'Test missing currency',
            'type' => 'received',
            'currency_id' => null
        ]);
        
        $transaction->save();
    }

    public function test_transaction_boot_converts_amount_to_user_currency()
    {
        // User's base currency is EGP (2)
        $user = User::factory()->create(['currency_id' => 2]);
        
        // Admin creates a transaction in USD (1) for 10 USD
        $transaction = new Transaction([
            'user_id' => $user->id,
            'amount' => 10,
            'reason' => 'Admin transferred USD to EGP user',
            'type' => 'received',
            'currency_id' => 1
        ]);
        
        $transaction->save();
        
        // The transaction should be converted to EGP implicitly via the boot method
        // 10 USD * 50 = 500 EGP
        $this->assertEquals(500, $transaction->amount);
        $this->assertEquals(2, $transaction->currency_id);
    }

    public function test_invoice_creation_fails_without_client_currency()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('is missing a currency configuration');

        $client = User::factory()->create(['currency_id' => null]);
        
        // Mock authentication
        $this->actingAs(User::factory()->create(['currency_id' => 1]));
        
        Invoice::createInvoice($client, null, null);
    }
    
    public function test_invoice_creation_succeeds_with_client_currency()
    {
        $client = User::factory()->create(['currency_id' => 2]); // EGP
        
        $this->actingAs(User::factory()->create(['currency_id' => 1]));
        
        $invoice = Invoice::createInvoice($client, null, null);
        
        $this->assertEquals(2, $invoice->currency_id);
        $this->assertNotNull($invoice->uuid);
    }
}
