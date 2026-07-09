<?php

namespace Tests\Feature\Admin;

use App\Models\Task;
use App\Models\Todo;
use App\Models\User;
use App\Services\Admin\TodoListQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminTaskAsListTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientA;
    protected User $clientB;
    protected User $clientC;
    protected Task $activeTask;
    protected Task $archivedTask;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientA = User::factory()->create(['onboarding_completed' => true, 'currency' => 2, 'name' => 'Alpha Co']);
        $this->clientA->assignRole('client');

        $this->clientB = User::factory()->create(['onboarding_completed' => true, 'currency' => 2, 'name' => 'Beta Inc', 'email' => 'beta@matching.com']);
        $this->clientB->assignRole('client');

        $this->clientC = User::factory()->create(['onboarding_completed' => true, 'currency' => 2, 'name' => 'Charlie LLC', 'email' => 'charlie@otherdomain.com']);
        $this->clientC->assignRole('client');

        $this->activeTask = Task::create([
            'user_id' => $this->clientA->id,
            'task_name' => 'Onboarding',
            'archived'  => false,
        ]);
        $this->archivedTask = Task::create([
            'user_id' => $this->clientB->id,
            'task_name' => 'Old Work',
            'archived'  => true,
        ]);
    }

    private function makeTodo(array $overrides = []): Todo
    {
        return Todo::create(array_merge([
            'user_id'        => $this->clientA->id,
            'title'          => 'Sample todo',
            'description'    => null,
            'completed'      => false,
            'paused'         => false,
            'inDate'         => date('Y-m-d'),
            'priority'       => 'normal',
            'priorityColor'  => '#11cdef',
            'tags'           => json_encode([]),
            'start_at'       => null,
            'end_at'         => null,
        ], $overrides));
    }

    public function test_non_admin_cannot_access_as_list(): void
    {
        $response = $this->actingAs($this->clientA)->get(route('admin.tasks.as_list'));
        $response->assertStatus(403);
    }

    public function test_admin_can_access_as_list(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.tasks.as_list'));
        $response->assertStatus(200);
    }

    /**
     * This is the original bug from the audit screenshot: stat = 3, list = empty
     * because the stat query and list query disagreed about what "active" means.
     */
    public function test_stats_match_list_for_orphan_todos(): void
    {
        // 3 orphan todos (no live task) → would inflate stats but vanish in list.
        for ($i = 0; $i < 3; $i++) {
            $this->makeTodo(['title' => "Orphan $i"]);
        }
        // 2 healthy active todos (with live task)
        $this->makeTodo(['task_id' => $this->activeTask->id, 'title' => 'Healthy 1']);
        $this->makeTodo(['task_id' => $this->activeTask->id, 'title' => 'Healthy 2']);
        // 1 todo under archived task → would also inflate stats
        $this->makeTodo([
            'user_id' => $this->clientB->id,
            'task_id' => $this->archivedTask->id,
            'title'   => 'Archived',
        ]);
        // 1 completed todo → must not appear
        $this->makeTodo(['completed' => true, 'title' => 'Done']);
        // 1 paused todo → must not appear
        $this->makeTodo(['paused' => true, 'title' => 'Paused']);

        $response = $this->actingAs($this->admin)->get(route('admin.tasks.as_list'));
        $response->assertStatus(200);

        $stats = $response->viewData('page')['props']['stats'];
        $arranged = $response->viewData('page')['props']['arrangedClients'];

        // Stat counts ALL incomplete + non-paused todos (6 total) — same scope as the list.
        $this->assertSame(6, $stats['total_active_todos']);
        $this->assertSame(2, $stats['total_in_boards'], 'Only todos on a non-archived board count toward "in boards"');
        $this->assertSame(4, $stats['orphan_count'], 'Orphans = 3 with no task + 1 with an archived task');

        // List must include all 6 (orphans are surfaced under their own bucket).
        $renderedIds = collect($arranged)
            ->flatMap(fn ($c) => collect($c['tasks'])->flatMap(fn ($t) => collect($t['todos'])->pluck('id')))
            ->sort()->values()->all();
        $expectedIds = Todo::active()->orderBy('id')->pluck('id')->sort()->values()->all();
        $this->assertSame($expectedIds, $renderedIds, 'Every active todo (incl. orphans) must appear in the list');
    }

    public function test_filter_by_client_returns_only_that_client(): void
    {
        $this->makeTodo(['title' => 'A todo']);
        $this->makeTodo(['user_id' => $this->clientB->id, 'title' => 'B todo']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.tasks.as_list', ['client_id' => $this->clientA->id]));

        $response->assertStatus(200);
        $arranged = $response->viewData('page')['props']['arrangedClients'];
        $this->assertCount(1, $arranged);
        $this->assertSame($this->clientA->id, $arranged[0]['client']['id']);
    }

    public function test_search_matches_title_description_email_and_task_name(): void
    {
        // Three clients to keep matching clean
        $clientX = User::factory()->create(['onboarding_completed' => true, 'currency' => 2, 'name' => 'X Co']);
        $clientX->assignRole('client');
        $clientY = User::factory()->create(['onboarding_completed' => true, 'currency' => 2, 'name' => 'Y Co', 'email' => 'token@y.com']);
        $clientY->assignRole('client');
        $clientZ = User::factory()->create(['onboarding_completed' => true, 'currency' => 2, 'name' => 'Z Co']);
        $clientZ->assignRole('client');

        // 1. title match
        $this->makeTodo(['user_id' => $clientX->id, 'title' => 'TOKEN_title_hit',  'description' => null]);
        // 2. description match
        $this->makeTodo(['user_id' => $clientX->id, 'title' => 'plain title',      'description' => 'contains TOKEN_desc_hit somewhere']);
        // 3. task name match
        $task = Task::create(['user_id' => $clientX->id, 'task_name' => 'TOKEN_task_hit']);
        $this->makeTodo(['user_id' => $clientX->id, 'task_id' => $task->id, 'title' => 'On task']);
        // 4. email match (clientY's email contains TOKEN)
        $this->makeTodo(['user_id' => $clientY->id, 'title' => 'email hit']);
        // 5. negative
        $this->makeTodo(['user_id' => $clientZ->id, 'title' => 'plain no match', 'description' => 'nothing here']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.tasks.as_list', ['search' => 'TOKEN']));
        $response->assertStatus(200);

        $titles = collect($response->viewData('page')['props']['arrangedClients'])
            ->flatMap(fn ($c) => collect($c['tasks'])->flatMap(fn ($t) => collect($t['todos'])->pluck('title')))
            ->all();

        $this->assertContains('TOKEN_title_hit',    $titles, 'title match');
        $this->assertContains('plain title',        $titles, 'description match');
        $this->assertContains('On task',            $titles, 'task name match');
        $this->assertContains('email hit',          $titles, 'email match');
        $this->assertNotContains('plain no match',  $titles, 'no false positives');
    }

    public function test_priority_paused_filters(): void
    {
        $this->makeTodo(['title' => 'High active', 'priority' => 'high',   'paused' => false]);
        $this->makeTodo(['title' => 'Low active',  'priority' => 'low',    'paused' => false]);
        $this->makeTodo(['title' => 'Normal paused','priority' => 'normal','paused' => true]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.tasks.as_list', ['priority' => 'high']));
        $response->assertStatus(200);

        $titles = collect($response->viewData('page')['props']['arrangedClients'])
            ->flatMap(fn ($c) => collect($c['tasks'])->flatMap(fn ($t) => collect($t['todos'])->pluck('title')))
            ->all();

        $this->assertSame(['High active'], $titles);
    }

    public function test_sort_by_due_asc_orders_by_end_at(): void
    {
        $this->makeTodo(['title' => 'Later', 'end_at' => Carbon::now()->addDays(10)]);
        $this->makeTodo(['title' => 'Soon',  'end_at' => Carbon::now()->addDays(1)]);
        $this->makeTodo(['title' => 'No deadline', 'end_at' => null]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.tasks.as_list', ['sort' => 'due_asc']));
        $response->assertStatus(200);

        $titles = collect($response->viewData('page')['props']['arrangedClients'])
            ->flatMap(fn ($c) => collect($c['tasks'])->flatMap(fn ($t) => collect($t['todos'])->pluck('title')))
            ->all();

        $this->assertSame(['Soon', 'Later', 'No deadline'], $titles);
    }

    public function test_pagination_respects_per_page(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->makeTodo(['title' => "T $i"]);
        }

        $response = $this->actingAs($this->admin)
            ->get(route('admin.tasks.as_list', ['per_page' => 5, 'page' => 2]));
        $response->assertStatus(200);

        $paginator = $response->viewData('page')['props']['pagination'];
        $this->assertSame(5, $paginator['per_page']);
        $this->assertSame(2, $paginator['current_page']);
        $this->assertSame(3, $paginator['last_page']);
        $this->assertSame(12, $paginator['total']);
    }

    public function test_bulk_complete_marks_only_active_todos(): void
    {
        $a = $this->makeTodo(['title' => 'A']);
        $b = $this->makeTodo(['title' => 'B']);
        $completed = $this->makeTodo(['title' => 'Done', 'completed' => true]);
        $paused = $this->makeTodo(['title' => 'Paused', 'paused' => true]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.tasks.todos.bulk-complete'), [
                'todo_ids'  => [$a->id, $b->id, $completed->id, $paused->id],
                'completed' => true,
            ]);
        $response->assertOk();
        $response->assertJson(['status' => 'success', 'affected' => 2]);

        $this->assertTrue((bool) $a->fresh()->completed);
        $this->assertTrue((bool) $b->fresh()->completed);
        $this->assertTrue((bool) $completed->fresh()->completed, 'Already-completed must remain completed');
        $this->assertFalse((bool) $paused->fresh()->completed, 'Paused must NOT be marked complete by bulk');
    }

    public function test_bulk_complete_rejects_empty_or_oversized(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.tasks.todos.bulk-complete'), [
                'todo_ids'  => [],
                'completed' => true,
            ])->assertStatus(422);

        $ids = range(1, 501);
        $this->actingAs($this->admin)
            ->postJson(route('admin.tasks.todos.bulk-complete'), [
                'todo_ids'  => $ids,
                'completed' => true,
            ])->assertStatus(422);
    }

    public function test_csv_export_returns_filtered_rows(): void
    {
        $this->makeTodo(['title' => 'Export me', 'user_id' => $this->clientA->id]);
        $this->makeTodo(['title' => 'Other',     'user_id' => $this->clientB->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.tasks.as_list.export', ['client_id' => $this->clientA->id]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $body = $response->streamedContent();
        $this->assertStringContainsString('Export me', $body);
        $this->assertStringNotContainsString('Other', $body);
    }

    public function test_service_normalize_filters_handles_blank_strings(): void
    {
        $svc = app(TodoListQueryService::class);
        $out = $svc->normalizeFilters([
            'search'    => '',
            'is_paid'   => '',
            'paused'    => '',
            'priority'  => null,
            'sort'      => 'bogus',
        ]);
        $this->assertNull($out['search']);
        $this->assertNull($out['is_paid']);
        $this->assertNull($out['paused']);
        $this->assertNull($out['priority']);
        $this->assertSame('created_desc', $out['sort'], 'invalid sort falls back to default');
    }

    public function test_service_compute_stats_is_consistent_with_paginate(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->makeTodo(['title' => "T $i", 'task_id' => $this->activeTask->id]);
        }
        for ($i = 0; $i < 2; $i++) {
            $this->makeTodo(['title' => "Orphan $i"]);
        }

        $svc = app(TodoListQueryService::class);
        $stats = $svc->computeStats();
        $page  = $svc->paginate($svc->normalizeFilters([]), 100);

        $this->assertSame($stats['total_active_todos'], $page->total(), 'stat total must match list total');
    }
}
