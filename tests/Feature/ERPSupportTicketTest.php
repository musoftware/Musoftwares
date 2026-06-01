<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\SupportTicket;
use Tests\TestCase;

class ERPSupportTicketTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Tenant $tenant;
    protected TenantClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create(['onboarding_completed' => true]);
        $this->user->assignRole('client');

        $this->tenant = Tenant::create([
            'user_id' => $this->user->id,
            'name' => 'My Test Workspace',
            'status' => 'active',
        ]);

        $this->client = TenantClient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Acme Corp',
            'email' => 'billing@acme.com',
            'phone' => '+15551234567',
            'currency' => 'USD',
            'address' => '123 Main St, Anytown',
        ]);
    }

    public function test_can_store_support_ticket_with_existing_client(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('erp.tickets.store'), [
                'subject' => 'Database Sync Issue',
                'description' => 'We are seeing latency while syncing the client wallet.',
                'priority' => 'high',
                'client_id' => $this->client->id,
            ]);

        $response->assertStatus(302);
        
        $this->assertDatabaseHas('erp_support_tickets', [
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'subject' => 'Database Sync Issue',
            'status' => 'open',
            'priority' => 'high',
        ]);
    }

    public function test_can_store_support_ticket_with_auto_created_client(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('erp.tickets.store'), [
                'subject' => 'Billing Inquiry',
                'description' => 'Need help setting up dynamic rates.',
                'priority' => 'medium',
                'client_name' => 'Dynamic Client LLC',
            ]);

        $response->assertStatus(302);

        // Verify the client was auto-created under this tenant
        $this->assertDatabaseHas('erp_tenant_clients', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Dynamic Client LLC',
            'status' => 'lead',
        ]);

        $newClient = TenantClient::where('name', 'Dynamic Client LLC')->firstOrFail();

        $this->assertDatabaseHas('erp_support_tickets', [
            'tenant_id' => $this->tenant->id,
            'client_id' => $newClient->id,
            'subject' => 'Billing Inquiry',
            'status' => 'open',
            'priority' => 'medium',
        ]);
    }

    public function test_can_resolve_and_close_and_delete_own_support_ticket(): void
    {
        $ticket = SupportTicket::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'subject' => 'Minor Bug',
            'description' => 'Small visual bug.',
            'status' => 'open',
            'priority' => 'low',
            'created_by' => $this->user->id,
        ]);

        // Resolve
        $response = $this->actingAs($this->user)
            ->post(route('erp.tickets.resolve', $ticket->id));
        $response->assertStatus(302);
        $this->assertEquals('resolved', $ticket->fresh()->status);

        // Close
        $response = $this->actingAs($this->user)
            ->post(route('erp.tickets.close', $ticket->id));
        $response->assertStatus(302);
        $this->assertEquals('closed', $ticket->fresh()->status);

        // Delete
        $response = $this->actingAs($this->user)
            ->delete(route('erp.tickets.destroy', $ticket->id));
        $response->assertStatus(302);
        $this->assertDatabaseMissing('erp_support_tickets', ['id' => $ticket->id]);
    }

    public function test_tenant_scoping_prevents_unauthorized_access(): void
    {
        // Create another tenant & user
        $otherUser = User::factory()->create(['onboarding_completed' => true]);
        $otherUser->assignRole('client');

        $otherTenant = Tenant::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Workspace',
            'status' => 'active',
        ]);

        $ticket = SupportTicket::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'subject' => 'Secret Ticket',
            'description' => 'Do not access.',
            'status' => 'open',
            'priority' => 'high',
            'created_by' => $this->user->id,
        ]);

        // Try to resolve as other user
        $response = $this->actingAs($otherUser)
            ->post(route('erp.tickets.resolve', $ticket->id));
        $response->assertStatus(403);

        // Try to close as other user
        $response = $this->actingAs($otherUser)
            ->post(route('erp.tickets.close', $ticket->id));
        $response->assertStatus(403);

        // Try to delete as other user
        $response = $this->actingAs($otherUser)
            ->delete(route('erp.tickets.destroy', $ticket->id));
        $response->assertStatus(403);

        // Ticket should remain untouched
        $this->assertDatabaseHas('erp_support_tickets', [
            'id' => $ticket->id,
            'status' => 'open',
        ]);
    }
}
