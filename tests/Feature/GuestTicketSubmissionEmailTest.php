<?php

namespace Tests\Feature;

use App\Models\GuestTicket;
use App\Models\GuestTicketMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuestTicketSubmissionEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
        Mail::fake();
        Notification::fake();
    }

    public function test_public_submission_sends_confirmation_and_notification(): void
    {
        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->post(route('guest-tickets.submit'), [
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'mobile' => '0123456789',
            'body' => 'I need help with onboarding.',
            'subject' => 'Onboarding help',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('guest_tickets', [
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'status' => 'pending',
        ]);
        Mail::assertSentCount(1);

        $ticket = GuestTicket::where('email', 'guest@example.com')->firstOrFail();
        $this->assertDatabaseHas('guest_ticket_messages', [
            'guest_ticket_id' => $ticket->id,
            'direction' => 'outbound',
        ]);
        $this->assertNotNull($ticket->last_message_message_id);
    }
}
