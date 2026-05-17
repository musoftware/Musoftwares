<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\SupportTicket;
use Modules\Core\Models\Conversation;
use Modules\Core\Models\Message;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    protected User $client;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_client_can_view_own_tickets(): void
    {
        $response = $this->actingAs($this->client)
            ->get(route('tickets.index'));

        $response->assertStatus(200);
    }

    public function test_client_can_open_support_ticket_workflow(): void
    {
        $response = $this->actingAs($this->client)
            ->post(route('tickets.store'), [
                'subject' => 'Cannot update billing method',
                'priority' => 'High',
                'description' => 'I get a 500 error when trying to save my credit card details.',
            ]);

        $response->assertStatus(302);

        // Assert support ticket is created in the database
        $this->assertDatabaseHas('support_tickets', [
            'client_id' => $this->client->id,
            'subject' => 'Cannot update billing method',
            'status' => 'open',
            'priority' => 'high',
        ]);

        $ticket = SupportTicket::where('subject', 'Cannot update billing method')->first();

        // Assert conversation is automatically created and morphed
        $this->assertDatabaseHas('conversations', [
            'conversable_type' => SupportTicket::class,
            'conversable_id' => $ticket->id,
            'type' => 'support_ticket',
            'status' => 'open',
        ]);

        $conversation = Conversation::where('conversable_id', $ticket->id)->first();

        // Assert description is saved as the first message
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->client->id,
            'body' => 'I get a 500 error when trying to save my credit card details.',
        ]);
    }

    public function test_admin_can_view_all_tickets(): void
    {
        // Create a ticket for client
        $ticket = SupportTicket::create([
            'client_id' => $this->client->id,
            'subject' => 'Database issue',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('tickets.index'));

        $response->assertStatus(200);
    }

    public function test_can_resolve_ticket(): void
    {
        $ticket = SupportTicket::create([
            'client_id' => $this->client->id,
            'subject' => 'Resolved issue',
            'status' => 'open',
            'priority' => 'low',
        ]);

        $conversation = Conversation::create([
            'conversable_type' => SupportTicket::class,
            'conversable_id' => $ticket->id,
            'type' => 'support_ticket',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('tickets.resolve', $ticket->id));

        $response->assertStatus(302);
        $this->assertEquals('resolved', $ticket->fresh()->status);
        $this->assertEquals('closed', $conversation->fresh()->status);
    }
}
