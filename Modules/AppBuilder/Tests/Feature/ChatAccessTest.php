<?php

namespace Modules\AppBuilder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AppBuilder\Models\BuilderProject;
use Tests\TestCase;

/** A chat that belongs to an account opens only for that account, for admins, and for the browser that started it. */
class ChatAccessTest extends TestCase
{
    use RefreshDatabase;

    private function ownedChat(): array
    {
        $owner = \App\Models\User::factory()->create(['onboarding_completed' => true]);
        $project = BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD', 'user_id' => $owner->id, 'title' => 'نظام']);

        return [$owner, $project];
    }

    public function test_a_guest_is_sent_to_sign_in_and_another_account_sees_nothing(): void
    {
        [, $project] = $this->ownedChat();

        $this->get('/ai/'.$project->uuid)->assertRedirect(route('login', ['redirect' => '/ai/'.$project->uuid]));
        $this->getJson('/ai/'.$project->uuid.'/state')->assertStatus(401);
        $this->postJson('/ai/'.$project->uuid.'/messages', ['text' => 'انت مين؟'])->assertStatus(401);

        $this->actingAs(\App\Models\User::factory()->create(['onboarding_completed' => true]));
        $this->get('/ai/'.$project->uuid)->assertNotFound();
        $this->getJson('/ai/'.$project->uuid.'/state')->assertNotFound();
    }

    public function test_the_owner_and_an_admin_can_open_it(): void
    {
        [$owner, $project] = $this->ownedChat();

        $this->actingAs($owner)->get('/ai/'.$project->uuid)->assertOk();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $admin = \App\Models\User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        $this->actingAs($admin)->getJson('/ai/'.$project->uuid.'/state')->assertOk();
        // an admin only looks: the chat stays in its owner's account
        $this->assertSame($owner->id, $project->refresh()->user_id);
    }

    public function test_a_chat_without_an_account_stays_open_to_its_link(): void
    {
        $project = BuilderProject::create(['ip' => '1.1.1.1', 'locale' => 'ar', 'currency' => 'USD']);

        $this->getJson('/ai/'.$project->uuid.'/state')->assertOk();
    }
}
