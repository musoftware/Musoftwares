<?php

namespace Tests\Feature\Admin;

use App\Models\Project;
use App\Models\ProjectBoardNote;
use App\Models\ProjectBoardPreference;
use App\Models\User;
use App\Services\ProjectBoardService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectBoardPreferencesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->project = Project::create([
            'user_id' => $this->admin->id,
            'project_name' => 'Preference Board',
            'share_token' => 'pref-token-1234567890-abcdef',
        ]);
    }

    public function test_board_view_passes_default_preferences_when_none_saved(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.projects.board', ['project' => $this->project->id, 'date' => '2026-07-01']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('preferences.view_mode', 'cards')
            ->where('preferences.sort_by', 'manual')
            ->where('preferences.sort_dir', 'asc')
        );
    }

    public function test_admin_can_update_board_preferences(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson(route('admin.projects.board.preferences.update', $this->project), [
                'view_mode' => 'table',
                'sort_by' => 'title',
                'sort_dir' => 'desc',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('preferences.view_mode', 'table');
        $response->assertJsonPath('preferences.sort_by', 'title');
        $response->assertJsonPath('preferences.sort_dir', 'desc');

        $row = ProjectBoardPreference::query()
            ->where('user_id', $this->admin->id)
            ->where('project_id', $this->project->id)
            ->first();
        $this->assertNotNull($row);
        $this->assertSame('table', $row->view_mode);
        $this->assertSame('title', $row->sort_by);
        $this->assertSame('desc', $row->sort_dir);
    }

    public function test_invalid_preference_values_are_rejected(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson(route('admin.projects.board.preferences.update', $this->project), [
                'view_mode' => 'banana',
            ]);

        $response->assertStatus(422);
    }

    public function test_saved_preferences_are_returned_on_next_board_load(): void
    {
        $this->actingAs($this->admin)
            ->putJson(route('admin.projects.board.preferences.update', $this->project), [
                'view_mode' => 'grid',
                'sort_by' => 'priority',
                'sort_dir' => 'desc',
            ])->assertStatus(200);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.projects.board', ['project' => $this->project->id, 'date' => '2026-07-01']));

        $response->assertInertia(fn ($page) => $page
            ->where('preferences.view_mode', 'grid')
            ->where('preferences.sort_by', 'priority')
            ->where('preferences.sort_dir', 'desc')
        );
    }

    public function test_apply_sort_by_title_uses_alphabetical_order(): void
    {
        $service = app(ProjectBoardService::class);

        $cards = [
            $this->makeCard('note', 1, 'Banana'),
            $this->makeCard('note', 2, 'Apple'),
            $this->makeCard('note', 3, 'Cherry'),
        ];

        $asc = $service->applySort($cards, 'title', 'asc');
        $this->assertSame(['Apple', 'Banana', 'Cherry'], array_column($asc, 'title'));

        $desc = $service->applySort($cards, 'title', 'desc');
        $this->assertSame(['Cherry', 'Banana', 'Apple'], array_column($desc, 'title'));
    }

    public function test_apply_sort_by_priority_groups_urgent_first(): void
    {
        $service = app(ProjectBoardService::class);

        $cards = [
            $this->makeCard('task', 1, 'B', priority: 'low'),
            $this->makeCard('task', 2, 'A', priority: 'urgent'),
            $this->makeCard('task', 3, 'C', priority: 'normal'),
        ];

        $sorted = $service->applySort($cards, 'priority', 'asc');
        $this->assertSame(['A', 'C', 'B'], array_column($sorted, 'title'));
    }

    public function test_apply_sort_by_lane_groups_by_status(): void
    {
        $service = app(ProjectBoardService::class);

        $cards = [
            $this->makeCard('task', 1, 'A', lane: 'done'),
            $this->makeCard('task', 2, 'B', lane: 'backlog'),
            $this->makeCard('task', 3, 'C', lane: 'in_progress'),
        ];

        $sorted = $service->applySort($cards, 'lane', 'asc');
        $this->assertSame(['backlog', 'in_progress', 'done'], array_column($sorted, 'lane'));
    }

    public function test_apply_sort_by_category_pushes_nulls_last(): void
    {
        $service = app(ProjectBoardService::class);

        $cards = [
            $this->makeCard('task', 1, 'A', categoryId: null),
            $this->makeCard('task', 2, 'B', categoryId: 5),
            $this->makeCard('task', 3, 'C', categoryId: null),
            $this->makeCard('task', 4, 'D', categoryId: 2),
        ];

        $asc = $service->applySort($cards, 'category', 'asc');
        $this->assertSame([2, 5, null, null], array_column($asc, 'category_id'));

        $desc = $service->applySort($cards, 'category', 'desc');
        $this->assertSame([5, 2, null, null], array_column($desc, 'category_id'));
    }

    public function test_apply_sort_manual_returns_input_untouched(): void
    {
        $service = app(ProjectBoardService::class);

        $cards = [
            $this->makeCard('task', 1, 'Z', sort: 99),
            $this->makeCard('task', 2, 'A', sort: 1),
        ];

        $sorted = $service->applySort($cards, 'manual', 'asc');
        $this->assertSame(['Z', 'A'], array_column($sorted, 'title'));
    }

    public function test_cards_for_date_applies_user_sort_preference(): void
    {
        $today = Carbon::parse('2026-07-01');

        ProjectBoardNote::create([
            'project_id' => $this->project->id,
            'title' => 'Banana',
            'content' => null,
            'for_date' => $today->toDateString(),
            'color' => 'yellow',
        ]);
        ProjectBoardNote::create([
            'project_id' => $this->project->id,
            'title' => 'Apple',
            'content' => null,
            'for_date' => $today->toDateString(),
            'color' => 'yellow',
        ]);

        $service = app(ProjectBoardService::class);

        $sorted = $service->cardsForDate($this->project, $today, applyFutureGating: false, preferences: [
            'view_mode' => 'cards',
            'sort_by' => 'title',
            'sort_dir' => 'asc',
        ]);

        $this->assertSame(['Apple', 'Banana'], array_column($sorted, 'title'));
    }

    /**
     * Build a synthetic card for sorting tests. Mirrors the shape that
     * `ProjectBoardService::buildCards()` would produce for a real DB row.
     */
    private function makeCard(string $type, int $id, string $title, string $lane = 'backlog', ?string $priority = null, ?int $categoryId = null, int $sort = 0): array
    {
        return [
            'type' => $type,
            'id' => $id,
            'title' => $title,
            'lane' => $lane,
            'pos_x' => 0,
            'pos_y' => 0,
            'sort' => $sort,
            'priority' => $priority,
            'category_id' => $categoryId,
            'category' => null,
            'comments_count' => 0,
        ];
    }
}
