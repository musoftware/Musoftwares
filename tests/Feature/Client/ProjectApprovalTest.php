<?php

namespace Tests\Feature\Client;

use App\Models\Currency;
use App\Models\Project;
use App\Models\ProjectBoardItem;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function makeClient(array $attrs = []): User
    {
        $client = User::factory()->create(array_merge(['onboarding_completed' => true], $attrs));
        $client->assignRole('client');

        return $client->fresh();
    }

    private function makeProject(User $client, array $attrs = []): Project
    {
        return Project::create(array_merge([
            'user_id' => $client->id,
            'project_name' => 'Test Project',
            'status' => 'open',
            'archived' => 0,
        ], $attrs));
    }

    public function test_client_can_approve_deliverable()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);

        $task = Task::create([
            'user_id' => $client->id,
            'project_id' => $project->id,
            'task_name' => 'Design Logo',
            'due_date' => '2026-08-01',
        ]);

        $boardItem = ProjectBoardItem::create([
            'project_id' => $project->id,
            'for_date' => '2026-08-01',
            'itemable_type' => Task::class,
            'itemable_id' => $task->id,
            'lane' => 'review',
        ]);

        $response = $this->actingAs($client)->post(route('client.projects.board.items.approval', [
            'project' => $project->id,
            'boardItem' => $boardItem->id,
        ]), [
            'client_approval_status' => 'approved',
        ]);

        $response->assertSuccessful();
        $response->assertJsonPath('client_approval_status', 'approved');

        $this->assertEquals('approved', $boardItem->fresh()->client_approval_status);
    }

    public function test_client_can_request_revision_and_creates_comment()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);

        $task = Task::create([
            'user_id' => $client->id,
            'project_id' => $project->id,
            'task_name' => 'Design Logo',
            'due_date' => '2026-08-01',
        ]);

        $boardItem = ProjectBoardItem::create([
            'project_id' => $project->id,
            'for_date' => '2026-08-01',
            'itemable_type' => Task::class,
            'itemable_id' => $task->id,
            'lane' => 'review',
        ]);

        $response = $this->actingAs($client)->post(route('client.projects.board.items.approval', [
            'project' => $project->id,
            'boardItem' => $boardItem->id,
        ]), [
            'client_approval_status' => 'revision_requested',
            'client_feedback' => 'Make the logo blue please.',
        ]);

        $response->assertSuccessful();
        $response->assertJsonPath('client_approval_status', 'revision_requested');
        $response->assertJsonPath('client_feedback', 'Make the logo blue please.');

        $this->assertEquals('revision_requested', $boardItem->fresh()->client_approval_status);
        $this->assertEquals('Make the logo blue please.', $boardItem->fresh()->client_feedback);

        // Verify comment was automatically posted
        $this->assertDatabaseHas('project_comments', [
            'project_id' => $project->id,
            'commentable_type' => Task::class,
            'commentable_id' => $task->id,
            'body' => '**Revision Requested:** Make the logo blue please.',
        ]);
    }

    public function test_other_client_cannot_approve_someone_elses_deliverable()
    {
        $owner = $this->makeClient();
        $intruder = $this->makeClient();
        $project = $this->makeProject($owner);

        $task = Task::create([
            'user_id' => $owner->id,
            'project_id' => $project->id,
            'task_name' => 'Design Logo',
            'due_date' => '2026-08-01',
        ]);

        $boardItem = ProjectBoardItem::create([
            'project_id' => $project->id,
            'for_date' => '2026-08-01',
            'itemable_type' => Task::class,
            'itemable_id' => $task->id,
            'lane' => 'review',
        ]);

        $response = $this->actingAs($intruder)->post(route('client.projects.board.items.approval', [
            'project' => $project->id,
            'boardItem' => $boardItem->id,
        ]), [
            'client_approval_status' => 'approved',
        ]);

        $response->assertStatus(403);
    }

    public function test_client_can_load_project_details_page_with_tickets()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);

        // Create a ticket for the user
        \App\Models\Ticket::create([
            'user_id' => $client->id,
            'ticket_subject' => 'Help with Logo',
            'ticket_message' => 'Please assist with logo design details.',
            'ticket_status' => 'open',
            'priority' => 'low',
        ]);

        $response = $this->actingAs($client)->get(route('client.projects.show', $project));

        $response->assertSuccessful();
    }
}
