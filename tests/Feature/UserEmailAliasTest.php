<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserEmail;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEmailAliasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_login_with_primary_email_still_works(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_login_with_a_verified_alias_email_resolves_owner(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123')]);
        $alias = UserEmail::create([
            'user_id' => $user->id,
            'email' => 'm.alias@example.com',
            'verified_at' => now(),
            'source' => UserEmail::SOURCE_ADMIN,
        ]);

        $response = $this->post('/login', [
            'email' => strtoupper($alias->email), // case-insensitive lookup
            'password' => 'secret123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_login_throttle_key_uses_typed_email_not_resolved_user(): void
    {
        User::factory()->create([
            'email' => 'owner@example.com',
            'password' => bcrypt('secret123'),
        ]);

        // Typing a wrong password for `owner@example.com` should be the throttle key.
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'owner@example.com',
                'password' => 'wrong',
            ])->assertSessionHasErrors('email');
        }

        // Next attempt (anybody) at the same email is throttled.
        $this->post('/login', [
            'email' => 'owner@example.com',
            'password' => 'secret123',
        ])->assertSessionHasErrors('email');
    }

    public function test_admin_can_create_alias(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/emails", [
            'email' => 'second@example.com',
            'verified_at' => 1,
        ]);

        $response->assertRedirect(route('admin.users.emails.index', $user->id));
        $this->assertDatabaseHas('user_emails', [
            'user_id' => $user->id,
            'email' => 'second@example.com',
        ]);
    }

    public function test_admin_cannot_attach_email_owned_by_another_user(): void
    {
        $admin = $this->makeAdmin();
        $owner = User::factory()->create(['email' => 'taken@example.com']);
        $target = User::factory()->create();

        $response = $this->actingAs($admin)
            ->from("/admin/users/{$target->id}/emails")
            ->post("/admin/users/{$target->id}/emails", [
                'email' => $owner->email,
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_remove_alias_and_verify_it(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create();
        $alias = UserEmail::create([
            'user_id' => $user->id,
            'email' => 'rev@example.com',
            'verified_at' => null,
            'source' => UserEmail::SOURCE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->post("/admin/users/{$user->id}/emails/{$alias->id}/verify")
            ->assertRedirect();

        $this->assertNotNull($alias->fresh()->verified_at);

        $this->actingAs($admin)
            ->delete("/admin/users/{$user->id}/emails/{$alias->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('user_emails', ['id' => $alias->id]);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_owns_email_matches_primary_and_aliases(): void
    {
        $user = User::factory()->create(['email' => 'primary@example.com']);
        UserEmail::create([
            'user_id' => $user->id,
            'email' => 'also@example.com',
            'verified_at' => now(),
        ]);

        $this->assertTrue($user->ownsEmail('primary@example.com'));
        $this->assertTrue($user->ownsEmail('ALSO@example.com'));
        $this->assertFalse($user->ownsEmail('unknown@example.com'));
    }
}
