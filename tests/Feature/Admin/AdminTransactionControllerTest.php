<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createAdmin()
    {
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function createClient()
    {
        $client = User::factory()->create(['onboarding_completed' => true]);
        $client->assignRole('client');

        return $client;
    }

    public function test_admin_can_access_transactions_index()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.transactions.index'));

        $response->assertSuccessful();
    }

    public function test_non_admin_cannot_access_transactions_index()
    {
        $client = $this->createClient();

        $response = $this->actingAs($client)->get(route('admin.transactions.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_access_create_transaction_page_with_user()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();

        $response = $this->actingAs($admin)->get(route('admin.transactions.create', ['user' => $client->id]));

        $response->assertSuccessful();
    }

    public function test_admin_cannot_access_create_transaction_page_without_user()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.transactions.create'));

        $response->assertRedirect(route('admin.users.index'));
    }

    public function test_admin_can_store_transaction()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();

        $payload = [
            'user' => $client->id,
            'type' => 'earned',
            'data' => [
                [
                    'amount' => 100,
                    'fee' => 0,
                    'currency_id' => Currency::first()->id ?? 1,
                    'transaction_date' => now()->toDateString(),
                    'reason' => 'Test Transaction',
                ],
            ],
        ];

        $response = $this->actingAs($admin)->post(route('admin.transactions.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $client->id,
            'amount' => 100,
        ]);
    }

    public function test_admin_sees_transaction_running_balance()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();
        $currencyId = Currency::first()->id ?? 1;

        \App\Models\Transaction::create([
            'user_id' => $client->id,
            'amount' => 300,
            'type' => 'received',
            'currency_id' => $currencyId,
            'reason' => 'Deposit',
            'created_at' => now()->subDays(2),
        ]);

        \App\Models\Transaction::create([
            'user_id' => $client->id,
            'amount' => -75,
            'type' => 'used',
            'currency_id' => $currencyId,
            'reason' => 'Invoice #1',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.transactions.index', ['user' => $client->id]));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Transactions/Income')
            ->has('transactions.data', 2)
            ->where('transactions.data.0.balance', 225)
            ->where('transactions.data.1.balance', 300)
        );
    }

    public function test_admin_can_filter_transactions_by_search_term()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();
        $currencyId = Currency::first()->id ?? 1;

        \App\Models\Transaction::create([
            'user_id' => $client->id,
            'amount' => 500,
            'type' => 'received',
            'currency_id' => $currencyId,
            'reason' => 'Domain Registration Payment',
            'created_at' => now(),
        ]);

        \App\Models\Transaction::create([
            'user_id' => $client->id,
            'amount' => 150,
            'type' => 'used',
            'currency_id' => $currencyId,
            'reason' => 'Monthly Hosting VPS',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.transactions.index', [
            'type' => 'income',
            'search' => 'Hosting',
        ]));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Transactions/Income')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.reason', 'Monthly Hosting VPS')
        );
    }

    public function test_admin_can_filter_transactions_by_tx_type()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();
        $currencyId = Currency::first()->id ?? 1;

        \App\Models\Transaction::create([
            'user_id' => $client->id,
            'amount' => 400,
            'type' => 'received',
            'currency_id' => $currencyId,
            'reason' => 'Deposit',
            'created_at' => now(),
        ]);

        \App\Models\Transaction::create([
            'user_id' => $client->id,
            'amount' => -100,
            'type' => 'used',
            'currency_id' => $currencyId,
            'reason' => 'Invoice #101',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.transactions.index', [
            'type' => 'income',
            'tx_type' => 'received',
        ]));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Transactions/Income')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.type', 'received')
        );
    }

    public function test_admin_receives_accurate_transaction_summary()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();
        $currencyId = Currency::first()->id ?? 1;

        \App\Models\Transaction::create([
            'user_id' => $client->id,
            'amount' => 1000,
            'type' => 'received',
            'currency_id' => $currencyId,
            'reason' => 'Annual Support',
            'created_at' => now(),
        ]);

        \App\Models\Transaction::create([
            'user_id' => $client->id,
            'amount' => -200,
            'type' => 'used',
            'currency_id' => $currencyId,
            'reason' => 'Server License',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.transactions.index', [
            'type' => 'income',
            'user' => $client->id,
        ]));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Transactions/Income')
            ->has('summary')
            ->where('summary.total_count', 2)
        );
    }
}
