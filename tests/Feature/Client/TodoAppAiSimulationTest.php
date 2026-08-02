<?php

namespace Tests\Feature\Client;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectComment;
use App\Models\Task;
use App\Models\Todo;
use App\Models\User;
use App\Services\AI\Tools\CreateInvoiceTool;
use App\Services\AI\Tools\CreateTodosTool;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoAppAiSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createClient(): User
    {
        $client = User::factory()->create([
            'onboarding_completed' => true,
        ]);
        $client->assignRole('client');
        return $client->fresh();
    }

    public function test_full_todo_app_project_lifecycle_simulation()
    {
        $client = $this->createClient();

        // Create initial Project
        $project = Project::create([
            'user_id'      => $client->id,
            'project_name' => 'Todo App Project',
            'status'       => 'open',
            'ai_enabled'   => true,
            'ai_context'   => [
                'goal'               => 'بناء تطبيق Todo بـ Laravel',
                'current_stage'      => 'greeting',
                'completed_features' => [],
                'pending_features'   => [],
            ],
        ]);

        // -------------------------------------------------------------
        // Step 1: Initial Client Idea Post
        // -------------------------------------------------------------
        $response1 = $this->actingAs($client)->post(route('client.projects.messages.store', $project), [
            'body' => 'سلام عليكم، عايز اعمل تطبيق Todo App بسيط باستخدام لارافل',
        ]);

        $response1->assertSuccessful();
        $this->assertDatabaseHas('project_comments', [
            'project_id' => $project->id,
            'body'       => 'سلام عليكم، عايز اعمل تطبيق Todo App بسيط باستخدام لارافل',
        ]);

        // Verify AI replied
        $commentsCount = ProjectComment::where('project_id', $project->id)->count();
        $this->assertGreaterThanOrEqual(2, $commentsCount);

        // -------------------------------------------------------------
        // Step 2: Native Tool Execution - Create Invoice Tool
        // -------------------------------------------------------------
        $invoiceTool = new CreateInvoiceTool();
        $invRes = $invoiceTool->execute($project, [
            'amount'      => 2500,
            'currency'    => 'EGP',
            'description' => 'تطوير تطبيق Todo App بسيط',
        ]);

        $this->assertEquals('success', $invRes['status']);
        $project->refresh();
        $invoice = Invoice::where('project_id', $project->id)->latest()->first();
        $this->assertNotNull($invoice, 'Invoice should be created via CreateInvoiceTool execution.');

        // -------------------------------------------------------------
        // Step 3: Native Tool Execution - Create Todos Tool
        // -------------------------------------------------------------
        $todosTool = new CreateTodosTool();
        $todosRes = $todosTool->execute($project, [
            'todos' => [
                [
                    'title'       => 'تصميم وقواعد بيانات المهام (Database Models)',
                    'description' => 'إنشاء جدول المهام والنماذج الخاصة به',
                    'priority'    => 'high',
                ],
                [
                    'title'       => 'بناء APIs التحكم والعمليات (Task CRUD Controllers)',
                    'description' => 'تطوير المتحكمات لإدارة المهام',
                    'priority'    => 'high',
                ],
            ],
        ]);

        $this->assertTrue($todosRes['success']);
        $project->refresh();

        // Verify Developer Tasks were created in database
        $tasksCount = Task::where('project_id', $project->id)->count();
        $this->assertEquals(2, $tasksCount, 'Developer tasks should be generated upon approval.');

        $todosCount = Todo::where('project_id', $project->id)->count();
        $this->assertEquals(2, $todosCount, 'Developer todos should be seeded.');

        // Inspect generated tasks to ensure they are high-level developer tasks without trivial setup steps
        $generatedTasks = Task::where('project_id', $project->id)->get();
        foreach ($generatedTasks as $t) {
            $taskName = mb_strtolower($t->task_name);
            $this->assertFalse(
                str_contains($taskName, 'composer require') || str_contains($taskName, 'npm install'),
                "Generated task should be a clean developer task, not a micro setup command like composer require. Found: {$t->task_name}"
            );
        }
    }
}
