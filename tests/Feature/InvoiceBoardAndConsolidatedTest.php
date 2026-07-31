<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureSubscriptionIsActive;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectBoardItem;
use App\Models\ProjectBoardNote;
use App\Models\Task;
use App\Models\Todo;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceBoardAndConsolidatedTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $clientUser;
    protected Project $project;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureSubscriptionIsActive::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->adminUser = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);
        $this->adminUser->assignRole('super_admin');

        $this->clientUser = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
            'currency_id' => 1,
        ]);
        $this->clientUser->assignRole('client');

        $this->project = Project::create([
            'user_id' => $this->clientUser->id,
            'project_name' => 'Invoice Board Test Project',
            'status' => 'open',
            'currency' => 1,
        ]);

        $this->invoice = Invoice::create([
            'user_id' => $this->clientUser->id,
            'project_id' => $this->project->id,
            'status' => 'unpaid',
            'currency_id' => 1,
        ]);
    }

    public function test_admin_can_create_note_associated_with_invoice(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.invoices.board.store-note', ['invoice' => $this->invoice->id]), [
            'title' => 'Test Note Title',
            'content' => 'Test Content here',
            'color' => 'yellow',
            'lane' => 'backlog',
        ]);

        $response->assertJson(['ok' => true]);
        $this->assertDatabaseHas('project_board_notes', [
            'invoice_id' => $this->invoice->id,
            'project_id' => $this->project->id,
            'title' => 'Test Note Title',
        ]);
    }

    public function test_admin_can_create_task_associated_with_invoice(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.invoices.board.store-task', ['invoice' => $this->invoice->id]), [
            'task_name' => 'Test Task Name',
            'task_description' => 'Test Description here',
            'priority' => 'high',
            'lane' => 'in_progress',
        ]);

        $response->assertJson(['ok' => true]);
        $this->assertDatabaseHas('tasks', [
            'invoice_id' => $this->invoice->id,
            'project_id' => $this->project->id,
            'task_name' => 'Test Task Name',
        ]);
    }

    public function test_admin_can_create_todo_associated_with_invoice(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.invoices.board.store-todo', ['invoice' => $this->invoice->id]), [
            'title' => 'Test Todo Title',
            'description' => 'Test Description here',
            'lane' => 'review',
        ]);

        $response->assertJson(['ok' => true]);
        $this->assertDatabaseHas('todos', [
            'invoice_id' => $this->invoice->id,
            'project_id' => $this->project->id,
            'title' => 'Test Todo Title',
        ]);
    }

    public function test_client_can_view_consolidated_board_active_projects(): void
    {
        $this->actingAs($this->clientUser);

        $note = ProjectBoardNote::create([
            'project_id' => $this->project->id,
            'invoice_id' => $this->invoice->id,
            'author_id' => $this->adminUser->id,
            'for_date' => '2026-07-31',
            'title' => 'Consolidated Item',
            'content' => 'Check me on the global board',
            'color' => 'green',
        ]);

        ProjectBoardItem::create([
            'project_id' => $this->project->id,
            'invoice_id' => $this->invoice->id,
            'for_date' => '2026-07-31',
            'itemable_type' => ProjectBoardNote::class,
            'itemable_id' => $note->id,
            'lane' => 'backlog',
        ]);

        // Get index page (should redirect to date route)
        $response = $this->get(route('client.projects.all-projects-board.index'));
        $response->assertRedirect();

        // Get date page
        $response = $this->get(route('client.projects.all-projects-board.date', ['date' => '2026-07-31']));
        $response->assertOk();
    }
}
