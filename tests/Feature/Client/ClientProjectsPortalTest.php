<?php

namespace Tests\Feature\Client;

use App\Models\Currency;
use App\Models\Project;
use App\Models\ProjectBoardItem;
use App\Models\ProjectReport;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientProjectsPortalTest extends TestCase
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

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        return $admin->fresh();
    }

    private function makeCurrency(string $code = 'USD'): Currency
    {
        return Currency::create([
            'currency' => $code,
            'symbol' => '$',
            'string_format' => '%s%.2f',
        ]);
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

    public function test_client_can_list_their_projects()
    {
        $client = $this->makeClient();
        $this->makeProject($client);

        $response = $this->actingAs($client)->get(route('client.projects.index'));

        $response->assertSuccessful();
    }

    public function test_other_client_cannot_view_someone_elses_project()
    {
        $owner = $this->makeClient();
        $intruder = $this->makeClient();
        $project = $this->makeProject($owner);

        $response = $this->actingAs($intruder)->get(route('client.projects.show', $project));

        $response->assertStatus(403);
    }

    public function test_get_currency_resolves_to_the_client_currency_id()
    {
        $currency = $this->makeCurrency('EUR');
        $client = $this->makeClient();
        $client->currency_id = $currency->id;
        $client->save();

        $project = $this->makeProject($client->fresh());

        $this->assertSame($currency->id, $project->fresh()->get_currency());
    }

    public function test_show_exposes_client_currency_on_the_project()
    {
        $currency = $this->makeCurrency('EUR');
        $client = $this->makeClient();
        $client->currency_id = $currency->id;
        $client->save();

        $project = $this->makeProject($client->fresh());

        $response = $this->actingAs($client)->get(route('client.projects.show', $project));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->where('project.currency.currency', 'EUR')
            ->etc());
    }

    public function test_tasks_list_hides_future_tasks_when_flag_enabled()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client, ['hide_future_tasks' => true]);

        Task::create([
            'task_name' => 'Past task',
            'project_id' => $project->id,
            'user_id' => $client->id,
            'due_date' => now()->subDay()->toDateString(),
        ]);
        Task::create([
            'task_name' => 'Future task',
            'project_id' => $project->id,
            'user_id' => $client->id,
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $response = $this->actingAs($client)->get(route('client.projects.tasks.index', $project));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->has('tasks', 1)
            ->missing('tasks.1')
            ->etc());
    }

    public function test_calendar_date_is_empty_for_a_future_date_when_gating_enabled()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client, ['hide_future_tasks' => true]);

        Task::create([
            'task_name' => 'Future task',
            'project_id' => $project->id,
            'user_id' => $client->id,
            'due_date' => now()->addWeek()->toDateString(),
        ]);

        $future = now()->addWeek()->toDateString();

        $response = $this->actingAs($client)->get(route('client.projects.calendar.date', ['project' => $project, 'date' => $future]));

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->where('hideFuture', true)
            ->has('cards', 0)
            ->etc());
    }

    public function test_scheduled_future_report_is_hidden_from_client()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);

        $future = ProjectReport::create([
            'project_id' => $project->id,
            'author_id' => $this->makeAdmin()->id,
            'title' => 'Scheduled',
            'body' => 'soon',
            'published_at' => now()->addWeek(),
        ]);
        $past = ProjectReport::create([
            'project_id' => $project->id,
            'author_id' => $this->makeAdmin()->id,
            'title' => 'Live',
            'body' => 'hello',
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($client)
            ->get(route('client.projects.reports.show', ['project' => $project, 'report' => $future]))
            ->assertNotFound();

        $this->actingAs($client)
            ->get(route('client.projects.reports.show', ['project' => $project, 'report' => $past]))
            ->assertSuccessful();
    }

    public function test_client_can_move_a_card_and_position_is_persisted()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $today = now()->toDateString();

        $task = Task::create([
            'task_name' => 'Move me',
            'project_id' => $project->id,
            'user_id' => $client->id,
            'due_date' => $today,
        ]);

        $response = $this->actingAs($client)->postJson(route('client.projects.board.move-card', $project), [
            'for_date' => $today,
            'type' => 'task',
            'id' => $task->id,
            'lane' => 'in_progress',
            'pos_x' => 120,
            'pos_y' => 80,
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('project_board_items', [
            'project_id' => $project->id,
            'for_date' => $today,
            'itemable_type' => Task::class,
            'itemable_id' => $task->id,
            'lane' => 'in_progress',
            'pos_x' => 120,
            'pos_y' => 80,
        ]);
    }

    public function test_card_move_is_rejected_for_other_clients_project()
    {
        $owner = $this->makeClient();
        $intruder = $this->makeClient();
        $project = $this->makeProject($owner);

        $response = $this->actingAs($intruder)->postJson(route('client.projects.board.move-card', $project), [
            'for_date' => now()->toDateString(),
            'type' => 'task',
            'id' => 1,
            'lane' => 'backlog',
        ]);

        $response->assertStatus(403);
    }

    public function test_client_can_add_a_sticky_note()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $today = now()->toDateString();

        $response = $this->actingAs($client)->postJson(route('client.projects.board.store-note', $project), [
            'for_date' => $today,
            'content' => 'Remember the milk',
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
            'color' => 'green',
        ]);

        $note = ProjectBoardItem::where('project_id', $project->id)->where('for_date', $today)->first();
        $this->assertNotNull($note);
    }

    public function test_admin_can_access_reports_management()
    {
        $admin = $this->makeAdmin();
        $project = $this->makeProject($this->makeClient());

        $this->actingAs($admin)
            ->get(route('admin.projects.reports.index', $project))
            ->assertSuccessful();
    }

    public function test_admin_can_store_a_scheduled_report()
    {
        $admin = $this->makeAdmin();
        $project = $this->makeProject($this->makeClient());

        $response = $this->actingAs($admin)
            ->post(route('admin.projects.reports.store', $project), [
                'title' => 'Weekly update',
                'type' => 'progress',
                'priority' => 'normal',
                'body' => '## Done',
                'published_at' => now()->addDay()->toDateTimeString(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_reports', [
            'project_id' => $project->id,
            'title' => 'Weekly update',
        ]);
    }

    public function test_client_can_comment_on_a_published_report()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $report = ProjectReport::create([
            'project_id' => $project->id,
            'author_id' => $this->makeAdmin()->id,
            'title' => 'Open report',
            'body' => 'hi',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($client)->postJson(route('client.projects.comments.store', $project), [
            'type' => 'report',
            'commentable_id' => $report->id,
            'body' => 'Looks great, thanks!',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('project_comments', [
            'project_id' => $project->id,
            'author_id' => $client->id,
            'commentable_type' => ProjectReport::class,
            'commentable_id' => $report->id,
            'body' => 'Looks great, thanks!',
        ]);
    }

    public function test_client_cannot_comment_on_an_unpublished_report()
    {
        $client = $this->makeClient();
        $project = $this->makeProject($client);
        $draft = ProjectReport::create([
            'project_id' => $project->id,
            'author_id' => $this->makeAdmin()->id,
            'title' => 'Draft',
            'body' => 'secret',
            'published_at' => null,
        ]);

        $this->actingAs($client)->postJson(route('client.projects.comments.store', $project), [
            'type' => 'report',
            'commentable_id' => $draft->id,
            'body' => 'sneaky',
        ])->assertStatus(422);

        $this->assertDatabaseMissing('project_comments', ['commentable_id' => $draft->id]);
    }
}
