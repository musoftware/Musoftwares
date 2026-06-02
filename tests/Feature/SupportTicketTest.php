<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Ticket;
use App\Models\Conversation;
use App\Models\Message;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use DatabaseTransactions;

    protected User $client;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->client = User::factory()->create();
        $this->client->assignRole('client');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->admin->load('roles');
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
        $this->assertDatabaseHas('tickets', [
            'user_id' => $this->client->id,
            'ticket_subject' => 'Cannot update billing method',
            'ticket_status' => 'open',
            'priority' => 'high',
        ]);

        $ticket = Ticket::where('ticket_subject', 'Cannot update billing method')->first();

        // Assert conversation is automatically created and morphed
        $this->assertDatabaseHas('conversations', [
            'conversable_type' => Ticket::class,
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
        $ticket = Ticket::create([
            'user_id' => $this->client->id,
            'ticket_subject' => 'Database issue',
            'ticket_message' => 'Database connection issues.',
            'ticket_status' => 'open',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('tickets.index'));

        $response->assertStatus(200);
    }

    public function test_can_resolve_ticket(): void
    {
        $ticket = Ticket::create([
            'user_id' => $this->client->id,
            'ticket_subject' => 'Resolved issue',
            'ticket_message' => 'This issue will be resolved.',
            'ticket_status' => 'open',
            'priority' => 'low',
        ]);

        $conversation = Conversation::create([
            'conversable_type' => Ticket::class,
            'conversable_id' => $ticket->id,
            'type' => 'support_ticket',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('tickets.resolve', $ticket->id));

        $response->assertStatus(302);
        $this->assertEquals('closed', $ticket->fresh()->ticket_status);
        $this->assertEquals('closed', $conversation->fresh()->status);
    }
}

