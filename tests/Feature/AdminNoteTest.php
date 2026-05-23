<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\Client;
use App\Models\AdminNote;
use Tests\TestCase;

class AdminNoteTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Tenant $tenant;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->tenant = Tenant::create([
            'user_id' => $this->admin->id,
            'name' => 'HQ Agency',
            'status' => 'active',
        ]);

        session(['tenant_id' => $this->tenant->id]);

        $this->client = Client::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Fraudulent Client Corp',
            'email' => 'suspicious@corp.com',
            'phone' => '+1555000000',
            'currency' => 'USD',
            'address' => 'Unknown St',
        ]);
    }

    public function test_can_list_admin_notes(): void
    {
        $note = AdminNote::create([
            'noteable_type' => Client::class,
            'noteable_id' => $this->client->id,
            'author_id' => $this->admin->id,
            'content' => 'Watch this client closely.',
            'type' => 'warning',
            'visibility' => 'staff_only',
            'risk_level' => 'medium',
            'is_pinned' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin-notes?noteable_type=client&noteable_id={$this->client->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'content' => 'Watch this client closely.',
        ]);
    }

    public function test_can_store_admin_note(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin-notes', [
                'noteable_type' => 'client',
                'noteable_id' => $this->client->id,
                'content' => 'High risk account: excessive chargeback potential.',
                'type' => 'fraud_risk',
                'visibility' => 'admins_only',
                'risk_level' => 'high',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('admin_notes', [
            'noteable_type' => Client::class,
            'noteable_id' => $this->client->id,
            'author_id' => $this->admin->id,
            'content' => 'High risk account: excessive chargeback potential.',
            'type' => 'fraud_risk',
            'risk_level' => 'high',
        ]);
    }

    public function test_can_toggle_pin_admin_note(): void
    {
        $note = AdminNote::create([
            'noteable_type' => Client::class,
            'noteable_id' => $this->client->id,
            'author_id' => $this->admin->id,
            'content' => 'A pinned warning note.',
            'type' => 'general',
            'visibility' => 'staff_only',
            'risk_level' => 'none',
            'is_pinned' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/admin-notes/{$note->id}/pin");

        $response->assertStatus(200);
        $this->assertTrue($note->fresh()->is_pinned);

        // Toggle back to unpinned
        $response = $this->actingAs($this->admin)
            ->patchJson("/api/admin-notes/{$note->id}/pin");

        $response->assertStatus(200);
        $this->assertFalse($note->fresh()->is_pinned);
    }

    public function test_can_delete_admin_note(): void
    {
        $note = AdminNote::create([
            'noteable_type' => Client::class,
            'noteable_id' => $this->client->id,
            'author_id' => $this->admin->id,
            'content' => 'To be deleted.',
            'type' => 'general',
            'visibility' => 'staff_only',
            'risk_level' => 'none',
            'is_pinned' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin-notes/{$note->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('admin_notes', [
            'id' => $note->id,
        ]);
    }
}

