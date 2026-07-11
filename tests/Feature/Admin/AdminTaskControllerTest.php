<?php

namespace Tests\Feature\Admin;

use App\Models\Todo;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTaskControllerTest extends TestCase
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

        $this->clientUser = User::factory()->create(['onboarding_completed' => true, 'currency' => 2]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_access_tasks_as_list(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.tasks.as_list'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_tasks_as_list(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.tasks.as_list'));
        $response->assertStatus(403);
    }

    public function test_admin_can_complete_todo(): void
    {
        $todo = Todo::create([
            'user_id' => $this->clientUser->id,
            'title' => 'Test Todo',
            'completed' => false,
            'inDate' => date('Y-m-d'),
            'priority' => 'normal',
            'priorityColor' => 'gray',
            'paused' => false,
            'tags' => json_encode([]),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.tasks.todos.complete', $todo->id), [
            'completed' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $this->assertTrue((bool) $todo->fresh()->completed);
    }

    public function test_admin_can_access_calendar(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.tasks.calendar'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_client_tasks(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.tasks.client-tasks', ['client_id' => $this->clientUser->id]));
        $response->assertStatus(200);
    }

    public function test_admin_can_schedule_todo(): void
    {
        $todo = Todo::create([
            'user_id' => $this->clientUser->id,
            'title' => 'Schedule Me',
            'completed' => false,
            'inDate' => date('Y-m-d'),
            'priority' => 'normal',
            'priorityColor' => 'gray',
            'paused' => false,
            'tags' => json_encode([]),
        ]);

        $start = now()->addDay()->startOfHour();
        $end = $start->copy()->addHours(1);

        $payload = [
            'start_at' => $start->format('Y-m-d\TH:i'),
            'end_at' => $end->format('Y-m-d\TH:i'),
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.tasks.todos.schedule', $todo->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $todo->refresh();
        $this->assertNotNull($todo->start_at);
        $this->assertNotNull($todo->end_at);
    }
}
