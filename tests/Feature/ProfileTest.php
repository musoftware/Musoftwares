<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertSoftDeleted($user);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_impersonator_cannot_delete_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin'); // Assuming Spatie roles

        $user = User::factory()->create();

        // Simulate impersonation session
        $response = $this
            ->actingAs($user)
            ->withSession(['impersonator_id' => $admin->id])
            ->delete('/profile', [
                'password' => 'password', // Even with correct password
            ]);

        $response->assertForbidden();

        $this->assertNotNull($user->fresh());
    }

    public function test_user_cannot_delete_account_with_unpaid_invoices(): void
    {
        $user = User::factory()->create();

        $invoice = new \App\Models\Invoice([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'status' => 'unpaid',
            'unpaid' => 100,
            'paid' => 0,
        ]);
        $invoice->saveQuietly();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_user_can_update_workspace_settings(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile/workspace-settings', [
                'hide_values' => true,
            ]);

        $response->assertRedirect();
        
        $user->refresh();
        $this->assertIsArray($user->workspace_settings);
        $this->assertTrue($user->workspace_settings['hide_values']);

        // Toggle back to false
        $response = $this
            ->actingAs($user)
            ->patch('/profile/workspace-settings', [
                'hide_values' => false,
            ]);

        $response->assertRedirect();
        
        $user->refresh();
        $this->assertFalse($user->workspace_settings['hide_values']);
    }

    public function test_user_can_update_ai_preferences(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile/preferences', [
                'enable_3d_dashboard' => true,
                'default_ai_model' => 'openai',
                'openai_api_key' => 'sk-my-custom-test-key',
                'openai_model' => 'gpt-4o',
                'gemini_api' => 'AIzaSyMyCustomGeminiKey',
                'gemini_model' => 'gemini-2.5-pro',
            ]);

        $response->assertRedirect();
        
        $user->refresh();
        $this->assertTrue($user->enable_3d_dashboard);
        $this->assertSame('openai', $user->default_ai_model);
        $this->assertSame('sk-my-custom-test-key', $user->openai_api_key);
        $this->assertSame('gpt-4o', $user->openai_model);
        $this->assertSame('AIzaSyMyCustomGeminiKey', $user->gemini_api);
        $this->assertSame('gemini-2.5-pro', $user->gemini_model);
    }
}
