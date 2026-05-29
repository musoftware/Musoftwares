<?php

namespace Modules\ERP\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Product;
use App\Models\User;
use App\Models\Currency;
use Modules\ERP\Models\Expense;
use Modules\ERP\Models\WalletTransaction;
use Modules\ERP\Models\Invoice;

class PosCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $currency = Currency::factory()->create(['currency' => 'USD']);
        
        $this->user = User::factory()->create();
        $this->tenant = Tenant::factory()->create([
            'user_id' => $this->user->id,
            'base_currency_id' => $currency->id
        ]);
        
        \Modules\ERP\Models\UserSubscription::factory()->create([
            'user_id' => $this->user->id,
            'module_name' => 'erp-pos',
            'status' => 'active'
        ]);
        
        $this->client = TenantClient::factory()->create([
            'tenant_id' => $this->tenant->id,
            'currency_id' => $currency->id
        ]);

        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'currency_id' => $currency->id,
            'price' => 100,
            'cost_price' => 60,
            'stock_quantity' => 10,
        ]);
    }

    public function test_can_checkout_as_guest_with_payment_and_cost_expense()
    {
        $response = $this->actingAs($this->user)->postJson(route('pos.checkout'), [
            'client_id' => null,
            'payment_method' => 'cash',
            'is_paid' => true,
            'discount_amount' => 10,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 100,
                ]
            ]
        ]);

        $response->assertStatus(200);

        // Verify walk-in client created
        $walkInClient = TenantClient::where('name', 'Walk-in Client')->first();
        $this->assertNotNull($walkInClient);

        // Verify Invoice
        $invoice = Invoice::latest('id')->first();
        $this->assertEquals($walkInClient->id, $invoice->client_id);
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(190, $invoice->amount); // (2*100) - 10 discount
        $this->assertEquals(10, $invoice->discount_amount);

        // Verify Transaction
        $this->assertDatabaseHas('erp_transactions', [
            'client_id' => $walkInClient->id,
            'amount' => 190,
            'type' => 'received',
            'reference_type' => 'invoice_payment',
        ]);

        // Verify Expense (COGS)
        $this->assertDatabaseHas('erp_expenses', [
            'client_id' => $walkInClient->id,
            'amount' => 120, // 2 * 60 (cost_price)
            'category' => 'COGS',
        ]);

        // Verify Stock Deducted
        $this->assertEquals(8, $this->product->fresh()->stock_quantity);
    }

    public function test_can_checkout_existing_client_as_unpaid()
    {
        $response = $this->actingAs($this->user)->postJson(route('pos.checkout'), [
            'client_id' => $this->client->id,
            'payment_method' => 'card',
            'is_paid' => false,
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_price' => 100,
                ]
            ]
        ]);

        $response->assertStatus(200);

        // Verify Invoice
        $invoice = Invoice::latest('id')->first();
        $this->assertEquals($this->client->id, $invoice->client_id);
        $this->assertEquals('sent', $invoice->status); // Unpaid status
        $this->assertEquals(100, $invoice->amount);

        // Verify NO Transaction generated because it's unpaid
        $this->assertDatabaseMissing('erp_transactions', [
            'reference_type' => 'invoice_payment',
            'reference_id' => $invoice->id,
        ]);

        // Verify Expense (COGS) is still generated
        $this->assertDatabaseHas('erp_expenses', [
            'client_id' => $this->client->id,
            'amount' => 60,
        ]);
    }
}
