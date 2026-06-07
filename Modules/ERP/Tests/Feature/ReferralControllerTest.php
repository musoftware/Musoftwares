<?php

namespace Modules\ERP\Tests\Feature;

use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\ReferralEarning;
use Tests\TestCase;

class ReferralControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CurrenciesSeeder::class);
    }

    public function test_referrals_index_loads_clients_with_referrers()
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Acme Corp', 'status' => 'active']);
        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp-referrals',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $referrer = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'John Doe', 'email' => 'john@test.com', 'referral_code' => 'JOHNDOE', 'currency_id' => 1]);
        $referee = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Jane Smith', 'email' => 'jane@test.com', 'referred_by' => $referrer->id, 'currency_id' => 1]);

        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->get('/erp/referrals');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ERP/Referrals/Index')
            ->has('clients')
        );

        $inertiaClients = $response->original->getData()['page']['props']['clients'];
        $this->assertCount(2, $inertiaClients);
    }

    public function test_referrals_tree_loads_client_referrals()
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Acme Corp', 'status' => 'active']);
        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp-referrals',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $referrer = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'John Doe', 'email' => 'john@test.com', 'referral_code' => 'JOHNDOE', 'currency_id' => 1]);
        $referee = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Jane Smith', 'email' => 'jane@test.com', 'referred_by' => $referrer->id, 'currency_id' => 1]);

        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->get("/erp/referrals/tree/{$referrer->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ERP/Referrals/Tree')
            ->has('client.referrals')
        );
    }

    public function test_referrals_earnings_loads_earnings_list()
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Acme Corp', 'status' => 'active']);
        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp-referrals',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $referrer = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'John Doe', 'email' => 'john@test.com', 'referral_code' => 'JOHNDOE', 'currency_id' => 1]);
        $referee = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Jane Smith', 'email' => 'jane@test.com', 'referred_by' => $referrer->id, 'currency_id' => 1]);

        $invoice = \Modules\ERP\Models\Invoice::create([
            'tenant_id' => $tenant->id,
            'client_id' => $referee->id,
            'invoice_number' => 'INV-001',
            'amount' => 500.00,
            'currency_id' => 1,
            'business_amount' => 500.00,
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'paid',
        ]);

        ReferralEarning::create([
            'tenant_id' => $tenant->id,
            'referrer_id' => $referrer->id,
            'referee_id' => $referee->id,
            'amount' => 50.00,
            'currency_id' => 1,
            'business_amount' => 50.00,
            'business_currency_id' => 1,
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'commission_rate' => 10.00,
            'status' => 'pending',
            'invoice_id' => $invoice->id,
        ]);

        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->get('/erp/referrals/earnings');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ERP/Referrals/Earnings')
            ->has('earnings.data')
        );
        
        $inertiaEarnings = $response->original->getData()['page']['props']['earnings']['data'];
        $this->assertCount(1, $inertiaEarnings);
        $this->assertEquals(50.00, $inertiaEarnings[0]['amount']);
    }
}
