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
}
