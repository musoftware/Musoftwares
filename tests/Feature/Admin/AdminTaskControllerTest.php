<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Task;
use App\Models\Todo;
use App\Models\Project;
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

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true, 'currency' => 2]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_access_tasks_as_list(): void
    {
        $response = $this->actingAs($this->admin)->get(route('tasks.as_list'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_tasks_as_list(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('tasks.as_list'));
        $response->assertStatus(403);
    }

    public function test_admin_can_complete_todo(): void
    {
        $todo = Todo::create([
            'user_id' => $this->clientUser->id,
            'title' => 'Test Todo',
            'completed' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('tasks.todos.complete', $todo->id), [
            'completed' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $this->assertTrue((bool)$todo->fresh()->completed);
    }

    public function test_admin_can_store_unpaid_todo(): void
    {
        $payload = [
            'title' => 'Unpaid Task',
        ];

        $response = $this->actingAs($this->admin)->post(route('tasks.client-tasks.store-unpaid', $this->clientUser->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $this->assertDatabaseHas('todos', [
            'user_id' => $this->clientUser->id,
            'title' => 'Unpaid Task',
            'is_paid' => false,
        ]);
    }

    public function test_store_unpaid_todo_validation(): void
    {
        $response = $this->actingAs($this->admin)->post(route('tasks.client-tasks.store-unpaid', $this->clientUser->id), [
            'title' => '',
        ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_admin_can_destroy_unpaid_todo(): void
    {
        $todo = Todo::create([
            'user_id' => $this->clientUser->id,
            'title' => 'Test Todo',
            'is_paid' => false,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('tasks.todos.destroy', $todo->id));

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_admin_cannot_destroy_paid_todo(): void
    {
        $todo = Todo::create([
            'user_id' => $this->clientUser->id,
            'title' => 'Test Todo',
            'is_paid' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('tasks.todos.destroy', $todo->id));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');

        $this->assertDatabaseHas('todos', ['id' => $todo->id]);
    }

    public function test_admin_can_access_calendar(): void
    {
        $response = $this->actingAs($this->admin)->get(route('tasks.calendar'));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_client_tasks(): void
    {
        $response = $this->actingAs($this->admin)->get(route('tasks.client-tasks', ['client_id' => $this->clientUser->id]));
        $response->assertStatus(200);
    }

    public function test_admin_can_refund_todo(): void
    {
        $todo = Todo::create([
            'user_id' => $this->clientUser->id,
            'title' => 'Test Todo',
            'is_paid' => true,
            'refunded' => false,
            'cost' => 100,
            // Setup an invalid slot to trigger immediate refund without time calculations
            'start_at' => null,
            'end_at' => null,
            'currency_id' => 2,
        ]);

        $response = $this->actingAs($this->admin)->post(route('tasks.todos.refund', $todo->id));

        $response->assertRedirect();
        $response->assertSessionHas('message');

        // Since it's an invalid slot refund, it gets deleted
        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_admin_can_schedule_todo(): void
    {
        $todo = Todo::create([
            'user_id' => $this->clientUser->id,
            'title' => 'Schedule Me',
        ]);

        $start = now()->addDay()->startOfHour();
        $end = $start->copy()->addHours(1);

        $payload = [
            'start_at' => $start->format('Y-m-d\TH:i'),
            'end_at' => $end->format('Y-m-d\TH:i'),
        ];

        $response = $this->actingAs($this->admin)->post(route('tasks.todos.schedule', $todo->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $todo->refresh();
        $this->assertNotNull($todo->start_at);
        $this->assertNotNull($todo->end_at);
    }

    public function test_admin_can_pay_and_schedule_todo(): void
    {
        // Add balance so user can afford the todo cost
        $this->clientUser->add_balance(5000, 'Test deposit', 'earned', 2);

        $todo = Todo::create([
            'user_id' => $this->clientUser->id,
            'title' => 'Pay and Schedule Me',
            'is_paid' => false,
            'cost' => 100,
            'currency_id' => 2,
        ]);

        $response = $this->actingAs($this->admin)->post(route('tasks.todos.pay-schedule', $todo->id));

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $this->assertTrue((bool)$todo->fresh()->is_paid);
    }
}
