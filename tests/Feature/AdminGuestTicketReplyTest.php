<?php

namespace Tests\Feature;

use App\Models\GuestTicket;
use App\Models\GuestTicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminGuestTicketReplyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'moderator']);
        Role::firstOrCreate(['name' => 'support_agent']);
    }

    public function test_admin_can_post_reply_and_status_flips_to_replied(): void
    {
        Mail::fake();
        Notification::fake();

        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        $ticket = GuestTicket::create([
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'mobile' => '0123456789',
            'subject' => 'Need help',
            'body' => 'Please help',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.guest-tickets.reply', $ticket), [
            'body' => 'We are looking into it.',
        ]);

        $response->assertRedirect();
        $this->assertSame('replied', $ticket->fresh()->status);
        $this->assertDatabaseHas('guest_ticket_messages', [
            'guest_ticket_id' => $ticket->id,
            'direction' => 'outbound',
            'body_text' => 'We are looking into it.',
        ]);
        Mail::assertSentCount(1);
    }

    public function test_status_transitions_are_enforced(): void
    {
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        $ticket = GuestTicket::create([
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'mobile' => '0123456789',
            'subject' => 'Need help',
            'body' => 'Please help',
            'status' => 'closed',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.guest-tickets.show', $ticket))
            ->post(route('admin.guest-tickets.updateStatus', $ticket), [
                'status' => 'pending',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('status');
        $this->assertSame('closed', $ticket->fresh()->status);
    }
}
