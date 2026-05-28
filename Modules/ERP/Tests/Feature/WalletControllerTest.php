<?php

namespace Modules\ERP\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Modules\ERP\Models\WalletTransaction;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Tenant;
use Tests\TestCase;

class WalletControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', [
            '--path' => [
                'database/migrations',
                'Modules/Core/Database/Migrations',
                'Modules/ERP/Database/Migrations',
            ]
        ]);
        
        $this->seed(\Database\Seeders\CurrenciesSeeder::class);
        $this->withoutMiddleware();
    }


    public function test_show_ledger(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Acme Corp', 'status' => 'active']);
        $client = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Test Client', 'email' => 'test@example.com', 'currency_id' => 1]);
        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->get("/erp/clients/{$client->id}/wallet");
        $response->assertStatus(200);
    }

    public function test_receive_and_send_payments(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Test Tenant', 'status' => 'active']);
        session(['tenant_id' => $tenant->id]);

        $client = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Test Client', 'email' => 'client@test.com', 'currency_id' => 1]);

        // Test Receive Payment
        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->post("/erp/clients/{$client->id}/wallet/receive", [
            'amount' => 500.00,
            'note' => 'Initial payment',
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals(500.00, (float)$client->balance());

        // Verify transaction type
        $receivedTx = WalletTransaction::where('client_id', $client->id)->where('type', 'received')->first();
        $this->assertNotNull($receivedTx);
        $this->assertEquals(500.00, (float)$receivedTx->amount);

        // Test Locked Balance by creating a sent invoice
        $invoice = \Modules\ERP\Models\Invoice::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'invoice_number' => 'INV-2026-001',
            'status' => 'sent',
            'amount' => 100.00,
            'paid_amount' => 0.00,
            'currency_id' => 1,
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $this->assertEquals(100.00, (float)$client->lockedBalance());

        // Test Send Payment
        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->post("/erp/clients/{$client->id}/wallet/send", [
            'amount' => 200.00,
            'note' => 'Service charge',
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals(300.00, (float)$client->balance());

        // Verify sent transaction has negative amount
        $sentTx = WalletTransaction::where('client_id', $client->id)->where('type', 'sent')->first();
        $this->assertNotNull($sentTx);
        $this->assertEquals(-200.00, (float)$sentTx->amount);
    }

    public function test_refund_payment(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Test Tenant', 'status' => 'active']);
        $client = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Test Client', 'email' => 'client@test.com', 'currency_id' => 1]);

        // First receive some money
        $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->post("/erp/clients/{$client->id}/wallet/receive", [
            'amount' => 500.00,
            'note' => 'Initial payment',
        ]);
        $this->assertEquals(500.00, (float)$client->balance());

        // Then refund
        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->post("/erp/clients/{$client->id}/wallet/refund", [
            'amount' => 150.00,
            'note' => 'Partial refund for overpayment',
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals(350.00, (float)$client->balance());

        // Verify refund transaction has negative amount
        $refundTx = WalletTransaction::where('client_id', $client->id)->where('type', 'refunded')->first();
        $this->assertNotNull($refundTx);
        $this->assertEquals(-150.00, (float)$refundTx->amount);
    }

    public function test_user_cannot_modify_other_tenant_client(): void
    {
        // User 1 & Tenant 1
        $user1 = User::factory()->create();
        $tenant1 = Tenant::create(['user_id' => $user1->id, 'name' => 'Tenant 1', 'status' => 'active']);
        $client1 = TenantClient::create(['tenant_id' => $tenant1->id, 'name' => 'Client 1', 'email' => 'client1@test.com', 'currency_id' => 1]);

        // User 2 & Tenant 2
        $user2 = User::factory()->create();
        $tenant2 = Tenant::create(['user_id' => $user2->id, 'name' => 'Tenant 2', 'status' => 'active']);
        
        // Setup session for User 2
        session(['tenant_id' => $tenant2->id]);

        $response = $this->actingAs($user2)->withSession(['tenant_id' => $tenant2->id])->postJson("/erp/clients/{$client1->id}/wallet/receive", [
            'amount' => 500.00,
            'note' => 'Hacker deposit',
        ]);
        
        // Should be not found (404) due to tenant isolation boundary checks
        $response->assertStatus(404);
        
        // Verify balance did not change
        $this->assertEquals(0.00, (float)$client1->balance());
    }

    public function test_receive_and_send_with_project(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Acme Corp', 'status' => 'active']);
        $client = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Test Client', 'email' => 'test@example.com', 'currency_id' => 1]);
        
        $project = \Modules\ERP\Models\Project::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'name' => 'Acme Website',
            'status' => 'Active',
            'budget' => 1000.00,
            'leader' => 'John Doe',
            'currency_id' => 1,
        ]);

        // Receive with project_id
        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->post("/erp/clients/{$client->id}/wallet/receive", [
            'amount' => 500.00,
            'note' => 'Project milestone payment',
            'project_id' => $project->id,
        ]);

        $response->assertRedirect(route('erp.projects.show', $project->id));
        $this->assertEquals(500.00, (float)$client->balance());

        $transaction = WalletTransaction::where('project_id', $project->id)->where('type', 'received')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(500.00, (float)$transaction->amount);
        $this->assertEquals('received', $transaction->type);

        // Send with project_id
        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->post("/erp/clients/{$client->id}/wallet/send", [
            'amount' => 200.00,
            'note' => 'Project design costs',
            'project_id' => $project->id,
        ]);

        $response->assertRedirect(route('erp.projects.show', $project->id));
        $this->assertEquals(300.00, (float)$client->balance());

        $sentTransaction = WalletTransaction::where('project_id', $project->id)->where('type', 'sent')->first();
        $this->assertNotNull($sentTransaction);
        $this->assertEquals(-200.00, (float)$sentTransaction->amount);
        $this->assertEquals('sent', $sentTransaction->type);
    }

    public function test_send_insufficient_balance(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Test Tenant', 'status' => 'active']);
        $client = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Test Client', 'email' => 'client@test.com', 'currency_id' => 1]);

        // Try to send without any balance
        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->post("/erp/clients/{$client->id}/wallet/send", [
            'amount' => 500.00,
            'note' => 'Should fail',
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertEquals(0.00, (float)$client->balance());
    }
}
