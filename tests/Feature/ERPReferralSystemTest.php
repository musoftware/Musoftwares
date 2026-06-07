<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Currency;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\WalletTransaction;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\ReferralEarning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ERPReferralSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        config(['erp.platform_tenant_id' => 999]);

        // Seed necessary roles/permissions and currencies
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\CurrenciesSeeder::class);

        $this->currency = Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['symbol' => '$', 'string_format' => '$%01.2f']
        );

        $this->user = User::factory()->create([
            'onboarding_completed' => true,
            'currency_id' => 1,
        ]);
        $this->user->assignRole('client');

        $this->tenant = Tenant::create([
            'user_id' => $this->user->id,
            'name' => 'Test Agency',
            'status' => 'active',
            'base_currency_id' => $this->currency->id,
        ]);
    }

    public function test_client_creation_auto_generates_unique_referral_code(): void
    {
        $client = TenantClient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Referrer',
            'email' => 'referrer@test.com',
            'currency_id' => $this->currency->id,
        ]);

        $this->assertNotEmpty($client->referral_code);
        $this->assertStringStartsWith('REF-', $client->referral_code);
    }

    public function test_referral_pages_are_gated_by_saas_addon_subscription(): void
    {
        // 1. Without subscription, access should be forbidden (403)
        $response = $this->actingAs($this->user)
            ->withoutMiddleware(\App\Http\Middleware\SubscriptionMiddleware::class)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->get(route('erp.referrals.index'));

        $response->assertStatus(403);

        // 2. Active subscription
        UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'erp-referrals',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
            'auto_renew' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->withoutMiddleware(\App\Http\Middleware\SubscriptionMiddleware::class)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->get(route('erp.referrals.index'));

        $response->assertStatus(200);
    }

    public function test_invoice_show_page_does_not_crash_without_addon_subscription(): void
    {
        $client = TenantClient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Referee',
            'email' => 'referee@test.com',
            'currency_id' => $this->currency->id,
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'INV-REF-001',
            'client_id' => $client->id,
            'status' => 'draft',
            'amount' => 1000.00,
            'currency_id' => $this->currency->id,
            'business_amount' => 1000.00,
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        // Access invoice details without active addon
        $response = $this->actingAs($this->user)
            ->withoutMiddleware(\App\Http\Middleware\SubscriptionMiddleware::class)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->get(route('erp.invoices.show', $invoice->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('ERP/Invoices/Show')
                 ->has('referral_earnings')
                 ->where('referral_earnings', [])
        );
    }

    public function test_referral_commission_calculates_and_distributes_correctly(): void
    {
        // 1. Create Referrer client
        $referrer = TenantClient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Referrer Client',
            'email' => 'referrer@example.com',
            'currency_id' => $this->currency->id,
        ]);



        // 2. Create Referee client referred by Referrer
        $referee = TenantClient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Referee Client',
            'email' => 'referee@example.com',
            'currency_id' => $this->currency->id,
            'referred_by' => $referrer->id,
        ]);

        WalletTransaction::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $referee->id,
            'type' => 'received',
            'direction' => 'credit',
            'amount' => 1000.00,
            'currency_id' => $this->currency->id,
            'business_amount' => 1000.00,
            'business_currency_id' => $this->tenant->base_currency_id,
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        // 3. Create Invoice for Referee
        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'INV-REF-002',
            'client_id' => $referee->id,
            'status' => 'sent',
            'amount' => 500.00,
            'currency_id' => $this->currency->id,
            'business_amount' => 500.00,
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        // 4. Without addon active, paying invoice should NOT generate commission
        $invoice->billInvoice();
        $this->assertDatabaseMissing('erp_client_referral_earnings', [
            'invoice_id' => $invoice->id,
        ]);
        $this->assertEquals(0.00, (float) $referrer->balance());

        // Reset Invoice and referee wallet balance for next check
        $invoice->update(['status' => 'sent', 'paid_amount' => 0, 'paid_at' => null]);
        WalletTransaction::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $referee->id,
            'type' => 'received',
            'direction' => 'credit',
            'amount' => 500.00,
            'currency_id' => $this->currency->id,
            'business_amount' => 500.00,
            'business_currency_id' => $this->tenant->base_currency_id,
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        // 5. Active subscription to erp-referrals
        UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'erp-referrals',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
            'auto_renew' => true,
        ]);

        // Bill invoice with active addon
        $invoice->billInvoice();

        // 5% of 500.00 is 25.00
        $this->assertDatabaseHas('erp_client_referral_earnings', [
            'tenant_id' => $this->tenant->id,
            'invoice_id' => $invoice->id,
            'referrer_id' => $referrer->id,
            'referee_id' => $referee->id,
            'amount' => 25.00,
            'status' => 'pending',
        ]);

        $this->assertEquals(25.00, (float) $referrer->balance());

        // 6. Cancelling invoice should set commission status to cancelled
        $invoice->cancelInvoice();

        $this->assertDatabaseHas('erp_client_referral_earnings', [
            'invoice_id' => $invoice->id,
            'status' => 'cancelled',
        ]);
    }
}
