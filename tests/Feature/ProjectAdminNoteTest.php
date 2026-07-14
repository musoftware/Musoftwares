<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectAdminNote;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAdminNoteTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'onboarding_completed' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create([
            'onboarding_completed' => true,
        ]);
        $this->clientUser->assignRole('client');

        $this->project = Project::create([
            'user_id' => $this->clientUser->id,
            'project_name' => 'Test Project',
        ]);
    }

    public function test_admin_can_list_project_admin_notes(): void
    {
        ProjectAdminNote::create([
            'project_id' => $this->project->id,
            'author_id' => $this->admin->id,
            'content' => 'This is a test note for testing purposes.',
            'category' => 'Finance',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/admin/projects/{$this->project->id}/admin-notes");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'content' => 'This is a test note for testing purposes.',
            'category' => 'Finance',
        ]);
    }

    public function test_client_cannot_list_project_admin_notes(): void
    {
        $response = $this->actingAs($this->clientUser)
            ->getJson("/admin/projects/{$this->project->id}/admin-notes");

        $response->assertStatus(403);
    }

    public function test_admin_can_store_project_admin_note(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/admin/projects/{$this->project->id}/admin-notes", [
                'content' => 'Highly confidential requirement detail.',
                'category' => 'Requirements',
                'is_pinned' => true,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('project_admin_notes', [
            'project_id' => $this->project->id,
            'author_id' => $this->admin->id,
            'content' => 'Highly confidential requirement detail.',
            'category' => 'Requirements',
            'is_pinned' => 1,
        ]);
    }

    public function test_client_cannot_store_project_admin_note(): void
    {
        $response = $this->actingAs($this->clientUser)
            ->postJson("/admin/projects/{$this->project->id}/admin-notes", [
                'content' => 'Sneaking in a note.',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_project_admin_note(): void
    {
        $note = ProjectAdminNote::create([
            'project_id' => $this->project->id,
            'author_id' => $this->admin->id,
            'content' => 'Original note content.',
            'category' => 'General',
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/admin/projects/{$this->project->id}/admin-notes/{$note->id}", [
                'content' => 'Updated note content.',
                'category' => 'Technical',
                'is_pinned' => true,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('project_admin_notes', [
            'id' => $note->id,
            'content' => 'Updated note content.',
            'category' => 'Technical',
            'is_pinned' => 1,
        ]);
    }

    public function test_admin_cannot_update_note_of_another_project(): void
    {
        $anotherProject = Project::create([
            'user_id' => $this->clientUser->id,
            'project_name' => 'Another Project',
        ]);

        $note = ProjectAdminNote::create([
            'project_id' => $anotherProject->id,
            'author_id' => $this->admin->id,
            'content' => 'Another project note.',
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/admin/projects/{$this->project->id}/admin-notes/{$note->id}", [
                'content' => 'Trying to modify cross-project.',
            ]);

        $response->assertStatus(404);
    }

    public function test_admin_can_delete_project_admin_note(): void
    {
        $note = ProjectAdminNote::create([
            'project_id' => $this->project->id,
            'author_id' => $this->admin->id,
            'content' => 'Delete me.',
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/admin/projects/{$this->project->id}/admin-notes/{$note->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('project_admin_notes', [
            'id' => $note->id,
        ]);
    }
}
