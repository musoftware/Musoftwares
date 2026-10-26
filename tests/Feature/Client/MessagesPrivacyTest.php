<?php

namespace Tests\Feature\Client;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * /messages used to send every user's name and e-mail to every client. Clients may now only write
 * to our team and to people they already talk to, and never receive other people's e-mails.
 */
class MessagesPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes + ['onboarding_completed' => true]);
        $user->assignRole($role);

        return $user;
    }

    public function test_client_sees_only_the_team_and_no_emails(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $client = $this->user('client');
        $stranger = $this->user('client', ['name' => 'Other Customer', 'email' => 'other.customer@example.com']);
        $agent = $this->user('support_agent', ['name' => 'Support Sara', 'email' => 'sara@musoftwares.com']);

        $response = $this->actingAs($client)->get('/messages');
        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('users', 1)
            ->where('users.0.id', $agent->id)
            ->where('users.0.email', null)
        );

        $props = json_encode($response->viewData('page')['props']);
        $this->assertStringNotContainsString('other.customer@example.com', $props);
        $this->assertStringNotContainsString('Other Customer', $props);
        $this->assertStringNotContainsString('sara@musoftwares.com', $props);
    }

    public function test_client_cannot_start_a_conversation_with_a_stranger_but_can_with_the_team(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $client = $this->user('client');
        $stranger = $this->user('client');
        $agent = $this->user('support_agent');

        $this->actingAs($client)->post(route('messages.direct.store'), ['recipient_id' => $stranger->id, 'message' => 'hi'])->assertForbidden();
        $this->actingAs($client)->get('/messages?recipient_id='.$stranger->id)->assertInertia(fn (Assert $page) => $page->where('activeConversationId', null));

        $this->actingAs($client)->post(route('messages.direct.store'), ['recipient_id' => $agent->id, 'message' => 'hi'])->assertRedirect();
        $this->assertDatabaseHas('messages', ['sender_id' => $client->id, 'body' => 'hi']);
    }

    public function test_message_notification_names_the_sender_and_opens_the_conversation(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $client = $this->user('client');
        $agent = $this->user('support_agent', ['name' => 'Support Sara']);
        $stranger = $this->user('client');

        $this->actingAs($agent)->post(route('messages.direct.store'), ['recipient_id' => $client->id, 'message' => 'test']);
        $notification = $client->notifications()->latest()->firstOrFail();
        $conversationId = $notification->data['conversation_id'];

        $this->assertSame('New message from Support Sara', $notification->data['title']);
        $this->actingAs($client)->post(route('notifications.mark-read', ['id' => $notification->id]))
            ->assertRedirect('/messages?conversation='.$conversationId);
        $this->actingAs($client)->get('/messages?conversation='.$conversationId)
            ->assertInertia(fn (Assert $page) => $page->where('activeConversationId', $conversationId));

        // someone who is not in the conversation cannot open it through the link
        $this->actingAs($stranger)->get('/messages?conversation='.$conversationId)
            ->assertInertia(fn (Assert $page) => $page->where('activeConversationId', null));
    }

    public function test_admin_still_sees_everyone_with_emails(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = $this->user('admin');
        $client = $this->user('client', ['email' => 'visible@example.com']);

        $this->actingAs($admin)->get('/messages')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('users', fn ($users) => collect($users)->contains(fn ($u) => $u['id'] === $client->id && $u['email'] === 'visible@example.com'))
        );
    }
}
