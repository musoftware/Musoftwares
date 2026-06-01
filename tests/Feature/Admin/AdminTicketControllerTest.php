<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTicketControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function createAdmin()
    {
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        return $admin;
    }

    private function createClient()
    {
        $client = User::factory()->create(['onboarding_completed' => true]);
        $client->assignRole('client');
        return $client;
    }

    public function test_admin_can_access_tickets_index()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.tickets.index'));

        $response->assertSuccessful();
    }

    public function test_non_admin_cannot_access_tickets_index()
    {
        $client = $this->createClient();

        $response = $this->actingAs($client)->get(route('admin.tickets.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_ticket()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();

        $ticket = Ticket::forceCreate([
            'user_id' => $client->id,
            'ticket_subject' => 'Test Ticket',
            'ticket_message' => 'test body',
            'ticket_status' => 'open',
            'priority' => 'medium'
        ]);

        $response = $this->actingAs($admin)->get(route('admin.tickets.show', $ticket->id));

        $response->assertSuccessful();
    }

    public function test_admin_can_update_ticket_action_close()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();

        $ticket = Ticket::forceCreate([
            'user_id' => $client->id,
            'ticket_subject' => 'Test Ticket',
            'ticket_message' => 'test body',
            'ticket_status' => 'open',
            'priority' => 'medium'
        ]);

        $response = $this->actingAs($admin)->put(route('admin.tickets.update', $ticket->id), [
            'action' => 'close'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'ticket_status' => 'closed'
        ]);
    }
}
