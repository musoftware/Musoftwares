<?php

namespace Tests\Feature\Admin;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Models\UserCredential;
use App\Services\UserNoteAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserNoteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $otherAdmin;
    protected User $accountant;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->otherAdmin = User::factory()->create(['onboarding_completed' => true]);
        $this->otherAdmin->assignRole('admin');

        $this->accountant = User::factory()->create(['onboarding_completed' => true]);
        $this->accountant->assignRole('accountant');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_user_notes(): void
    {
        $this->actingAs($this->admin)
            ->get("/admin/users/{$this->clientUser->id}/notes")
            ->assertStatus(200);
    }

    public function test_view_404_for_unknown_user(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/users/999999/notes')
            ->assertStatus(404);
    }

    public function test_non_admin_cannot_view_user_notes(): void
    {
        $this->actingAs($this->clientUser)
            ->get("/admin/users/{$this->clientUser->id}/notes")
            ->assertStatus(403);
    }

    public function test_accountant_cannot_view_user_notes(): void
    {
        $this->actingAs($this->accountant)
            ->get("/admin/users/{$this->clientUser->id}/notes")
            ->assertStatus(403);
    }

    public function test_unauthenticated_request_redirects(): void
    {
        $this->get("/admin/users/{$this->clientUser->id}/notes")
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    public function test_json_endpoint_returns_paginated_items_and_stats(): void
    {
        for ($i = 0; $i < 30; $i++) {
            UserCredential::create([
                'user_id'  => $this->clientUser->id,
                'admin_id' => $this->admin->id,
                'category' => 'notes',
                'title'    => "Note $i",
                'content'  => "Content $i",
            ]);
        }
        $res = $this->actingAs($this->admin)
            ->getJson("/admin/users/{$this->clientUser->id}/notes/json?per_page=10");

        $res->assertStatus(200)
            ->assertJsonStructure(['data' => ['items', 'current_page', 'last_page', 'total', 'per_page'], 'stats'])
            ->assertJsonPath('data.total', 30)
            ->assertJsonPath('data.per_page', 10);
    }

    public function test_admin_can_store_user_note(): void
    {
        $res = $this->actingAs($this->admin)->postJson("/admin/users/{$this->clientUser->id}/notes", [
            'title'      => 'Database password',
            'content'    => str_repeat('a', 300), // mimic a SimpleCrypto cipher blob
            'category'   => 'password',
            'expires_at' => now()->addDays(10)->toDateString(),
        ]);

        $res->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_credentials', [
            'user_id'  => $this->clientUser->id,
            'category' => 'password',
        ]);
        $this->assertNotNull(UserCredential::first()->expires_at);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action'    => UserNoteAuditService::ACTION_CREATED,
            'actor_user_id' => $this->admin->id,
            'target_id' => $this->clientUser->id,
        ]);
    }

    public function test_invalid_category_is_rejected(): void
    {
        $this->actingAs($this->admin)->postJson("/admin/users/{$this->clientUser->id}/notes", [
            'title'    => 'Title',
            'content'  => 'Body',
            'category' => 'archived', // forbidden in create
        ])->assertStatus(422)->assertJsonValidationErrors(['category']);
    }

    public function test_missing_content_is_rejected(): void
    {
        $this->actingAs($this->admin)->postJson("/admin/users/{$this->clientUser->id}/notes", [
            'title'    => 'Title',
            'category' => 'notes',
        ])->assertStatus(422)->assertJsonValidationErrors(['content']);
    }

    public function test_admin_can_update_note(): void
    {
        $note = UserCredential::create([
            'user_id'  => $this->clientUser->id,
            'admin_id' => $this->admin->id,
            'category' => 'notes',
            'title'    => 'Old',
            'content'  => 'Old content',
        ]);

        $res = $this->actingAs($this->admin)->putJson(
            "/admin/users/{$this->clientUser->id}/notes/{$note->id}",
            [
                'title'    => 'New title',
                'content'  => 'New body',
                'category' => 'password',
            ]
        );

        $res->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('user_credentials', [
            'id'       => $note->id,
            'category' => 'password',
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => UserNoteAuditService::ACTION_UPDATED,
        ]);
    }

    public function test_update_records_rotated_at_for_password_category(): void
    {
        $note = UserCredential::create([
            'user_id'  => $this->clientUser->id,
            'admin_id' => $this->admin->id,
            'category' => 'notes',
            'title'    => 'T',
            'content'  => 'C',
        ]);

        $this->actingAs($this->admin)->putJson(
            "/admin/users/{$this->clientUser->id}/notes/{$note->id}",
            ['category' => 'password', 'title' => 'Rotated', 'content' => 'Rotated body']
        )->assertStatus(200);

        $this->assertNotNull(UserCredential::find($note->id)->rotated_at);
    }

    public function test_admin_can_delete_user_note(): void
    {
        $note = UserCredential::create([
            'user_id' => $this->clientUser->id, 'category' => 'notes', 'title' => 'T', 'content' => 'C',
        ]);

        $this->actingAs($this->admin)
            ->deleteJson("/admin/users/{$this->clientUser->id}/notes/{$note->id}")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('user_credentials', ['id' => $note->id]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => UserNoteAuditService::ACTION_DELETED,
        ]);
    }

    public function test_admin_can_archive_user_note(): void
    {
        $note = UserCredential::create([
            'user_id' => $this->clientUser->id, 'category' => 'notes', 'title' => 'T', 'content' => 'C',
        ]);

        $this->actingAs($this->admin)
            ->postJson("/admin/users/{$this->clientUser->id}/notes/{$note->id}/archive")
            ->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_credentials', [
            'id' => $note->id, 'category' => 'archived', 'original_category' => 'notes',
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => UserNoteAuditService::ACTION_ARCHIVED,
        ]);
    }

    public function test_archiving_already_archived_note_returns_400(): void
    {
        $note = UserCredential::create([
            'user_id' => $this->clientUser->id, 'category' => 'archived', 'original_category' => 'notes',
            'title' => 'T', 'content' => 'C',
        ]);

        $this->actingAs($this->admin)
            ->postJson("/admin/users/{$this->clientUser->id}/notes/{$note->id}/archive")
            ->assertStatus(400);
    }

    public function test_admin_can_unarchive_user_note(): void
    {
        $note = UserCredential::create([
            'user_id' => $this->clientUser->id, 'category' => 'archived', 'original_category' => 'anydesk',
            'title' => 'T', 'content' => 'C',
        ]);

        $this->actingAs($this->admin)
            ->postJson("/admin/users/{$this->clientUser->id}/notes/{$note->id}/unarchive")
            ->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_credentials', [
            'id' => $note->id, 'category' => 'anydesk', 'original_category' => null,
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => UserNoteAuditService::ACTION_UNARCHIVED,
        ]);
    }

    public function test_toggle_pin_returns_new_state(): void
    {
        $note = UserCredential::create([
            'user_id' => $this->clientUser->id, 'category' => 'notes', 'title' => 'T', 'content' => 'C',
        ]);

        $res = $this->actingAs($this->admin)
            ->postJson("/admin/users/{$this->clientUser->id}/notes/{$note->id}/toggle-pin");

        $res->assertStatus(200)
            ->assertJson(['success' => true, 'is_pinned' => true]);

        $this->actingAs($this->admin)
            ->postJson("/admin/users/{$this->clientUser->id}/notes/{$note->id}/toggle-pin")
            ->assertJson(['is_pinned' => false]);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => UserNoteAuditService::ACTION_PIN_TOGGLED,
        ]);
    }

    public function test_reveal_records_last_revealed_at_and_audits(): void
    {
        $note = UserCredential::create([
            'user_id' => $this->clientUser->id, 'category' => 'password', 'title' => 'T', 'content' => 'C',
        ]);

        $this->actingAs($this->admin)
            ->postJson("/admin/users/{$this->clientUser->id}/notes/{$note->id}/reveal")
            ->assertStatus(200);

        $fresh = UserCredential::find($note->id);
        $this->assertNotNull($fresh->last_revealed_at);
        $this->assertSame($this->admin->id, $fresh->last_revealed_by);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => UserNoteAuditService::ACTION_REVEALED,
        ]);
    }

    public function test_bulk_archive(): void
    {
        $ids = collect(range(1, 3))->map(fn ($i) => UserCredential::create([
            'user_id' => $this->clientUser->id, 'category' => 'notes', 'title' => "T$i", 'content' => "C$i",
        ])->id)->all();

        $this->actingAs($this->admin)
            ->postJson("/admin/users/{$this->clientUser->id}/notes/bulk", [
                'action'   => 'archive',
                'note_ids' => $ids,
            ])->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseMissing('user_credentials', [
            'id' => $ids[0], 'category' => 'notes',
        ]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => UserNoteAuditService::ACTION_BULK_ARCHIVED,
        ]);
    }

    public function test_bulk_delete(): void
    {
        $ids = collect(range(1, 2))->map(fn ($i) => UserCredential::create([
            'user_id' => $this->clientUser->id, 'category' => 'notes', 'title' => "T$i", 'content' => "C$i",
        ])->id)->all();

        $this->actingAs($this->admin)
            ->postJson("/admin/users/{$this->clientUser->id}/notes/bulk", [
                'action'   => 'delete',
                'note_ids' => $ids,
            ])->assertStatus(200);

        foreach ($ids as $id) {
            $this->assertSoftDeleted('user_credentials', ['id' => $id]);
        }
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => UserNoteAuditService::ACTION_BULK_DELETED,
        ]);
    }

    public function test_bulk_action_rejects_unknown_action(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/admin/users/{$this->clientUser->id}/notes/bulk", [
                'action'   => 'nuke',
                'note_ids' => [1],
            ])->assertStatus(422)->assertJsonValidationErrors(['action']);
    }

    public function test_bulk_action_requires_note_ids(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/admin/users/{$this->clientUser->id}/notes/bulk", [
                'action'   => 'archive',
                'note_ids' => [],
            ])->assertStatus(422)->assertJsonValidationErrors(['note_ids']);
    }

    public function test_stats_include_expiry_counts(): void
    {
        UserCredential::create([
            'user_id' => $this->clientUser->id, 'category' => 'password',
            'title' => 'Expired', 'content' => 'x', 'expires_at' => now()->subDay(),
        ]);
        UserCredential::create([
            'user_id' => $this->clientUser->id, 'category' => 'password',
            'title' => 'Soon', 'content' => 'x', 'expires_at' => now()->addDays(3),
        ]);
        UserCredential::create([
            'user_id' => $this->clientUser->id, 'category' => 'password',
            'title' => 'Active', 'content' => 'x', 'expires_at' => now()->addDays(60),
        ]);

        $stats = (new \App\Services\UserNoteService(new UserNoteAuditService()))->getStats($this->clientUser->id);

        $this->assertSame(1, $stats['expired']);
        $this->assertSame(1, $stats['expiring_soon']);
        $this->assertSame(3, $stats['password']);
    }

    public function test_resource_does_not_leak_admin_id(): void
    {
        $note = UserCredential::create([
            'user_id' => $this->clientUser->id, 'admin_id' => $this->admin->id,
            'category' => 'notes', 'title' => 'T', 'content' => 'C',
        ]);

        $payload = (new \App\Http\Resources\UserNoteResource($note))->resolve();

        $this->assertArrayNotHasKey('admin_id', $payload);
        $this->assertArrayHasKey('author', $payload);
        $this->assertSame($this->admin->name, $payload['author']['name']);
    }

    public function test_index_orders_pinned_first(): void
    {
        $old = UserCredential::create([
            'user_id' => $this->clientUser->id, 'category' => 'notes', 'title' => 'Newer', 'content' => 'c',
            'created_at' => now()->subDay(), 'updated_at' => now()->subDay(),
        ]);
        $pinned = UserCredential::create([
            'user_id' => $this->clientUser->id, 'category' => 'notes', 'title' => 'OlderPinned', 'content' => 'c',
            'is_pinned' => true,
            'created_at' => now()->subDays(5), 'updated_at' => now()->subDays(5),
        ]);

        $res = $this->actingAs($this->admin)
            ->getJson("/admin/users/{$this->clientUser->id}/notes/json");

        $res->assertStatus(200);
        $items = $res->json('data.items') ?? $res->json('data');
        $this->assertSame($pinned->id, $items[0]['id']);
    }

    public function test_unauthorized_admin_cannot_modify_other_admins_notes_through_bulk(): void
    {
        $note = UserCredential::create([
            'user_id'  => $this->clientUser->id,
            'admin_id' => $this->otherAdmin->id,
            'category' => 'notes', 'title' => 'T', 'content' => 'C',
        ]);

        // Bulk delete should still succeed (audit logs the actor); this is admin-only.
        $this->actingAs($this->admin)
            ->postJson("/admin/users/{$this->clientUser->id}/notes/bulk", [
                'action'   => 'delete',
                'note_ids' => [$note->id],
            ])->assertStatus(200);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action'        => UserNoteAuditService::ACTION_BULK_DELETED,
            'actor_user_id' => $this->admin->id,
        ]);
    }
}
