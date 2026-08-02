<?php

namespace Tests\Feature\Client;

use App\Models\Project;
use App\Models\ProjectBoardItem;
use App\Models\ProjectFile;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientProjectViewsTest extends TestCase
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
            'project_name' => 'Client Project X',
            'status' => 'open',
            'archived' => 0,
        ], $attrs));
    }

    public function test_client_can_view_aggregated_tasks()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);

        // Create a task
        Task::create([
            'user_id' => $client->id,
            'project_id' => $project->id,
            'task_name' => 'Aggregated Task Item',
            'due_date' => '2026-08-01',
        ]);

        $response = $this->actingAs($client)->get(route('client.projects.all-tasks'));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Client/Projects/TasksAggregator')
            ->has('items')
            ->has('projects')
        );
    }

    public function test_client_all_projects_board_index_redirects_to_date()
    {
        $client = $this->makeClient();
        $this->makeProject($client);

        $response = $this->actingAs($client)->get(route('client.projects.all-projects-board.index'));

        $response->assertRedirect();
    }

    public function test_client_can_view_all_projects_board_date()
    {
        $client = $this->makeClient();
        $this->makeProject($client);

        $response = $this->actingAs($client)->get(route('client.projects.all-projects-board.date', ['date' => '2026-08-01']));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Client/Projects/AllProjectsBoard')
            ->has('cards')
            ->has('lanes')
        );
    }

    public function test_client_project_calendar_index_redirects_to_date()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);

        $response = $this->actingAs($client)->get(route('client.projects.calendar.index', $project));

        $response->assertRedirect();
    }

    public function test_client_can_view_project_calendar_date()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);

        $response = $this->actingAs($client)->get(route('client.projects.calendar.date', [
            'project' => $project->id,
            'date' => '2026-08-01',
        ]));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Client/Projects/Calendar/Date')
            ->has('cards')
            ->has('lanes')
        );
    }

    public function test_client_can_download_project_file()
    {
        Storage::fake('local');
        $client = $this->makeClient();
        $project = $this->makeProject($client);

        // Create a dummy file on storage
        $uploaded = UploadedFile::fake()->create('mock_asset.pdf', 100);
        $path = Storage::disk('local')->putFileAs('project-files/' . $project->id, $uploaded, 'mock_asset.pdf');

        $projectFile = ProjectFile::create([
            'project_id' => $project->id,
            'user_id' => $client->id,
            'original_name' => 'mock_asset.pdf',
            'mime' => 'application/pdf',
            'size' => 100,
            'disk_path' => $path,
            'uploaded_by' => $client->id,
        ]);

        $response = $this->actingAs($client)->get(route('client.projects.files.download', [
            'project' => $project->id,
            'file' => $projectFile->id,
        ]));

        $response->assertSuccessful();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_client_can_retrieve_comments_list_for_item()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);

        $task = Task::create([
            'user_id' => $client->id,
            'project_id' => $project->id,
            'task_name' => 'Design Logo',
            'due_date' => '2026-08-01',
        ]);

        // Post a parent comment
        $comment = $task->comments()->create([
            'project_id' => $project->id,
            'author_id' => $client->id,
            'body' => 'Original request details',
        ]);

        // Post a child reply
        $reply = $task->comments()->create([
            'project_id' => $project->id,
            'author_id' => $client->id,
            'body' => 'Sure, we will review this!',
            'parent_id' => $comment->id,
        ]);

        $response = $this->actingAs($client)->get(route('client.projects.comments.index', [
            'project' => $project->id,
            'type' => 'task',
            'id' => $task->id,
        ]));

        $response->assertSuccessful();
        $response->assertJsonCount(2, 'comments');
        
        // Assert serialization contains parent_id
        $response->assertJsonFragment([
            'id' => $reply->id,
            'body' => 'Sure, we will review this!',
            'parent_id' => $comment->id,
        ]);
    }

    public function test_client_can_create_project_with_simple_form()
    {
        $client = $this->makeClient();

        $response = $this->actingAs($client)->get(route('client.projects.create-new'));
        $response->assertSuccessful();

        $response = $this->actingAs($client)->post(route('client.projects.store-new'), [
            'project_name' => 'Form Project',
            'description' => 'First message of project scope',
        ]);

        $project = Project::where('project_name', 'Form Project')->first();
        $this->assertNotNull($project);
        $this->assertEquals($client->id, $project->user_id);
        $this->assertFalse($project->ai_enabled);

        $response->assertRedirect(route('client.projects.show', $project->id));
        
        $comment = $project->comments()->where('commentable_type', Project::class)->first();
        $this->assertNotNull($comment);
        $this->assertEquals('First message of project scope', $comment->body);
    }

    public function test_client_can_activate_ai_with_egp_balance()
    {
        $client = $this->makeClient([
            'user_balance' => 20.0,
        ]);
        $project = $this->makeProject($client);

        \App\Models\Currency::firstOrCreate(['currency' => 'EGP'], [
            'currency' => 'EGP',
            'symbol' => 'EGP',
        ]);

        $response = $this->actingAs($client)->post(route('client.projects.ai.activate', $project));

        $response->assertSuccessful();
        $response->assertJson(['ok' => true]);

        $project->refresh();
        $this->assertTrue($project->ai_enabled);

        $client->refresh();
        $this->assertTrue($client->user_balance < 20.0);
    }

    public function test_client_cannot_activate_ai_with_insufficient_balance()
    {
        $client = $this->makeClient([
            'user_balance' => 0.0,
        ]);
        $project = $this->makeProject($client);

        \App\Models\Currency::firstOrCreate(['currency' => 'EGP'], [
            'currency' => 'EGP',
            'symbol' => 'EGP',
        ]);

        $response = $this->actingAs($client)->post(route('client.projects.ai.activate', $project));

        $response->assertStatus(422);
        $response->assertJson(['ok' => false, 'insufficient' => true]);
    }

    public function test_client_chat_comment_dispatches_ai_job()
    {
        \Illuminate\Support\Facades\Queue::fake();

        $client = $this->makeClient();
        $project = $this->makeProject($client, ['ai_enabled' => true]);

        $response = $this->actingAs($client)->post(route('client.projects.comments.store', $project), [
            'type' => 'project',
            'commentable_id' => $project->id,
            'body' => 'I need a contact form added.',
        ]);

        $response->assertSuccessful();
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ProcessClientMessageWithAi::class);
    }

    public function test_admin_daily_task_notification_command()
    {
        \App\Models\AdminSettings::SetValue('admin_work_days', '0,1,2,3,4,5,6');
        \App\Models\AdminSettings::SetValue('admin_work_start_time', '00:00');
        \App\Models\AdminSettings::SetValue('admin_work_end_time', '23:59');

        $client = $this->makeClient();
        $project = $this->makeProject($client, ['ai_enabled' => true]);

        Task::create([
            'user_id' => $client->id,
            'project_id' => $project->id,
            'task_name' => 'Pending Feature Task',
            'due_date' => '2026-08-01',
            'archived' => false,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $exitCode = $this->artisan('admin:daily-tasks-notification');
        $this->assertEquals(0, $exitCode);

        $this->assertTrue($admin->notifications()->count() > 0);
    }
}
