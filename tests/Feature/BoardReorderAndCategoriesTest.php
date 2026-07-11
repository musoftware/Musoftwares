<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureSubscriptionIsActive;
use App\Models\Project;
use App\Models\ProjectBoardCategory;
use App\Models\ProjectBoardItem;
use App\Models\ProjectBoardNote;
use App\Models\User;
use App\Services\ProjectBoardService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature coverage for the two board features:
 *  - drag-and-drop reordering (the new sort column + reorderCards endpoint);
 *  - per-project categories (CRUD + assigning + filter).
 *
 * These tests are intentionally end-to-end so that route/middleware/serializer interactions
 * are exercised together with the underlying models.
 */
class BoardReorderAndCategoriesTest extends TestCase
{
    use RefreshDatabase;

    protected User $clientUser;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureSubscriptionIsActive::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->clientUser = User::factory()->create([
            'onboarding_completed' => true,
            'currency_id' => 1,
        ]);
        $this->clientUser->assignRole('client');

        $this->project = Project::create([
            'user_id' => $this->clientUser->id,
            'project_name' => 'Reorder Test Project',
            'status' => 'open',
            'currency' => 1,
        ]);
    }

    public function test_sort_column_defaults_to_zero_for_existing_rows(): void
    {
        $note = ProjectBoardNote::create([
            'project_id' => $this->project->id,
            'author_id' => $this->clientUser->id,
            'for_date' => '2026-07-04',
            'title' => 'First',
            'content' => 'Legacy',
            'color' => 'yellow',
        ]);

        ProjectBoardItem::create([
            'project_id' => $this->project->id,
            'for_date' => '2026-07-04',
            'itemable_type' => ProjectBoardNote::class,
            'itemable_id' => $note->id,
            'lane' => 'backlog',
            'pos_x' => 24,
            'pos_y' => 24,
        ]);

        $placement = ProjectBoardItem::first();
        $this->assertNotNull($placement);
        $this->assertSame(0, (int) $placement->sort);
    }

    public function test_default_categories_are_seeded_once_per_project(): void
    {
        $service = app(ProjectBoardService::class);

        $first = $service->categoriesFor($this->project);
        $this->assertGreaterThanOrEqual(4, $first->count());

        $second = $service->categoriesFor($this->project);
        $this->assertSame($first->pluck('id')->all(), $second->pluck('id')->all());
    }

    public function test_card_can_be_assigned_a_category_via_move_endpoint(): void
    {
        $service = app(ProjectBoardService::class);
        $service->categoriesFor($this->project);
        $urgent = ProjectBoardCategory::where('project_id', $this->project->id)
            ->where('slug', 'urgent')
            ->firstOrFail();

        $note = ProjectBoardNote::create([
            'project_id' => $this->project->id,
            'author_id' => $this->clientUser->id,
            'for_date' => '2026-07-04',
            'title' => 'Urgent thing',
            'content' => '',
            'color' => 'rose',
        ]);

        // Seed a placement with sort=0 by inserting directly (mirrors the migration default).
        ProjectBoardItem::create([
            'project_id' => $this->project->id,
            'for_date' => '2026-07-04',
            'itemable_type' => ProjectBoardNote::class,
            'itemable_id' => $note->id,
            'lane' => 'backlog',
            'sort' => 0,
        ]);

        $response = $this->actingAs($this->clientUser)->post(route('client.projects.board.move-card', [
            'project' => $this->project->id,
        ]), [
            'for_date' => '2026-07-04',
            'type' => 'note',
            'id' => $note->id,
            'lane' => 'in_progress',
            'category_id' => $urgent->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true, 'category_id' => $urgent->id]);

        $placement = ProjectBoardItem::first();
        $this->assertSame('in_progress', $placement->lane);
        $this->assertSame($urgent->id, $placement->category_id);
    }

    public function test_reorder_endpoint_persists_new_sort_order(): void
    {
        $noteA = $this->makeNote('A');
        $noteB = $this->makeNote('B');
        $noteC = $this->makeNote('C');

        $notes = [$noteA, $noteB, $noteC];
        foreach (array_values($notes) as $i => $n) {
            ProjectBoardItem::create([
                'project_id' => $this->project->id,
                'for_date' => '2026-07-04',
                'itemable_type' => ProjectBoardNote::class,
                'itemable_id' => $n->id,
                'lane' => 'backlog',
                'sort' => $i,
            ]);
        }

        // Reverse the order.
        $response = $this->actingAs($this->clientUser)->post(route('client.projects.board.reorder-cards', [
            'project' => $this->project->id,
        ]), [
            'for_date' => '2026-07-04',
            'lane' => 'backlog',
            'order' => [
                ['type' => 'note', 'id' => $noteC->id],
                ['type' => 'note', 'id' => $noteB->id],
                ['type' => 'note', 'id' => $noteA->id],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true, 'count' => 3]);

        $placements = ProjectBoardItem::where('project_id', $this->project->id)
            ->where('for_date', '2026-07-04')
            ->where('lane', 'backlog')
            ->orderBy('sort')
            ->get();

        $this->assertSame([$noteC->id, $noteB->id, $noteA->id], $placements->pluck('itemable_id')->all());
    }

    public function test_admin_can_create_custom_category(): void
    {
        $admin = User::factory()->create(['onboarding_completed' => true, 'currency_id' => 1]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->postJson(route('admin.projects.board.categories.store', [
            'project' => $this->project->id,
        ]), [
            'name' => 'Blockers',
            'name_ar' => 'عوائق',
            'color' => 'fuchsia',
            'text_color' => 'fuchsia',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('project_board_categories', [
            'project_id' => $this->project->id,
            'slug' => 'blockers',
            'name' => 'Blockers',
            'name_ar' => 'عوائق',
            'is_system' => 0,
        ]);
    }

    public function test_admin_cannot_delete_a_system_category_but_can_clear_assignments(): void
    {
        $admin = User::factory()->create(['onboarding_completed' => true, 'currency_id' => 1]);
        $admin->assignRole('admin');

        $service = app(ProjectBoardService::class);
        $service->categoriesFor($this->project);
        $urgent = ProjectBoardCategory::where('project_id', $this->project->id)
            ->where('slug', 'urgent')
            ->firstOrFail();

        $note = $this->makeNote('Hot item');
        $placement = ProjectBoardItem::create([
            'project_id' => $this->project->id,
            'for_date' => '2026-07-04',
            'itemable_type' => ProjectBoardNote::class,
            'itemable_id' => $note->id,
            'lane' => 'backlog',
            'sort' => 0,
            'category_id' => $urgent->id,
        ]);
        $this->assertSame($urgent->id, $placement->category_id);

        $response = $this->actingAs($admin)->deleteJson(route('admin.projects.board.categories.destroy', [
            'project' => $this->project->id,
            'category' => $urgent->id,
        ]));

        $response->assertStatus(200);
        $response->assertJson(['ok' => true, 'cleared' => true]);
        $this->assertNotNull(ProjectBoardCategory::find($urgent->id), 'system category must remain');
        $this->assertNull($placement->fresh()->category_id, 'placements for a cleared category must drop their reference');
    }

    public function test_assigning_a_category_from_another_project_rejects_request(): void
    {
        $otherProject = Project::create([
            'user_id' => $this->clientUser->id,
            'project_name' => 'Other project',
            'status' => 'open',
            'currency' => 1,
        ]);
        $service = app(ProjectBoardService::class);
        $service->categoriesFor($otherProject);
        $foreignCategory = ProjectBoardCategory::where('project_id', $otherProject->id)
            ->where('slug', 'urgent')
            ->firstOrFail();

        $note = $this->makeNote('Cross-tenant attempt');
        ProjectBoardItem::create([
            'project_id' => $this->project->id,
            'for_date' => '2026-07-04',
            'itemable_type' => ProjectBoardNote::class,
            'itemable_id' => $note->id,
            'lane' => 'backlog',
            'sort' => 0,
        ]);

        $response = $this->actingAs($this->clientUser)->postJson(route('client.projects.board.move-card', [
            'project' => $this->project->id,
        ]), [
            'for_date' => '2026-07-04',
            'type' => 'note',
            'id' => $note->id,
            'lane' => 'backlog',
            'category_id' => $foreignCategory->id,
        ]);

        $response->assertStatus(422);
    }

    private function makeNote(string $title): ProjectBoardNote
    {
        return ProjectBoardNote::create([
            'project_id' => $this->project->id,
            'author_id' => $this->clientUser->id,
            'for_date' => '2026-07-04',
            'title' => $title,
            'content' => '',
            'color' => 'yellow',
        ]);
    }
}
