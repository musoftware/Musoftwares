<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserEmail;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleSocialLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['services.google.client_id' => 'mock-google-client-id']);
        config(['services.google.client_secret' => 'mock-google-client-secret']);
    }

    public function test_google_redirect_returns_redirect_url(): void
    {
        $response = $this->get('/auth/google/redirect');

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->getTargetUrl());
    }

    public function test_guest_can_login_with_google_primary_email(): void
    {
        $user = User::factory()->create([
            'email' => 'primary@example.com',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'mock-token'], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'email' => 'primary@example.com',
                'name' => 'Primary User',
            ], 200),
        ]);

        $response = $this->get('/auth/google/callback?code=mock-auth-code');

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_can_login_with_google_secondary_alias_email(): void
    {
        $user = User::factory()->create([
            'email' => 'primary@example.com',
        ]);

        UserEmail::create([
            'user_id' => $user->id,
            'email' => 'alias@example.com',
            'verified_at' => now(),
            'source' => UserEmail::SOURCE_SELF,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'mock-token'], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'email' => 'ALIAS@example.com',
                'name' => 'Alias User',
            ], 200),
        ]);

        $response = $this->get('/auth/google/callback?code=mock-auth-code');

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_google_login_creates_new_user_if_not_found(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'mock-token'], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'email' => 'newuser@example.com',
                'name' => 'New User',
            ], 200),
        ]);

        $response = $this->get('/auth/google/callback?code=mock-auth-code');

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
        ]);
        $this->assertAuthenticated();
    }

    public function test_authenticated_user_linking_new_google_email(): void
    {
        $user = User::factory()->create(['email' => 'main@example.com']);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'mock-token'], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'email' => 'secondary.google@example.com',
                'name' => 'Secondary Google Account',
            ], 200),
        ]);

        $response = $this->actingAs($user)->get('/auth/google/callback?code=mock-auth-code');

        $response->assertRedirect(route('profile.edit'));
        $this->assertDatabaseHas('user_emails', [
            'user_id' => $user->id,
            'email' => 'secondary.google@example.com',
        ]);
    }

    public function test_authenticated_user_linking_already_linked_email_of_same_user(): void
    {
        $user = User::factory()->create(['email' => 'main@example.com']);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'mock-token'], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'email' => 'main@example.com',
                'name' => 'Main Account',
            ], 200),
        ]);

        $response = $this->actingAs($user)->get('/auth/google/callback?code=mock-auth-code');

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status');
    }

    public function test_authenticated_user_linking_google_email_owned_by_another_user_fails(): void
    {
        $userA = User::factory()->create(['email' => 'usera@example.com']);
        $userB = User::factory()->create(['email' => 'userb@example.com']);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'mock-token'], 200),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'email' => 'userb@example.com',
                'name' => 'User B',
            ], 200),
        ]);

        $response = $this->actingAs($userA)->get('/auth/google/callback?code=mock-auth-code');

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('user_emails', [
            'user_id' => $userA->id,
            'email' => 'userb@example.com',
        ]);
    }

    public function test_user_can_make_secondary_email_primary(): void
    {
        $user = User::factory()->create(['email' => 'primary@example.com']);
        $alias = UserEmail::create([
            'user_id' => $user->id,
            'email' => 'secondary@example.com',
            'verified_at' => now(),
            'source' => UserEmail::SOURCE_SELF,
        ]);

        $response = $this->actingAs($user)->post("/profile/emails/{$alias->id}/make-primary");

        $response->assertRedirect(route('profile.edit'));
        $this->assertSame('secondary@example.com', $user->fresh()->email);
        $this->assertDatabaseHas('user_emails', [
            'user_id' => $user->id,
            'email' => 'primary@example.com',
        ]);
    }

    public function test_admin_can_make_alias_email_primary(): void
    {
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        $user = User::factory()->create(['email' => 'oldprimary@example.com']);
        $alias = UserEmail::create([
            'user_id' => $user->id,
            'email' => 'newprimary@example.com',
            'verified_at' => now(),
            'source' => UserEmail::SOURCE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/emails/{$alias->id}/make-primary");

        $response->assertRedirect(route('admin.users.emails.index', $user->id));
        $this->assertSame('newprimary@example.com', $user->fresh()->email);
        $this->assertDatabaseHas('user_emails', [
            'user_id' => $user->id,
            'email' => 'oldprimary@example.com',
        ]);
    }
}
