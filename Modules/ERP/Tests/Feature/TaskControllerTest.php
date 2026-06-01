<?php

namespace Modules\ERP\Tests\Feature;

use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\ERPTask;
use Modules\ERP\Models\ERPTodoItem;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function createUserWithSubscription()
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Acme Corp', 'status' => 'active']);
        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp-tasks',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
        return [$user, $tenant];
    }

    public function test_task_index_loads()
    {
        [$user, $tenant] = $this->createUserWithSubscription();
        $client = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'John', 'email' => 'john@test.com', 'currency_id' => 1]);
        ERPTask::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'task_name' => 'Design Logo',
            'created_by' => $user->id,
            'status' => 'open'
        ]);

        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->get('/erp/tasks');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ERP/Tasks/Index')
            ->has('tasks')
        );
    }

    public function test_task_store_creates_task()
    {
        [$user, $tenant] = $this->createUserWithSubscription();

        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->post('/erp/tasks', [
            'task_name' => 'Build Website',
            'status' => 'open',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('erp_tasks', [
            'tenant_id' => $tenant->id,
            'task_name' => 'Build Website',
            'status' => 'open',
        ]);
    }

    public function test_task_store_item_creates_todo()
    {
        [$user, $tenant] = $this->createUserWithSubscription();
        $task = ERPTask::create([
            'tenant_id' => $tenant->id,
            'task_name' => 'Design Logo',
            'created_by' => $user->id,
            'status' => 'open'
        ]);

        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->post("/erp/tasks/{$task->id}/items", [
            'title' => 'First Draft',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('erp_todo_items', [
            'tenant_id' => $tenant->id,
            'task_id' => $task->id,
            'title' => 'First Draft',
        ]);
    }
}
