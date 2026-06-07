<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\CharityCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharityCounterControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_charity_counter_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.charity-counter.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_charity_counter_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.charity-counter.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_add_amount(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.charity-counter.add-amount'), [
            'amount' => 100,
            'description' => 'Donation',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('charity_transactions', [
            'user_id' => $this->admin->id,
            'type' => 'credit',
            'amount' => 100,
            'description' => 'Donation',
            'reference_type' => 'admin_add',
        ]);
    }

    public function test_admin_can_subtract_amount(): void
    {
        // First we add some global balance so we don't throw an exception (assuming it checks balance).
        CharityCounter::addToGlobalCounterByAdmin(200, 'Initial', $this->admin->id);

        $response = $this->actingAs($this->admin)->post(route('admin.charity-counter.subtract-amount'), [
            'amount' => 50,
            'description' => 'Used for charity',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('charity_transactions', [
            'user_id' => $this->admin->id,
            'type' => 'debit',
            'amount' => 50,
            'description' => 'Used for charity',
            'reference_type' => 'admin_subtract',
        ]);
    }
}
