<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\SupportTicket;
use App\Models\GuestTicket;

class SupportAgentRoleIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure roles exist
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'moderator']);
        Role::firstOrCreate(['name' => 'support_agent']);
    }

    public function test_support_agent_can_access_tickets_index()
    {
        $user = User::factory()->create(['onboarding_completed' => true]);
        $user->assignRole('support_agent');

        $response = $this->actingAs($user)->get(route('admin.tickets.index'));

        $response->assertStatus(200);
    }

    public function test_support_agent_can_access_guest_tickets_index()
    {
        $user = User::factory()->create(['onboarding_completed' => true]);
        $user->assignRole('support_agent');

        $response = $this->actingAs($user)->get(route('admin.guest-tickets.index'));

        $response->assertStatus(200);
    }

    public function test_support_agent_can_post_guest_ticket_reply_and_update_status()
    {
        $user = User::factory()->create(['onboarding_completed' => true]);
        $user->assignRole('support_agent');

        \Illuminate\Support\Facades\Mail::fake();

        $ticket = GuestTicket::create([
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'mobile' => '0123456789',
            'subject' => 'Help',
            'body' => 'Please',
            'status' => 'pending',
        ]);

        $replyResponse = $this->actingAs($user)
            ->post(route('admin.guest-tickets.reply', $ticket), ['body' => 'Replying']);
        $replyResponse->assertRedirect();

        $statusResponse = $this->actingAs($user)
            ->post(route('admin.guest-tickets.updateStatus', $ticket), ['status' => 'closed']);
        $statusResponse->assertRedirect();
        $this->assertSame('closed', $ticket->fresh()->status);
    }

    public function test_support_agent_cannot_access_dashboard()
    {
        $user = User::factory()->create(['onboarding_completed' => true]);
        $user->assignRole('support_agent');

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }
    
    public function test_support_agent_cannot_access_website_services()
    {
        $user = User::factory()->create(['onboarding_completed' => true]);
        $user->assignRole('support_agent');

        $response = $this->actingAs($user)->get(route('admin.website-services.index'));

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_access_tickets()
    {
        $user = User::factory()->create(['onboarding_completed' => true]);

        $response = $this->actingAs($user)->get(route('admin.tickets.index'));

        $response->assertStatus(403);
    }
    
    public function test_regular_user_cannot_access_guest_tickets()
    {
        $user = User::factory()->create(['onboarding_completed' => true]);

        $response = $this->actingAs($user)->get(route('admin.guest-tickets.index'));

        $response->assertStatus(403);
    }
}
