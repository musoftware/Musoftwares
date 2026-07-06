<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;

class AdminTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_transactions_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.transactions.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_transactions_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.transactions.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_create_transaction_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.transactions.create', ['user' => $this->clientUser->id]));
        $response->assertStatus(200);
    }

    public function test_create_transaction_page_requires_user(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.transactions.create'));
        $response->assertRedirect(route('admin.users.index'));
    }

    public function test_admin_can_store_transaction(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.transactions.store'), [
            'user' => $this->clientUser->id,
            'type' => 'earned',
            'data' => [
                ['amount' => 100, 'fee' => 0, 'date' => now()->toDateString(), 'reason' => 'Test Reason', 'currency' => 1]
            ]
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->clientUser->id,
            'type' => 'earned',
        ]);
    }

    public function test_admin_can_store_transaction_with_custom_date_time(): void
    {
        $customDate = '2026-05-15 14:30:00';
        $response = $this->actingAs($this->admin)->post(route('admin.transactions.store'), [
            'user' => $this->clientUser->id,
            'type' => 'earned',
            'data' => [
                [
                    'amount' => 125,
                    'fee' => 10,
                    'reason' => 'Custom Date Test',
                    'currency' => $this->clientUser->currency_id,
                    'created_at' => $customDate
                ]
            ]
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->clientUser->id,
            'type' => 'earned',
            'amount' => 125,
            'created_at' => $customDate,
        ]);

        $this->assertDatabaseHas('cost_transactions', [
            'user_id' => $this->clientUser->id,
            'amount' => 10,
            'created_at' => $customDate,
        ]);
    }
}
