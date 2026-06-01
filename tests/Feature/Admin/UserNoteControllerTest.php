<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\UserNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNoteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_user_notes()
    {
        $response = $this->actingAs($this->admin)->get("/admin/users/{$this->clientUser->id}/notes");
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_user_notes()
    {
        $response = $this->actingAs($this->clientUser)->get("/admin/users/{$this->clientUser->id}/notes");
        $response->assertStatus(403);
    }

    public function test_admin_can_get_user_notes_json()
    {
        $response = $this->actingAs($this->admin)->get("/admin/users/{$this->clientUser->id}/notes/json");
        $response->assertStatus(200)->assertJsonStructure(['data', 'stats']);
    }

    public function test_admin_can_store_user_note()
    {
        $response = $this->actingAs($this->admin)->postJson("/admin/users/{$this->clientUser->id}/notes", [
            'title' => 'Test Note',
            'category' => 'notes',
            'content' => 'This is a test note.'
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('user_notes', [
            'user_id' => $this->clientUser->id,
            'title' => 'Test Note',
            'content' => 'This is a test note.',
            'category' => 'notes'
        ]);
    }

    public function test_admin_can_delete_user_note()
    {
        $note = UserNote::create([
            'user_id' => $this->clientUser->id,
            'title' => 'Test Note',
            'category' => 'notes',
            'content' => 'Delete me'
        ]);

        $response = $this->actingAs($this->admin)->deleteJson("/admin/users/{$this->clientUser->id}/notes/{$note->id}");

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseMissing('user_notes', [
            'id' => $note->id
        ]);
    }

    public function test_admin_can_archive_user_note()
    {
        $note = UserNote::create([
            'user_id' => $this->clientUser->id,
            'title' => 'Test Note',
            'category' => 'notes',
            'content' => 'Archive me'
        ]);

        $response = $this->actingAs($this->admin)->postJson("/admin/users/{$this->clientUser->id}/notes/{$note->id}/archive");

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('user_notes', [
            'id' => $note->id,
            'category' => 'archived',
            'original_category' => 'notes'
        ]);
    }

    public function test_admin_can_unarchive_user_note()
    {
        $note = UserNote::create([
            'user_id' => $this->clientUser->id,
            'title' => 'Test Note',
            'category' => 'archived',
            'original_category' => 'notes',
            'content' => 'Unarchive me'
        ]);

        // assuming the route is post to unarchive
        $response = $this->actingAs($this->admin)->postJson("/admin/users/{$this->clientUser->id}/notes/{$note->id}/unarchive");

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('user_notes', [
            'id' => $note->id,
            'category' => 'notes',
            'original_category' => null
        ]);
    }

    public function test_admin_can_toggle_pin_user_note()
    {
        $note = UserNote::create([
            'user_id' => $this->clientUser->id,
            'title' => 'Test Note',
            'category' => 'notes',
            'content' => 'Pin me',
            'is_pinned' => false
        ]);

        // assuming the route is post to toggle-pin
        $response = $this->actingAs($this->admin)->postJson("/admin/users/{$this->clientUser->id}/notes/{$note->id}/toggle-pin");

        $response->assertStatus(200)->assertJson(['success' => true, 'is_pinned' => true]);
        $this->assertDatabaseHas('user_notes', [
            'id' => $note->id,
            'is_pinned' => true
        ]);
    }
}
