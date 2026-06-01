<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTaskControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
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

    public function test_admin_can_access_tasks_as_list()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.tasks.as_list'));

        $response->assertSuccessful();
    }

    public function test_non_admin_cannot_access_tasks_as_list()
    {
        $client = $this->createClient();

        $response = $this->actingAs($client)->get(route('admin.tasks.as_list'));

        $response->assertStatus(403);
    }

    public function test_admin_can_access_client_tasks()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.tasks.client-tasks'));

        $response->assertSuccessful();
    }

    public function test_admin_can_store_and_bill_client_todo()
    {
        $admin = $this->createAdmin();
        $client = User::factory()->create(['currency_id' => 1, 'booking_rate' => 0, 'onboarding_completed' => true]);
        $client->assignRole('client');

        $client->add_balance(5000, 'Test balance', 'earned', $client->currency);

        $start = now('Africa/Cairo')->addDays(1)->startOfHour();
        $end = clone $start;
        $end->addHours(2); // 2 hours duration

        $payload = [
            'title' => 'Test Focus Task',
            'start_at' => $start->toDateTimeString(),
            'end_at' => $end->toDateTimeString(),
            'description' => 'Test focus task description.',
        ];

        $response = $this->actingAs($admin)->post(route('admin.tasks.client-tasks.store', $client->id), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);
        
        $this->assertDatabaseHas('todos', [
            'user_id' => $client->id,
            'title' => 'Test Focus Task',
        ]);
    }
}
