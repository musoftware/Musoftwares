<?php

namespace Tests\Feature\Admin;

use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTicketControllerTest extends TestCase
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

    public function test_admin_can_view_tickets_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.tickets.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_view_tickets_index_with_closed_ticket(): void
    {
        Ticket::create([
            'user_id' => $this->clientUser->id,
            'ticket_subject' => 'Closed Ticket Test',
            'ticket_message' => 'This ticket is closed',
            'ticket_status' => 'closed',
            'priority' => 'low',
            'closed_at' => now(),
        ]);

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

    public function test_admin_can_bulk_close_tickets(): void
    {
        $t1 = Ticket::create([
            'user_id' => $this->clientUser->id,
            'ticket_subject' => 'Ticket 1',
            'ticket_message' => 'Help 1',
            'ticket_status' => 'open',
            'priority' => 'low',
        ]);
        $t2 = Ticket::create([
            'user_id' => $this->clientUser->id,
            'ticket_subject' => 'Ticket 2',
            'ticket_message' => 'Help 2',
            'ticket_status' => 'open',
            'priority' => 'medium',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.tickets.bulk'), [
            'action' => 'close',
            'ids' => [$t1->id, $t2->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('closed', $t1->fresh()->ticket_status);
        $this->assertEquals('closed', $t2->fresh()->ticket_status);
        $this->assertNotNull($t1->fresh()->closed_at);
        $this->assertNotNull($t2->fresh()->closed_at);
    }

    public function test_admin_can_bulk_reopen_tickets(): void
    {
        $t1 = Ticket::create([
            'user_id' => $this->clientUser->id,
            'ticket_subject' => 'Ticket 1',
            'ticket_message' => 'Help 1',
            'ticket_status' => 'closed',
            'priority' => 'low',
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.tickets.bulk'), [
            'action' => 'reopen',
            'ids' => [$t1->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('open', $t1->fresh()->ticket_status);
        $this->assertNull($t1->fresh()->closed_at);
    }

    public function test_admin_can_bulk_delete_tickets(): void
    {
        $t1 = Ticket::create([
            'user_id' => $this->clientUser->id,
            'ticket_subject' => 'Ticket 1',
            'ticket_message' => 'Help 1',
            'ticket_status' => 'open',
            'priority' => 'low',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.tickets.bulk'), [
            'action' => 'delete',
            'ids' => [$t1->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify soft-deleted
        $this->assertSoftDeleted('tickets', ['id' => $t1->id]);
    }

    public function test_admin_can_bulk_update_priority(): void
    {
        $t1 = Ticket::create([
            'user_id' => $this->clientUser->id,
            'ticket_subject' => 'Ticket 1',
            'ticket_message' => 'Help 1',
            'ticket_status' => 'open',
            'priority' => 'low',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.tickets.bulk'), [
            'action' => 'priority',
            'ids' => [$t1->id],
            'priority' => 'high',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('high', $t1->fresh()->priority);
    }
}
