<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AdminTicketControllerTest extends TestCase
{
    use DatabaseTransactions;

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

    public function test_admin_can_view_tickets_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.tickets.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_tickets_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.tickets.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_ticket_show(): void
    {
        $ticket = Ticket::create([
            'user_id' => $this->clientUser->id,
            'ticket_subject' => 'Help me',
            'ticket_message' => 'Need help',
            'ticket_status' => 'open',
            'priority' => 'low',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.tickets.show', $ticket->id));
        $response->assertStatus(200);
    }

    public function test_admin_can_update_ticket_status(): void
    {
        $ticket = Ticket::create([
            'user_id' => $this->clientUser->id,
            'ticket_subject' => 'Help me',
            'ticket_message' => 'Need help',
            'ticket_status' => 'open',
            'priority' => 'low',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.tickets.update', $ticket->id), [
            'action' => 'close',
            'comment' => 'Closing ticket',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('closed', $ticket->fresh()->ticket_status);
    }

    public function test_admin_update_ticket_validation(): void
    {
        $ticket = Ticket::create([
            'user_id' => $this->clientUser->id,
            'ticket_subject' => 'Help me',
            'ticket_message' => 'Need help',
            'ticket_status' => 'open',
            'priority' => 'low',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.tickets.update', $ticket->id), [
            'action' => 'invalid_action',
        ]);

        $response->assertSessionHasErrors('action');
    }

    public function test_admin_can_reply_to_ticket(): void
    {
        $ticket = Ticket::create([
            'user_id' => $this->clientUser->id,
            'ticket_subject' => 'Help me',
            'ticket_message' => 'Need help',
            'ticket_status' => 'open',
            'priority' => 'low',
        ]);
        
        $ticket->conversation()->create(['type' => 'ticket']);

        $response = $this->actingAs($this->admin)->post(route('admin.tickets.reply', $ticket->id), [
            'body' => 'Here is the answer',
            'is_internal' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_reply_ticket_validation(): void
    {
        $ticket = Ticket::create([
            'user_id' => $this->clientUser->id,
            'ticket_subject' => 'Help me',
            'ticket_message' => 'Need help',
            'ticket_status' => 'open',
            'priority' => 'low',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.tickets.reply', $ticket->id), [
            'body' => '',
            'is_internal' => false,
        ]);

        $response->assertSessionHasErrors('body');
    }

    public function test_admin_can_assign_ticket(): void
    {
        $ticket = Ticket::create([
            'user_id' => $this->clientUser->id,
            'ticket_subject' => 'Help me',
            'ticket_message' => 'Need help',
            'ticket_status' => 'open',
            'priority' => 'low',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.tickets.assign', $ticket->id), [
            'assigned_employee_id' => $this->admin->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals($this->admin->id, $ticket->fresh()->assigned_employee_id);
    }

    public function test_admin_assign_ticket_validation(): void
    {
        $ticket = Ticket::create([
            'user_id' => $this->clientUser->id,
            'ticket_subject' => 'Help me',
            'ticket_message' => 'Need help',
            'ticket_status' => 'open',
            'priority' => 'low',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.tickets.assign', $ticket->id), [
            'assigned_employee_id' => 99999, // Invalid ID
        ]);

        $response->assertSessionHasErrors('assigned_employee_id');
    }

    public function test_admin_can_add_canned_response(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.tickets.canned-responses.store'), [
            'title' => 'Greeting',
            'body' => 'Hello there!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ticket_canned_responses', [
            'title' => 'Greeting',
            'body' => 'Hello there!',
        ]);
    }

    public function test_admin_add_canned_response_validation(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.tickets.canned-responses.store'), [
            'title' => '',
            'body' => 'Hello there!',
        ]);

        $response->assertSessionHasErrors('title');
    }
}
