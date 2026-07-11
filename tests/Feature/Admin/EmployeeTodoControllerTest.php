<?php

namespace Tests\Feature\Admin;

use App\Models\EmployeeTodo;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTodoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->employee = User::factory()->create(['onboarding_completed' => true]);
        $this->employee->assignRole('moderator'); // Assuming moderator acts as an employee
    }

    public function test_admin_can_view_employee_todos_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.employee-todos.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_employee_todos_index(): void
    {
        // For example, a regular client
        $client = User::factory()->create(['onboarding_completed' => true]);
        $client->assignRole('client');

        $response = $this->actingAs($client)->get(route('admin.employee-todos.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_store_employee_todo(): void
    {
        $payload = [
            'user_id' => $this->employee->id,
            'title' => 'New Task',
            'description' => 'Task description',
            'priority' => 'high',
            'recurring' => 'day',
            'recurring_times' => 1,
            'current_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.employee-todos.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employee_todos', [
            'user_id' => $this->employee->id,
            'title' => 'New Task',
        ]);
    }

    public function test_store_employee_todo_validation(): void
    {
        $payload = [
            'user_id' => $this->employee->id,
            'title' => '', // title is required
            'priority' => 'high',
            'recurring' => 'day',
            'recurring_times' => 1,
            'current_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.employee-todos.store'), $payload);
        $response->assertSessionHasErrors('title');
    }

    public function test_admin_can_update_employee_todo(): void
    {
        $todo = EmployeeTodo::create([
            'user_id' => $this->employee->id,
            'title' => 'Old Task',
            'priority' => 'low',
            'recurring' => 'day',
            'recurring_times' => 1,
            'current_date' => now()->format('Y-m-d'),
        ]);

        $payload = [
            'user_id' => $this->employee->id,
            'title' => 'Updated Task',
            'description' => 'Updated desc',
            'priority' => 'high',
            'recurring' => 'week',
            'recurring_times' => 2,
            'current_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.employee-todos.update', $todo->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('Updated Task', $todo->fresh()->title);
        $this->assertEquals('high', $todo->fresh()->priority);
    }

    public function test_update_employee_todo_validation(): void
    {
        $todo = EmployeeTodo::create([
            'user_id' => $this->employee->id,
            'title' => 'Old Task',
            'priority' => 'low',
            'recurring' => 'day',
            'recurring_times' => 1,
            'current_date' => now()->format('Y-m-d'),
        ]);

        $payload = [
            'user_id' => $this->employee->id,
            'title' => 'Updated Task',
            'priority' => 'invalid_priority',
            'recurring' => 'day',
            'recurring_times' => 1,
            'current_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.employee-todos.update', $todo->id), $payload);
        $response->assertSessionHasErrors('priority');
    }

    public function test_admin_can_delete_employee_todo(): void
    {
        $todo = EmployeeTodo::create([
            'user_id' => $this->employee->id,
            'title' => 'Task to delete',
            'priority' => 'low',
            'recurring' => 'day',
            'recurring_times' => 1,
            'current_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.employee-todos.destroy', $todo->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('employee_todos', ['id' => $todo->id]);
    }
}
