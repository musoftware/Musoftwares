<?php

namespace Tests\Feature;

use App\Models\AdminSettings;
use App\Models\Project;
use App\Models\ProjectBoardItem;
use App\Models\ProjectBoardNote;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProjectBoardAiTest extends TestCase
{
    use RefreshDatabase;

    protected User $clientUser;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->clientUser = User::factory()->create([
            'onboarding_completed' => true,
            'currency_id' => 1,
        ]);
        $this->clientUser->assignRole('client');

        $this->project = Project::create([
            'user_id' => $this->clientUser->id,
            'project_name' => 'AI Test Project',
            'status' => 'open',
            'currency' => 1,
        ]);

        AdminSettings::SetValue('default_ai_model', 'openai');
        AdminSettings::SetValue('openai_api_key', 'test-key');
        AdminSettings::SetValue('openai_model', 'gpt-4o-mini');
    }

    public function test_generate_plan_distributes_tasks_with_eight_hour_limit_and_skips_fridays(): void
    {
        $mockJson = [
            'phases' => [
                [
                    'name' => 'Phase 1',
                    'scope' => 'Scope definition of Phase 1',
                    'items' => [
                        [
                            'type' => 'task',
                            'title' => 'Task 1',
                            'description' => 'Task 1 description',
                            'estimated_hours' => 6,
                            'is_important' => true,
                        ],
                        [
                            'type' => 'task',
                            'title' => 'Task 2',
                            'description' => 'Task 2 description',
                            'estimated_hours' => 4,
                            'is_important' => false,
                        ],
                    ]
                ]
            ]
        ];

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($mockJson),
                        ]
                    ]
                ]
            ])
        ]);

        // Thursday, July 9th, 2026.
        // Friday is July 10th.
        // Saturday is July 11th.
        $startDate = '2026-07-09';

        $response = $this->actingAs($this->clientUser)
            ->postJson(route('client.projects.board.add-with-ai', ['project' => $this->project->id]), [
                'prompt' => 'Create a captcha helper bot plan',
                'start_date' => $startDate,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);

        // Verification:
        // 1. Phase scope Note card must be on 2026-07-09.
        $scopeNote = ProjectBoardNote::where('title', 'Phase Scope: Phase 1')->first();
        $this->assertNotNull($scopeNote);
        $this->assertEquals('2026-07-09', $scopeNote->for_date);

        // 2. Task 1 (6 hours) must be on 2026-07-09.
        $task1 = Task::where('task_name', 'Task 1')->first();
        $this->assertNotNull($task1);
        $this->assertEquals('2026-07-09', $task1->due_date);

        // Task 1 board item is_important should be true
        $bi1 = ProjectBoardItem::where('itemable_type', Task::class)
            ->where('itemable_id', $task1->id)
            ->first();
        $this->assertNotNull($bi1);
        $this->assertTrue((bool)$bi1->is_important);
        $this->assertTrue((bool)$bi1->is_ai);

        // 3. Task 2 (4 hours) exceeds 8 hours on 2026-07-09.
        // It must skip Friday (2026-07-10) and land on Saturday (2026-07-11).
        $task2 = Task::where('task_name', 'Task 2')->first();
        $this->assertNotNull($task2);
        $this->assertEquals('2026-07-11', $task2->due_date);

        $bi2 = ProjectBoardItem::where('itemable_type', Task::class)
            ->where('itemable_id', $task2->id)
            ->first();
        $this->assertNotNull($bi2);
        $this->assertFalse((bool)$bi2->is_important);
        $this->assertTrue((bool)$bi2->is_ai);
    }

    public function test_adjust_future_items_handles_comment_mutations(): void
    {
        // 1. Setup Task 1 (AI) on 2026-07-09
        $task1 = Task::create([
            'project_id' => $this->project->id,
            'user_id' => $this->clientUser->id,
            'task_name' => 'Original Task 1',
            'task_description' => 'Original description 1',
            'due_date' => '2026-07-09',
        ]);
        $bi1 = ProjectBoardItem::create([
            'project_id' => $this->project->id,
            'for_date' => '2026-07-09',
            'itemable_type' => Task::class,
            'itemable_id' => $task1->id,
            'lane' => 'backlog',
            'is_ai' => true,
            'is_important' => false,
        ]);

        // 2. Setup Task 2 (AI) on 2026-07-11
        $task2 = Task::create([
            'project_id' => $this->project->id,
            'user_id' => $this->clientUser->id,
            'task_name' => 'Original Task 2',
            'task_description' => 'Original description 2',
            'due_date' => '2026-07-11',
        ]);
        $bi2 = ProjectBoardItem::create([
            'project_id' => $this->project->id,
            'for_date' => '2026-07-11',
            'itemable_type' => Task::class,
            'itemable_id' => $task2->id,
            'lane' => 'backlog',
            'is_ai' => true,
            'is_important' => false,
        ]);

        // Mock OpenAI response modifying Task 2
        $mockJson = [
            'timeline' => [
                [
                    'action' => 'modify',
                    'type' => 'task',
                    'id' => $bi2->id,
                    'title' => 'Task 2 Modified',
                    'description' => 'Task 2 Modified desc',
                    'estimated_hours' => 5,
                    'is_important' => true,
                ]
            ]
        ];

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($mockJson),
                        ]
                    ]
                ]
            ])
        ]);

        $response = $this->actingAs($this->clientUser)
            ->postJson(route('client.projects.comments.store', ['project' => $this->project->id]), [
                'type' => 'task',
                'commentable_id' => $task1->id,
                'body' => 'Please modify task 2 to make it important',
                'adjust_future_ai' => true,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('ai_adjusted', true);

        // Verification: Task 2 name and description should be modified
        $task2->refresh();
        $this->assertEquals('Task 2 Modified', $task2->task_name);
        $this->assertEquals('Task 2 Modified desc', $task2->task_description);

        $bi2->refresh();
        $this->assertTrue((bool)$bi2->is_important);
    }
}
