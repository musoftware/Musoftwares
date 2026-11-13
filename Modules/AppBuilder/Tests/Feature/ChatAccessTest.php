<?php

namespace Modules\AppBuilder\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\AppBuilder\Models\BuilderProject;
use Tests\TestCase;

/**
 * The AI builder opens only for an account that signed in with Google (no throwaway accounts), and a chat that
 * belongs to an account opens only for that account and for admins.
 */
class ChatAccessTest extends TestCase
{
    use RefreshDatabase;

    private function ownedChat(): array
    {
        $owner = User::factory()->google()->create(['onboarding_completed' => true]);
        $project = BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD', 'user_id' => $owner->id, 'title' => 'نظام']);

        return [$owner, $project];
    }

    private function googleSignIn(string $back): string
    {
        return route('social.google.redirect', ['redirect' => $back]);
    }

    public function test_a_visitor_is_sent_to_google_and_comes_back_to_the_same_page(): void
    {
        [, $project] = $this->ownedChat();

        $this->get('/ai')->assertRedirect($this->googleSignIn('/ai'));
        $this->get('/ai/'.$project->uuid)->assertRedirect($this->googleSignIn('/ai/'.$project->uuid));
        $this->getJson('/ai/'.$project->uuid.'/state')->assertStatus(401)->assertJsonPath('redirect', $this->googleSignIn('/ai/'.$project->uuid.'/state'));
        $this->postJson('/ai/messages', ['text' => 'عايز موقع'])->assertStatus(401);
        // no chat was opened by the visitor
        $this->assertSame(1, BuilderProject::count());
    }

    public function test_an_account_made_with_a_password_must_come_through_google_once(): void
    {
        $this->actingAs(User::factory()->create(['onboarding_completed' => true]));

        $this->get('/ai')->assertRedirect($this->googleSignIn('/ai'));
        $this->postJson('/ai/messages', ['text' => 'عايز موقع'])->assertStatus(401);
    }

    public function test_signing_in_with_google_keeps_its_id_and_opens_the_builder(): void
    {
        config(['services.google.client_id' => 'client-id', 'services.google.client_secret' => 'secret']);
        $user = User::factory()->create(['email' => 'ali@gmail.com', 'onboarding_completed' => true]);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'token']),
            'www.googleapis.com/oauth2/v3/userinfo' => Http::response(['sub' => '1098765', 'email' => 'ali@gmail.com', 'name' => 'Ali']),
        ]);

        $this->get('/auth/google/redirect?redirect=/ai');
        $this->get('/auth/google/callback?code=abc')->assertRedirect(url('/ai'));

        $this->assertSame('1098765', $user->refresh()->provider_id);
        $this->get('/ai')->assertOk();
    }

    public function test_the_payment_webhook_is_not_asked_to_sign_in(): void
    {
        $status = $this->postJson('/ai/webhook', [])->status();

        $this->assertNotContains($status, [302, 401]);
    }

    public function test_another_account_sees_nothing(): void
    {
        [, $project] = $this->ownedChat();

        $this->actingAs(User::factory()->google()->create(['onboarding_completed' => true]));
        $this->get('/ai/'.$project->uuid)->assertNotFound();
        $this->getJson('/ai/'.$project->uuid.'/state')->assertNotFound();
    }

    public function test_the_owner_and_an_admin_can_open_it(): void
    {
        [$owner, $project] = $this->ownedChat();

        $this->actingAs($owner)->get('/ai/'.$project->uuid)->assertOk();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        // an admin is not asked for Google
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        $this->actingAs($admin)->getJson('/ai/'.$project->uuid.'/state')->assertOk();
        // an admin only looks: the chat stays in its owner's account
        $this->assertSame($owner->id, $project->refresh()->user_id);
    }
}
