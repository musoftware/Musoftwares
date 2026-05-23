<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\PaymentMethod;
use Modules\ERP\Models\Withdrawal;
use Modules\ERP\Models\ClientWallet;
use Tests\TestCase;

class WithdrawalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Tenant $tenant;
    protected TenantClient $client;
    protected ClientWallet $wallet;
    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create(['onboarding_completed' => true]);
        $this->user->assignRole('client');

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->tenant = Tenant::create([
            'user_id' => $this->user->id,
            'name' => 'Agency Pro',
            'status' => 'active',
        ]);

        session(['tenant_id' => $this->tenant->id]);

        $this->client = TenantClient::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'name' => 'John Doe',
            'email' => $this->user->email,
            'phone' => '+15559876543',
            'currency' => 'USD',
            'address' => '456 West Ave',
        ]);

        $this->wallet = ClientWallet::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'balance' => 2000.00,
            'currency' => 'USD',
            'locked_balance' => 0.00,
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'type' => 'bank_transfer',
            'bank_name' => 'Chase Bank',
            'account_holder_name' => 'John Doe',
            'account_number' => '1234567890',
            'bank_country' => 'US',
            'bank_currency' => 'USD',
            'iban' => 'US1234567890',
            'status' => 'approved',
        ]);
    }

    public function test_client_can_view_withdrawal_index(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->get(route('erp.withdrawals.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_view_withdrawal_index(): void
    {
        $this->tenant->update(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->get(route('erp.withdrawals.index'));

        $response->assertStatus(200);
    }

    public function test_client_can_request_withdrawal_success(): void
    {
        $this->withoutExceptionHandling();

        $response = $this->actingAs($this->user)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->post(route('erp.withdrawals.store'), [
                'client_id' => $this->client->id,
                'amount' => 500.00,
                'payment_method_id' => $this->paymentMethod->id,
            ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('erp_withdrawals', [
            'client_id' => $this->client->id,
            'amount' => 500.00,
            'status' => 'pending',
        ]);
    }

    public function test_client_cannot_request_withdrawal_insufficient_funds(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->post(route('erp.withdrawals.store'), [
                'client_id' => $this->client->id,
                'amount' => 2500.00,
                'payment_method_id' => $this->paymentMethod->id,
            ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('erp_withdrawals', [
            'client_id' => $this->client->id,
            'amount' => 2500.00,
        ]);
    }

    public function test_admin_can_approve_and_mark_paid_withdrawal(): void
    {
        Storage::fake('public');

        $this->tenant->update(['user_id' => $this->admin->id]);

        $withdrawal = Withdrawal::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 500.00,
            'currency_code' => 'USD',
            'status' => 'pending',
        ]);

        // Approve
        $response = $this->actingAs($this->admin)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->post(route('erp.withdrawals.approve', $withdrawal->id));

        $response->assertStatus(302);
        $this->assertEquals('approved', $withdrawal->fresh()->status);

        // Mark Paid with fake proof file upload
        $proofFile = UploadedFile::fake()->create('proof.pdf', 100);

        $response = $this->actingAs($this->admin)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->post(route('erp.withdrawals.markPaid', $withdrawal->id), [
                'reference' => 'TXN-BANK-1002',
                'proof' => $proofFile,
            ]);

        $response->assertStatus(302);
        $this->assertEquals('paid', $withdrawal->fresh()->status);
        $this->assertEquals('TXN-BANK-1002', $withdrawal->fresh()->reference);
        $this->assertNotNull($withdrawal->fresh()->proof_path);

        // Verify balance was deducted
        $this->assertEquals(1500.00, $this->wallet->fresh()->balance);
    }

    public function test_admin_can_reject_withdrawal(): void
    {
        $this->tenant->update(['user_id' => $this->admin->id]);

        $withdrawal = Withdrawal::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 300.00,
            'currency_code' => 'USD',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->post(route('erp.withdrawals.reject', $withdrawal->id), [
                'admin_notes' => 'Invalid bank details',
            ]);

        $response->assertStatus(302);
        $this->assertEquals('rejected', $withdrawal->fresh()->status);
        $this->assertEquals('Invalid bank details', $withdrawal->fresh()->admin_notes);
    }

    public function test_client_can_cancel_pending_withdrawal(): void
    {
        $withdrawal = Withdrawal::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 200.00,
            'currency_code' => 'USD',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->post(route('erp.withdrawals.cancel', $withdrawal->id));

        $response->assertStatus(302);
        $this->assertEquals('cancelled', $withdrawal->fresh()->status);
    }
}
