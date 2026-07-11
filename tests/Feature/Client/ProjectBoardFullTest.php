<?php

namespace Tests\Feature\Client;

use App\Models\Project;
use App\Models\ProjectBoardItem;
use App\Models\ProjectBoardNote;
use App\Models\ProjectFile;
use App\Models\ProjectReport;
use App\Models\Task;
use App\Models\Todo;
use App\Models\TodoChecklistItem;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectBoardFullTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    private function makeClient(array $attrs = []): User
    {
        $client = User::factory()->create(array_merge(['onboarding_completed' => true], $attrs));
        $client->assignRole('client');

        return $client->fresh();
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        return $admin->fresh();
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

    // ───────── Note CRUD ─────────

    public function test_client_can_add_a_sticky_note()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $today = now()->toDateString();

        $response = $this->actingAs($client)->postJson(route('client.projects.board.store-note', $project), [
            'for_date' => $today,
            'content' => 'Hello board',
            'color' => 'green',
            'lane' => 'backlog',
            'pos_x' => 10,
            'pos_y' => 10,
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('project_board_notes', [
            'project_id' => $project->id,
            'author_id' => $client->id,
            'for_date' => $today,
            'content' => 'Hello board',
            'color' => 'green',
        ]);
        $this->assertDatabaseHas('project_board_items', [
            'project_id' => $project->id,
            'for_date' => $today,
            'itemable_type' => ProjectBoardNote::class,
            'lane' => 'backlog',
        ]);
    }

    public function test_client_can_update_a_sticky_note()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $today = now()->toDateString();

        $note = ProjectBoardNote::create([
            'project_id' => $project->id,
            'author_id' => $client->id,
            'for_date' => $today,
            'content' => 'Old',
            'color' => 'yellow',
        ]);
        ProjectBoardItem::create([
            'project_id' => $project->id,
            'for_date' => $today,
            'itemable_type' => ProjectBoardNote::class,
            'itemable_id' => $note->id,
            'lane' => 'backlog',
            'pos_x' => 0,
            'pos_y' => 0,
        ]);

        $response = $this->actingAs($client)->putJson(route('client.projects.board.update-note', [$project, $note]), [
            'content' => 'New content',
            'color' => 'purple',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('project_board_notes', [
            'id' => $note->id,
            'content' => 'New content',
            'color' => 'purple',
        ]);
    }

    public function test_client_can_delete_a_sticky_note()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $today = now()->toDateString();

        $note = ProjectBoardNote::create([
            'project_id' => $project->id,
            'author_id' => $client->id,
            'for_date' => $today,
            'content' => 'bye',
            'color' => 'yellow',
        ]);

        $response = $this->actingAs($client)->deleteJson(route('client.projects.board.destroy-note', [$project, $note]));

        $response->assertSuccessful();
        $this->assertDatabaseMissing('project_board_notes', ['id' => $note->id]);
    }

    public function test_sticky_note_rejects_other_clients_project()
    {
        $owner = $this->makeClient();
        $intruder = $this->makeClient();
        $project = $this->makeProject($owner);

        $response = $this->actingAs($intruder)->postJson(route('client.projects.board.store-note', $project), [
            'for_date' => now()->toDateString(),
            'content' => 'nope',
            'color' => 'green',
        ]);

        $response->assertStatus(403);
    }

    public function test_sticky_note_rejects_future_date_when_gating_enabled()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client, ['hide_future_tasks' => true]);

        $response = $this->actingAs($client)->postJson(route('client.projects.board.store-note', $project), [
            'for_date' => now()->addWeek()->toDateString(),
            'content' => 'future',
            'color' => 'green',
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_can_add_sticky_note_for_a_future_date_even_when_gating_enabled()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client, ['hide_future_tasks' => true]);
        $admin = $this->makeAdmin();
        $future = now()->addWeek()->toDateString();

        $response = $this->actingAs($admin)->postJson(route('client.projects.board.store-note', $project), [
            'for_date' => $future,
            'content' => 'admin future note',
            'color' => 'yellow',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('project_board_notes', [
            'project_id' => $project->id,
            'for_date' => $future,
            'content' => 'admin future note',
            'author_id' => $admin->id,
        ]);
    }

    // ───────── Task CRUD ─────────

    public function test_client_can_add_a_task()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $today = now()->toDateString();

        $response = $this->actingAs($client)->postJson(route('client.projects.board.store-task', $project), [
            'for_date' => $today,
            'task_name' => 'Spec the design',
            'task_description' => 'Look at references',
            'priority' => 'high',
            'lane' => 'backlog',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'task_name' => 'Spec the design',
            'priority' => 'high',
            'due_date' => $today,
        ]);
        $this->assertDatabaseHas('project_board_items', [
            'project_id' => $project->id,
            'for_date' => $today,
            'itemable_type' => Task::class,
        ]);
    }

    public function test_client_can_update_a_task()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $task = Task::create([
            'project_id' => $project->id,
            'user_id' => $client->id,
            'task_name' => 'Original',
            'task_description' => null,
            'priority' => 'normal',
            'due_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($client)->putJson(route('client.projects.board.update-task', [$project, $task]), [
            'task_name' => 'Updated',
            'task_description' => 'Now with detail',
            'priority' => 'urgent',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'task_name' => 'Updated',
            'priority' => 'urgent',
        ]);
    }

    public function test_client_can_delete_a_task()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $task = Task::create([
            'project_id' => $project->id,
            'user_id' => $client->id,
            'task_name' => 'Throw away',
            'due_date' => now()->toDateString(),
        ]);

        $this->actingAs($client)->deleteJson(route('client.projects.board.destroy-task', [$project, $task]))->assertSuccessful();
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    // ───────── Todo CRUD with checklist ─────────

    public function test_client_can_add_a_todo_without_checklist()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $today = now()->toDateString();

        $response = $this->actingAs($client)->postJson(route('client.projects.board.store-todo', $project), [
            'for_date' => $today,
            'title' => 'My first todo',
            'description' => 'desc',
            'lane' => 'backlog',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('todos', [
            'project_id' => $project->id,
            'user_id' => $client->id,
            'title' => 'My first todo',
            'inDate' => $today,
        ]);
    }

    public function test_client_can_add_a_todo_with_checklist_sub_items()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $today = now()->toDateString();

        $response = $this->actingAs($client)->postJson(route('client.projects.board.store-todo', $project), [
            'for_date' => $today,
            'title' => 'Checklist todo',
            'description' => 'desc',
            'checklist' => ['Buy milk', 'Pay invoice', 'Reply to email'],
            'lane' => 'backlog',
        ]);

        $response->assertSuccessful();
        $response->assertJsonPath('card.type', 'todo');
        $response->assertJsonPath('card.title', 'Checklist todo');

        $this->assertDatabaseHas('todos', [
            'project_id' => $project->id,
            'title' => 'Checklist todo',
        ]);

        $todo = Todo::where('project_id', $project->id)->where('title', 'Checklist todo')->firstOrFail();
        $this->assertDatabaseCount('todo_checklist_items', 3);
        $this->assertDatabaseHas('todo_checklist_items', ['todo_id' => $todo->id, 'title' => 'Buy milk', 'is_completed' => 0]);
        $this->assertDatabaseHas('todo_checklist_items', ['todo_id' => $todo->id, 'title' => 'Pay invoice', 'is_completed' => 0]);
        $this->assertDatabaseHas('todo_checklist_items', ['todo_id' => $todo->id, 'title' => 'Reply to email', 'is_completed' => 0]);
    }

    public function test_client_can_add_a_todo_with_blank_and_duplicated_checklist_entries()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $today = now()->toDateString();

        $response = $this->actingAs($client)->postJson(route('client.projects.board.store-todo', $project), [
            'for_date' => $today,
            'title' => 'Mixed',
            'checklist' => ['  ', '', 'real', 'real'],
        ]);

        $response->assertSuccessful();
        $todo = Todo::where('project_id', $project->id)->where('title', 'Mixed')->firstOrFail();
        $this->assertCount(2, $todo->checklistItems()->get());
    }

    public function test_client_can_update_a_todo_replacing_the_checklist()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $today = now()->toDateString();

        $todo = Todo::create([
            'project_id' => $project->id,
            'user_id' => $client->id,
            'title' => 'Initial',
            'description' => null,
            'inDate' => $today,
            'completed' => false,
            'priority' => 'normal',
            'priorityColor' => 'gray',
            'tags' => '[]',
            'paused' => false,
        ]);
        $todo->checklistItems()->create(['title' => 'old1', 'is_completed' => false]);
        $todo->checklistItems()->create(['title' => 'old2', 'is_completed' => false]);

        $response = $this->actingAs($client)->putJson(route('client.projects.board.update-todo', [$project, $todo]), [
            'title' => 'Updated',
            'description' => 'now',
            'completed' => true,
            'checklist' => [
                ['title' => 'new1', 'is_completed' => true],
                ['title' => 'new2', 'is_completed' => false],
            ],
        ]);

        $response->assertSuccessful();
        $todo->refresh();
        $this->assertSame('Updated', $todo->title);
        $this->assertTrue((bool) $todo->completed);
        $items = $todo->checklistItems()->get();
        $this->assertCount(2, $items);
        $this->assertEqualsCanonicalizing(['new1', 'new2'], $items->pluck('title')->toArray());
        $this->assertTrue((bool) $items->firstWhere('title', 'new1')->is_completed);
        $this->assertFalse((bool) $items->firstWhere('title', 'new2')->is_completed);
    }

    public function test_client_can_delete_a_todo_and_cascades_checklist()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $today = now()->toDateString();

        $todo = Todo::create([
            'project_id' => $project->id,
            'user_id' => $client->id,
            'title' => 'Bye',
            'inDate' => $today,
            'completed' => false,
            'priority' => 'normal',
            'priorityColor' => 'gray',
            'tags' => '[]',
            'paused' => false,
        ]);
        $todo->checklistItems()->create(['title' => 'a', 'is_completed' => false]);

        $this->actingAs($client)->deleteJson(route('client.projects.board.destroy-todo', [$project, $todo]))->assertSuccessful();
        $this->assertSoftDeleted('todos', ['id' => $todo->id]);
        $this->assertSoftDeleted('todo_checklist_items', ['todo_id' => $todo->id]);
    }

    public function test_todo_creation_requires_title_and_date()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);

        $response = $this->actingAs($client)->postJson(route('client.projects.board.store-todo', $project), [
            'description' => 'no title',
        ]);
        $response->assertStatus(422);

        $response = $this->actingAs($client)->postJson(route('client.projects.board.store-todo', $project), [
            'title' => 'No date',
        ]);
        $response->assertStatus(422);
    }

    public function test_todo_creation_rejects_other_clients_project()
    {
        $owner = $this->makeClient();
        $intruder = $this->makeClient();
        $project = $this->makeProject($owner);

        $response = $this->actingAs($intruder)->postJson(route('client.projects.board.store-todo', $project), [
            'for_date' => now()->toDateString(),
            'title' => 'steal',
        ]);
        $response->assertStatus(403);
    }

    // ───────── File CRUD ─────────

    public function test_client_can_upload_a_file()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $today = now()->toDateString();
        $upload = UploadedFile::fake()->create('brief.pdf', 32);

        $response = $this->actingAs($client)->postJson(route('client.projects.board.store-file', $project), [
            'file' => $upload,
            'for_date' => $today,
            'lane' => 'backlog',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('project_files', [
            'project_id' => $project->id,
            'uploaded_by' => $client->id,
            'original_name' => 'brief.pdf',
        ]);
        $this->assertDatabaseHas('project_board_items', [
            'project_id' => $project->id,
            'for_date' => $today,
            'itemable_type' => ProjectFile::class,
        ]);
    }

    public function test_client_can_delete_a_file()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);

        $file = ProjectFile::create([
            'project_id' => $project->id,
            'uploaded_by' => $client->id,
            'disk_path' => 'project-files/'.$project->id.'/brief.pdf',
            'original_name' => 'brief.pdf',
            'mime' => 'application/pdf',
            'size' => 1234,
        ]);

        $this->actingAs($client)->deleteJson(route('client.projects.board.destroy-file', [$project, $file]))->assertSuccessful();
        $this->assertDatabaseMissing('project_files', ['id' => $file->id]);
    }

    public function test_file_upload_rejects_too_large_files()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $upload = UploadedFile::fake()->create('huge.bin', 25 * 1024);

        $response = $this->actingAs($client)->postJson(route('client.projects.board.store-file', $project), [
            'file' => $upload,
            'for_date' => now()->toDateString(),
        ]);
        $response->assertStatus(422);
    }

    // ───────── Report CRUD ─────────

    public function test_client_can_add_a_report()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $today = now()->toDateString();

        $response = $this->actingAs($client)->postJson(route('client.projects.board.store-report', $project), [
            'for_date' => $today,
            'title' => 'Daily recap',
            'body' => 'Everything done.',
            'lane' => 'backlog',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('project_reports', [
            'project_id' => $project->id,
            'author_id' => $client->id,
            'title' => 'Daily recap',
        ]);
    }

    public function test_client_can_update_a_report()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $report = ProjectReport::create([
            'project_id' => $project->id,
            'author_id' => $client->id,
            'title' => 'Old',
            'body' => 'Old body',
        ]);

        $this->actingAs($client)->putJson(route('client.projects.board.update-report', [$project, $report]), [
            'title' => 'New',
            'body' => 'New body',
        ])->assertSuccessful();

        $this->assertDatabaseHas('project_reports', [
            'id' => $report->id,
            'title' => 'New',
            'body' => 'New body',
        ]);
    }

    public function test_client_can_delete_a_report()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $report = ProjectReport::create([
            'project_id' => $project->id,
            'author_id' => $client->id,
            'title' => 'bye',
            'body' => '...',
        ]);

        $this->actingAs($client)->deleteJson(route('client.projects.board.destroy-report', [$project, $report]))->assertSuccessful();
        $this->assertSoftDeleted('project_reports', ['id' => $report->id]);
    }

    // ───────── Move Card ─────────

    public function test_client_can_move_a_note()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $today = now()->toDateString();

        $note = ProjectBoardNote::create([
            'project_id' => $project->id,
            'author_id' => $client->id,
            'for_date' => $today,
            'content' => 'note',
            'color' => 'green',
        ]);

        $this->actingAs($client)->postJson(route('client.projects.board.move-card', $project), [
            'for_date' => $today,
            'type' => 'note',
            'id' => $note->id,
            'lane' => 'done',
            'pos_x' => 200,
            'pos_y' => 100,
        ])->assertSuccessful();

        $this->assertDatabaseHas('project_board_items', [
            'project_id' => $project->id,
            'itemable_type' => ProjectBoardNote::class,
            'itemable_id' => $note->id,
            'lane' => 'done',
            'pos_x' => 200,
            'pos_y' => 100,
        ]);
    }

    public function test_client_cannot_move_a_card_belonging_to_another_project()
    {
        $owner = $this->makeClient();
        $intruder = $this->makeClient();
        $ownerProject = $this->makeProject($owner);
        $intruderProject = $this->makeProject($intruder);
        $today = now()->toDateString();

        $foreignTask = Task::create([
            'project_id' => $ownerProject->id,
            'user_id' => $owner->id,
            'task_name' => 'foreign',
            'due_date' => $today,
        ]);

        $this->actingAs($intruder)->postJson(route('client.projects.board.move-card', $intruderProject), [
            'for_date' => $today,
            'type' => 'task',
            'id' => $foreignTask->id,
            'lane' => 'done',
        ])->assertStatus(422);
    }

    // ───────── Bring Undone ─────────

    public function test_client_can_bring_undone_todos_forward()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $past = now()->subDays(2)->toDateString();
        $today = now()->toDateString();

        $todo = Todo::create([
            'project_id' => $project->id,
            'user_id' => $client->id,
            'title' => 'Pending',
            'description' => null,
            'completed' => false,
            'inDate' => $past,
            'priority' => 'normal',
            'priorityColor' => 'gray',
            'tags' => '[]',
            'paused' => false,
        ]);
        $todo->checklistItems()->create(['title' => 'sub', 'is_completed' => false]);
        ProjectBoardItem::create([
            'project_id' => $project->id,
            'for_date' => $past,
            'itemable_type' => Todo::class,
            'itemable_id' => $todo->id,
            'lane' => 'backlog',
            'pos_x' => 0,
            'pos_y' => 0,
        ]);

        $response = $this->actingAs($client)->postJson(route('client.projects.board.bring-undone', $project), [
            'for_date' => $today,
        ]);

        $response->assertSuccessful();
        $this->assertSame(1, Todo::where('project_id', $project->id)->where('inDate', $today)->count());
    }

    public function test_bring_undone_does_not_duplicate_when_already_present()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $past = now()->subDays(2)->toDateString();
        $today = now()->toDateString();

        $todo = Todo::create([
            'project_id' => $project->id,
            'user_id' => $client->id,
            'title' => 'Pending',
            'inDate' => $past,
            'completed' => false,
            'priority' => 'normal',
            'priorityColor' => 'gray',
            'tags' => '[]',
            'paused' => false,
        ]);
        ProjectBoardItem::create([
            'project_id' => $project->id,
            'for_date' => $past,
            'itemable_type' => Todo::class,
            'itemable_id' => $todo->id,
            'lane' => 'backlog',
            'pos_x' => 0,
            'pos_y' => 0,
        ]);
        $todayTodo = Todo::create([
            'project_id' => $project->id,
            'user_id' => $client->id,
            'title' => 'Pending',
            'inDate' => $today,
            'completed' => false,
            'priority' => 'normal',
            'priorityColor' => 'gray',
            'tags' => '[]',
            'paused' => false,
        ]);
        ProjectBoardItem::create([
            'project_id' => $project->id,
            'for_date' => $today,
            'itemable_type' => Todo::class,
            'itemable_id' => $todayTodo->id,
            'lane' => 'backlog',
            'pos_x' => 0,
            'pos_y' => 0,
        ]);

        $this->actingAs($client)->postJson(route('client.projects.board.bring-undone', $project), [
            'for_date' => $today,
        ])->assertSuccessful();

        $this->assertSame(1, Todo::where('project_id', $project->id)->where('inDate', $today)->count());
    }

    public function test_bring_undone_skips_completed_todos()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $past = now()->subDays(2)->toDateString();
        $today = now()->toDateString();

        $todo = Todo::create([
            'project_id' => $project->id,
            'user_id' => $client->id,
            'title' => 'Already done',
            'inDate' => $past,
            'completed' => true,
            'priority' => 'normal',
            'priorityColor' => 'gray',
            'tags' => '[]',
            'paused' => false,
        ]);
        ProjectBoardItem::create([
            'project_id' => $project->id,
            'for_date' => $past,
            'itemable_type' => Todo::class,
            'itemable_id' => $todo->id,
            'lane' => 'done',
            'pos_x' => 0,
            'pos_y' => 0,
        ]);

        $this->actingAs($client)->postJson(route('client.projects.board.bring-undone', $project), [
            'for_date' => $today,
        ])->assertSuccessful();

        $this->assertSame(0, Todo::where('project_id', $project->id)->where('inDate', $today)->count());
    }

    // ───────── Admin can mutate board on behalf of clients ─────────

    public function test_admin_can_add_a_todo_with_checklist_to_any_project()
    {
        $client = $this->makeClient();
        $admin = $this->makeAdmin();
        $project = $this->makeProject($client);
        $today = now()->toDateString();

        $response = $this->actingAs($admin)->postJson(route('client.projects.board.store-todo', $project), [
            'for_date' => $today,
            'title' => 'From admin',
            'checklist' => ['step A', 'step B'],
        ]);

        $response->assertSuccessful();
        $todo = Todo::where('project_id', $project->id)->where('title', 'From admin')->firstOrFail();
        $this->assertCount(2, $todo->checklistItems()->get());
    }

    // ───────── Validation gates ─────────

    public function test_store_todo_validates_for_date_format()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);

        $this->actingAs($client)->postJson(route('client.projects.board.store-todo', $project), [
            'for_date' => '07/02/2026',
            'title' => 'bad',
        ])->assertStatus(422);
    }

    // ───────── Reschedule (admin-only) ─────────

    public function test_admin_can_reschedule_a_task_to_another_day()
    {
        $client = $this->makeClient();
        $admin = $this->makeAdmin();
        $project = $this->makeProject($client);
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        $task = $project->tasks()->create([
            'user_id' => $client->id,
            'task_name' => 'Move me',
            'due_date' => $today,
        ]);
        $project->boardItems()->create([
            'for_date' => $today,
            'itemable_type' => Task::class,
            'itemable_id' => $task->id,
            'lane' => 'backlog',
            'pos_x' => 0,
            'pos_y' => 0,
        ]);

        $response = $this->actingAs($admin)->postJson(
            route('client.projects.board.reschedule-card', $project),
            ['for_date' => $tomorrow, 'type' => 'task', 'id' => $task->id],
        );

        $response->assertSuccessful();
        $fresh = $task->fresh();
        $this->assertSame(
            $tomorrow,
            is_string($fresh->due_date) ? $fresh->due_date : $fresh->due_date->toDateString(),
        );
        $this->assertDatabaseHas('project_board_items', [
            'project_id' => $project->id,
            'itemable_type' => Task::class,
            'itemable_id' => $task->id,
            'for_date' => $tomorrow,
        ]);
    }

    public function test_client_cannot_reschedule_a_card()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        $task = $project->tasks()->create([
            'user_id' => $client->id,
            'task_name' => 'Mine',
            'due_date' => $today,
        ]);

        $this->actingAs($client)->postJson(
            route('client.projects.board.reschedule-card', $project),
            ['for_date' => $tomorrow, 'type' => 'task', 'id' => $task->id],
        )->assertStatus(403);

        $fresh = $task->fresh();
        $this->assertSame(
            $today,
            is_string($fresh->due_date) ? $fresh->due_date : $fresh->due_date->toDateString(),
        );
    }

    public function test_reschedule_rejects_file_type()
    {
        $client = $this->makeClient();
        $admin = $this->makeAdmin();
        $project = $this->makeProject($client);
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        $file = ProjectFile::create([
            'project_id' => $project->id,
            'uploaded_by' => $admin->id,
            'disk_path' => 'fake/path.txt',
            'original_name' => 'x.txt',
            'mime' => 'text/plain',
            'size' => 1,
        ]);

        $this->actingAs($admin)->postJson(
            route('client.projects.board.reschedule-card', $project),
            ['for_date' => $tomorrow, 'type' => 'file', 'id' => $file->id],
        )->assertStatus(422);
    }

    public function test_admin_can_reschedule_a_todo_to_another_day_and_it_leaves_today()
    {
        $client = $this->makeClient();
        $admin = $this->makeAdmin();
        $project = $this->makeProject($client);
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        $todo = $project->todos()->create([
            'user_id' => $client->id,
            'title' => 'Reschedule me',
            'inDate' => $today,
            'completed' => false,
            'paused' => false,
            'priority' => 'normal',
            'priorityColor' => 'gray',
            'tags' => [],
        ]);
        $project->boardItems()->create([
            'for_date' => $today,
            'itemable_type' => Todo::class,
            'itemable_id' => $todo->id,
            'lane' => 'backlog',
            'pos_x' => 0,
            'pos_y' => 0,
        ]);

        $todayCards = collect(
            app(\App\Services\ProjectBoardService::class)->cardsForDate($project, now(), applyFutureGating: false),
        );
        $this->assertTrue(
            $todayCards->contains(fn ($c) => $c['type'] === 'todo' && $c['id'] === $todo->id),
            'Todo should appear on today\'s board before reschedule.',
        );

        $this->actingAs($admin)->postJson(
            route('client.projects.board.reschedule-card', $project),
            ['for_date' => $tomorrow, 'type' => 'todo', 'id' => $todo->id],
        )->assertSuccessful();

        $fresh = $todo->fresh();
        $this->assertSame(
            $tomorrow,
            is_string($fresh->inDate) ? $fresh->inDate : $fresh->inDate->toDateString(),
        );

        $todayCardsAfter = collect(
            app(\App\Services\ProjectBoardService::class)->cardsForDate($project, now(), applyFutureGating: false),
        );
        $this->assertFalse(
            $todayCardsAfter->contains(fn ($c) => $c['type'] === 'todo' && $c['id'] === $todo->id),
            'Todo should NOT reappear on today\'s board after being rescheduled to tomorrow.',
        );

        $tomorrowCards = collect(
            app(\App\Services\ProjectBoardService::class)->cardsForDate($project, now()->addDay(), applyFutureGating: false),
        );
        $this->assertTrue(
            $tomorrowCards->contains(fn ($c) => $c['type'] === 'todo' && $c['id'] === $todo->id),
            'Todo should appear on tomorrow\'s board after reschedule.',
        );
    }
}