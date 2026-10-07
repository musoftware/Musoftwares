<?php

namespace Tests\Feature\Admin;

use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use App\Models\CurrenciesExchange;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

class UserTabFilterTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_default_tab_is_customers_and_filters_accordingly(): void
    {
        // User with invoice (Customer)
        $customerInvoice = User::factory()->create(['name' => 'Customer With Invoice']);
        Invoice::create([
            'user_id' => $customerInvoice->id,
            'paid' => 100,
            'unpaid' => 0,
            'status' => 'paid',
        ]);

        // User with transaction (Customer). Writing a wallet row converts to the business currency.
        $this->seedUsdEgpRates();
        $customerTransaction = User::factory()->create(['name' => 'Customer With Transaction']);
        Transaction::create([
            'user_id' => $customerTransaction->id,
            'amount' => 50,
            'type' => 'received',
            'reason' => 'Test Payment',
        ]);

        // User without invoice or transaction (Lead)
        $leadUser = User::factory()->create(['name' => 'Lead User']);

        $response = $this->actingAs($this->admin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Users/Index')
            ->where('filters.type', 'customers')
            ->where('tabCounts.customers', 2)
            ->where('tabCounts.leads', 2) // leadUser + admin
            ->has('clients.data', 2)
        );
    }

    public function test_users_list_renders_when_exchange_rates_are_missing(): void
    {
        $this->seedUsdEgpRates();
        $customer = User::factory()->create(['name' => 'Customer With Transaction']);
        Transaction::create([
            'user_id' => $customer->id,
            'amount' => 50,
            'type' => 'received',
            'reason' => 'Test Payment',
        ]);

        CurrenciesExchange::query()->delete();
        CurrenciesExchange::flushCache();

        $response = $this->actingAs($this->admin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Users/Index')
            ->has('clients.data', 1)
        );
    }

    public function test_leads_tab_lists_only_users_without_transactions_or_invoices(): void
    {
        // User with invoice (Customer)
        $customerInvoice = User::factory()->create(['name' => 'Customer With Invoice']);
        Invoice::create([
            'user_id' => $customerInvoice->id,
            'paid' => 100,
            'unpaid' => 0,
            'status' => 'paid',
        ]);

        // User without invoice or transaction (Lead)
        $leadUser = User::factory()->create(['name' => 'Lead User']);

        $response = $this->actingAs($this->admin)->get('/admin/users?type=leads');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Users/Index')
            ->where('filters.type', 'leads')
            ->where('tabCounts.customers', 1)
            ->where('tabCounts.leads', 2) // leadUser + admin
            ->has('clients.data', 2)
        );
    }
}
