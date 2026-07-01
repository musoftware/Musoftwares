<?php

namespace Tests\Feature\Admin;

use App\Models\Project;
use App\Models\ProjectAuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerExtendedTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');
        $this->client = User::factory()->create(['onboarding_completed' => true]);
        $this->client->assignRole('client');
    }

    public function test_admin_can_store_project_with_description_and_dates(): void
    {
        $payload = [
            'user_id' => $this->client->id,
            'project_name' => 'Full Project',
            'description' => 'A long description of the project scope.',
            'status' => 'hold_on',
            'date_start' => '2026-08-01',
            'date_end' => '2026-09-30',
            'budget' => 5000.50,
            'hour_rate' => 25,
            'percentage' => 15.5,
            'hide_future_tasks' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('projects', [
            'project_name' => 'Full Project',
            'description' => 'A long description of the project scope.',
            'status' => 'hold_on',
        ]);

        $this->assertDatabaseHas('project_audit_logs', [
            'action' => ProjectAuditLog::ACTION_CREATED,
        ]);
    }

    public function test_store_rejects_end_date_before_start_date(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), [
                'user_id' => $this->client->id,
                'project_name' => 'Bad Dates',
                'date_start' => '2026-09-01',
                'date_end' => '2026-08-01',
            ]);

        $response->assertSessionHasErrors('date_end');
    }

    public function test_store_validates_status_enum(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), [
                'user_id' => $this->client->id,
                'project_name' => 'Bad Status',
                'status' => 'in_progress',
            ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_admin_can_update_project_with_all_fields(): void
    {
        $project = Project::create([
            'user_id' => $this->client->id,
            'project_name' => 'Before',
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.projects.update', $project->id), [
                'project_name' => 'After',
                'description' => 'Updated description',
                'status' => 'closed',
                'budget' => 1000,
                'date_start' => '2026-01-01',
                'date_end' => '2026-12-31',
            ]);

        $response->assertRedirect();
        $fresh = $project->fresh();
        $this->assertSame('After', $fresh->project_name);
        $this->assertSame('Updated description', $fresh->description);
        $this->assertSame('closed', $fresh->status);

        $this->assertDatabaseHas('project_audit_logs', [
            'project_id' => $project->id,
            'action' => ProjectAuditLog::ACTION_UPDATED,
        ]);
    }

    public function test_archive_sets_archived_at_timestamp_and_logs(): void
    {
        $project = Project::create([
            'user_id' => $this->client->id,
            'project_name' => 'Archive Test',
        ]);

        $this->assertNull($project->archived_at);

        $this->actingAs($this->admin)
            ->post(route('admin.projects.archive', $project->id))
            ->assertRedirect();

        $fresh = $project->fresh();
        $this->assertTrue((bool) $fresh->archived);
        $this->assertNotNull($fresh->archived_at);

        $this->assertDatabaseHas('project_audit_logs', [
            'project_id' => $project->id,
            'action' => ProjectAuditLog::ACTION_ARCHIVED,
        ]);
    }

    public function test_restore_clears_archived_at(): void
    {
        $project = Project::create([
            'user_id' => $this->client->id,
            'project_name' => 'Restore Test',
            'archived' => true,
            'archived_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.projects.restore', $project->id))
            ->assertRedirect();

        $fresh = $project->fresh();
        $this->assertFalse((bool) $fresh->archived);
        $this->assertNull($fresh->archived_at);
    }

    public function test_index_filters_by_status(): void
    {
        Project::create(['user_id' => $this->client->id, 'project_name' => 'Open One', 'status' => 'open']);
        Project::create(['user_id' => $this->client->id, 'project_name' => 'Hold One', 'status' => 'hold_on']);
        Project::create(['user_id' => $this->client->id, 'project_name' => 'Closed One', 'status' => 'closed']);

        $this->actingAs($this->admin)
            ->get(route('admin.projects.index', ['status_filter' => 'hold_on']))
            ->assertInertia(fn ($page) => $page
                ->where('statusFilter', 'hold_on')
                ->has('projects.data', 1)
                ->where('projects.data.0.project_name', 'Hold One'));
    }

    public function test_index_sorts_by_name_asc(): void
    {
        Project::create(['user_id' => $this->client->id, 'project_name' => 'Zeta']);
        Project::create(['user_id' => $this->client->id, 'project_name' => 'Alpha']);
        Project::create(['user_id' => $this->client->id, 'project_name' => 'Mike']);

        $this->actingAs($this->admin)
            ->get(route('admin.projects.index', ['sort' => 'project_name', 'dir' => 'asc']))
            ->assertInertia(fn ($page) => $page
                ->where('projects.data.0.project_name', 'Alpha')
                ->where('projects.data.1.project_name', 'Mike')
                ->where('projects.data.2.project_name', 'Zeta'));
    }

    public function test_index_respects_per_page(): void
    {
        for ($i = 0; $i < 30; $i++) {
            Project::create([
                'user_id' => $this->client->id,
                'project_name' => "P{$i}",
            ]);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.projects.index', ['per_page' => 25]))
            ->assertInertia(fn ($page) => $page
                ->where('perPage', 25)
                ->has('projects.data', 25));
    }

    public function test_bulk_archive_only_affects_active_projects(): void
    {
        $active = Project::create(['user_id' => $this->client->id, 'project_name' => 'Active', 'archived' => 0]);
        $alreadyArchived = Project::create(['user_id' => $this->client->id, 'project_name' => 'Archived', 'archived' => 1, 'archived_at' => now()]);

        $this->actingAs($this->admin)
            ->post(route('admin.projects.bulk-action'), [
                'action' => 'archive',
                'ids' => [$active->id, $alreadyArchived->id],
            ])
            ->assertRedirect();

        $this->assertTrue((bool) $active->fresh()->archived);
        $this->assertNotNull($active->fresh()->archived_at);
        // Already-archived project: archived_at should remain unchanged
        $this->assertNotNull($alreadyArchived->fresh()->archived_at);
    }

    public function test_bulk_delete_soft_deletes_and_logs(): void
    {
        $a = Project::create(['user_id' => $this->client->id, 'project_name' => 'A']);
        $b = Project::create(['user_id' => $this->client->id, 'project_name' => 'B']);

        $this->actingAs($this->admin)
            ->post(route('admin.projects.bulk-action'), [
                'action' => 'delete',
                'ids' => [$a->id, $b->id],
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('projects', ['id' => $a->id]);
        $this->assertSoftDeleted('projects', ['id' => $b->id]);
        $this->assertDatabaseHas('project_audit_logs', [
            'project_id' => $a->id,
            'action' => ProjectAuditLog::ACTION_BULK_DELETED,
        ]);
    }

    public function test_bulk_action_validates_action(): void
    {
        $project = Project::create(['user_id' => $this->client->id, 'project_name' => 'X']);

        $this->actingAs($this->admin)
            ->post(route('admin.projects.bulk-action'), [
                'action' => 'nuke',
                'ids' => [$project->id],
            ])
            ->assertSessionHasErrors('action');
    }

    public function test_bulk_action_requires_existing_ids(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.projects.bulk-action'), [
                'action' => 'archive',
                'ids' => [999999],
            ])
            ->assertSessionHasErrors('ids.0');
    }

    public function test_export_streams_csv(): void
    {
        Project::create(['user_id' => $this->client->id, 'project_name' => 'Alpha', 'status' => 'open']);
        Project::create(['user_id' => $this->client->id, 'project_name' => 'Beta', 'status' => 'closed', 'archived' => 1]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.projects.export', ['status' => 'active']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();
        $this->assertStringContainsString('project_name', $csv);
        $this->assertStringContainsString('Alpha', $csv);
        $this->assertStringNotContainsString('Beta', $csv);
    }

    public function test_search_clients_requires_admin(): void
    {
        $this->actingAs($this->client)
            ->getJson(route('admin.projects.search-clients', ['q' => 'foo']))
            ->assertStatus(403);
    }

    public function test_search_clients_returns_minimum_2_chars(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('admin.projects.search-clients', ['q' => 'a']))
            ->assertStatus(200)
            ->assertExactJson([]);
    }

    public function test_search_clients_finds_by_name_and_email(): void
    {
        User::factory()->create(['name' => 'Acme Corp', 'email' => 'contact@acme.test']);
        User::factory()->create(['name' => 'Globex', 'email' => 'info@globex.test']);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.projects.search-clients', ['q' => 'acme']));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Acme Corp']);
    }

    public function test_index_uses_load_count_and_exposes_counts(): void
    {
        $project = Project::create(['user_id' => $this->client->id, 'project_name' => 'With Counts']);
        for ($i = 0; $i < 2; $i++) {
            \DB::table('tasks')->insert([
                'project_id' => $project->id,
                'user_id' => $this->client->id,
                'title' => "Task {$i}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.projects.index'))
            ->assertInertia(fn ($page) => $page
                ->where('projects.data.0.counts.tasks', 2));
    }

    public function test_store_owner_id_must_exist(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), [
                'user_id' => $this->client->id,
                'project_name' => 'Bad Owner',
                'owner_id' => 999999,
            ]);

        $response->assertSessionHasErrors('owner_id');
    }

    public function test_store_persists_owner_id(): void
    {
        $other = User::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.projects.store'), [
                'user_id' => $this->client->id,
                'owner_id' => $other->id,
                'project_name' => 'Owned',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'project_name' => 'Owned',
            'owner_id' => $other->id,
        ]);
    }

    public function test_non_admin_cannot_bulk_action(): void
    {
        $project = Project::create(['user_id' => $this->client->id, 'project_name' => 'X']);

        $this->actingAs($this->client)
            ->post(route('admin.projects.bulk-action'), [
                'action' => 'archive',
                'ids' => [$project->id],
            ])
            ->assertStatus(403);
    }

    public function test_non_admin_cannot_export(): void
    {
        $this->actingAs($this->client)
            ->get(route('admin.projects.export'))
            ->assertStatus(403);
    }
}
