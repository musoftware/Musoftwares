<?php

namespace Tests\Feature\Admin;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_projects_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.projects.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_projects_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.projects.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_store_project(): void
    {
        $payload = [
            'user_id' => $this->clientUser->id,
            'project_name' => 'New Website',
            'description' => 'A great website project.',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.projects.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('projects', [
            'user_id' => $this->clientUser->id,
            'project_name' => 'New Website',
        ]);
    }

    public function test_store_project_validation(): void
    {
        $payload = [
            'user_id' => $this->clientUser->id,
            'project_name' => '', // Required
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.projects.store'), $payload);

        $response->assertSessionHasErrors('project_name');
    }

    public function test_admin_can_update_project(): void
    {
        $project = Project::create([
            'user_id' => $this->clientUser->id,
            'project_name' => 'Old Website',
        ]);

        $payload = [
            'project_name' => 'Updated Website',
            'user_id' => $this->clientUser->id,
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.projects.update', $project->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('Updated Website', $project->fresh()->project_name);
    }

    public function test_admin_can_archive_project(): void
    {
        $project = Project::create([
            'user_id' => $this->clientUser->id,
            'project_name' => 'Archive Me',
            'archived' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.projects.archive', $project->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue((bool) $project->fresh()->archived);
    }

    public function test_admin_can_restore_project(): void
    {
        $project = Project::create([
            'user_id' => $this->clientUser->id,
            'project_name' => 'Restore Me',
            'archived' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.projects.restore', $project->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertFalse((bool) $project->fresh()->archived);
    }

    public function test_admin_can_delete_project(): void
    {
        $project = Project::create([
            'user_id' => $this->clientUser->id,
            'project_name' => 'Delete Me',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.projects.destroy', $project->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }
}
