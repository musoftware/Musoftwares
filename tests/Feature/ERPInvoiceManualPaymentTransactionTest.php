<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Currency;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ERPInvoiceManualPaymentTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        config(['erp.platform_tenant_id' => 999]);

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\CurrenciesSeeder::class);

        $this->currency = Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['symbol' => '$', 'string_format' => '$%01.2f']
        );

        $this->user = User::factory()->create([
            'onboarding_completed' => true,
            'preferred_currency' => 'USD',
        ]);
        $this->user->assignRole('client');

        $this->tenant = Tenant::create([
            'user_id' => $this->user->id,
            'name' => 'Test Agency',
            'status' => 'active',
            'base_currency_id' => $this->currency->id,
        ]);
    }

    public function test_mark_paid_manual_generates_credit_and_debit_transactions(): void
    {
        $client = TenantClient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'currency_id' => $this->currency->id,
        ]);

        // Seed initial balance of 150.00
        WalletTransaction::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'type' => 'manual_credit',
            'direction' => 'credit',
            'amount' => 150.00,
            'currency_id' => $this->currency->id,
            'business_amount' => 150.00,
            'business_currency_id' => $this->tenant->base_currency_id,
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'note' => 'Initial deposit',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'INV-MAN-001',
            'client_id' => $client->id,
            'status' => 'sent',
            'amount' => 500.00,
            'currency_id' => $this->currency->id,
            'business_amount' => 500.00,
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        // Act
        $result = $invoice->markPaidManual();

        $this->assertTrue($result['ok']);
        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertEquals(500.00, (float) $invoice->fresh()->paid_amount);

        // Net wallet balance should remain unchanged
        $this->assertEquals(150.00, (float) $client->balance());

        // Check transactions
        $transactions = WalletTransaction::where('client_id', $client->id)
            ->orderBy('id', 'asc')
            ->get();

        $this->assertCount(3, $transactions);

        // Initial deposit
        $this->assertEquals('manual_credit', $transactions[0]->type);
        $this->assertEquals('credit', $transactions[0]->direction);
        $this->assertEquals(150.00, (float) $transactions[0]->amount);

        // Credit transaction
        $creditTx = $transactions[1];
        $this->assertEquals('manual_credit', $creditTx->type);
        $this->assertEquals('credit', $creditTx->direction);
        $this->assertEquals(500.00, (float) $creditTx->amount);
        $this->assertEquals(Invoice::class, $creditTx->reference_type);
        $this->assertEquals($invoice->id, $creditTx->reference_id);

        // Debit transaction
        $debitTx = $transactions[2];
        $this->assertEquals('invoice_paid', $debitTx->type);
        $this->assertEquals('debit', $debitTx->direction);
        $this->assertEquals(500.00, (float) $debitTx->amount);
        $this->assertEquals(Invoice::class, $debitTx->reference_type);
        $this->assertEquals($invoice->id, $debitTx->reference_id);
    }

    public function test_mark_paid_manual_handles_remaining_amount_after_partial_payment(): void
    {
        $client = TenantClient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'currency_id' => $this->currency->id,
        ]);

        // Seed initial balance of 500.00
        WalletTransaction::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'type' => 'manual_credit',
            'direction' => 'credit',
            'amount' => 500.00,
            'currency_id' => $this->currency->id,
            'business_amount' => 500.00,
            'business_currency_id' => $this->tenant->base_currency_id,
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'note' => 'Initial deposit',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'INV-MAN-002',
            'client_id' => $client->id,
            'status' => 'sent',
            'amount' => 500.00,
            'currency_id' => $this->currency->id,
            'business_amount' => 500.00,
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        // Apply a partial payment of 200.00 first (this debits wallet by 200.00)
        $invoice->partiallyBillInvoice(200.00);

        $this->assertEquals(300.00, (float) $client->balance());
        $this->assertEquals('partial', $invoice->fresh()->status);
        $this->assertEquals(200.00, (float) $invoice->fresh()->paid_amount);

        // Now mark paid manually (remaining 300.00 should generate credit & debit of 300.00)
        $result = $invoice->markPaidManual();

        $this->assertTrue($result['ok']);
        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertEquals(500.00, (float) $invoice->fresh()->paid_amount);

        // Wallet balance remains 300.00
        $this->assertEquals(300.00, (float) $client->balance());

        // Check transactions
        $transactions = WalletTransaction::where('client_id', $client->id)
            ->orderBy('id', 'asc')
            ->get();

        // 1 initial credit, 1 from partial payment, 2 from manual payment = 4 total
        $this->assertCount(4, $transactions);

        // Initial deposit
        $this->assertEquals('manual_credit', $transactions[0]->type);
        $this->assertEquals('credit', $transactions[0]->direction);
        $this->assertEquals(500.00, (float) $transactions[0]->amount);

        // First transaction (debit from partial bill)
        $this->assertEquals('invoice_paid', $transactions[1]->type);
        $this->assertEquals('debit', $transactions[1]->direction);
        $this->assertEquals(200.00, (float) $transactions[1]->amount);

        // Second transaction (credit from manual mark paid for the remaining amount)
        $this->assertEquals('manual_credit', $transactions[2]->type);
        $this->assertEquals('credit', $transactions[2]->direction);
        $this->assertEquals(300.00, (float) $transactions[2]->amount);

        // Third transaction (debit from manual mark paid for the remaining amount)
        $this->assertEquals('invoice_paid', $transactions[3]->type);
        $this->assertEquals('debit', $transactions[3]->direction);
        $this->assertEquals(300.00, (float) $transactions[3]->amount);
    }
}
