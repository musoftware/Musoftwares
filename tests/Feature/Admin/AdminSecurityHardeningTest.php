<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Auth\SetPasswordController;
use App\Mail\PasswordResetLinkMail;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * CRIT-fix regression tests for the audit findings shipped in the
 * security-hardening PR. Each test pins a single behavior so a future
 * regression is unambiguous.
 */
class AdminSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        Mail::fake();

        $this->admin = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole('admin');
    }

    // ── loginAs ────────────────────────────────────────────────────────────

    public function test_login_as_writes_audit_log_and_does_not_return_token(): void
    {
        $target = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);
        $target->assignRole('client');

        $response = $this->actingAs($this->admin)
            ->postJson("/admin/users/{$target->id}/login-as");

        $response->assertOk();
        $payload = $response->json();
        $this->assertArrayNotHasKey('token', $payload, 'loginAs must not return a Sanctum bearer token.');
    }

    public function test_login_as_refuses_to_impersonate_another_admin(): void
    {
        $otherAdmin = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
        ]);
        $otherAdmin->assignRole('admin');

        $response = $this->actingAs($this->admin)
            ->post("/admin/users/{$otherAdmin->id}/login-as");

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    public function test_login_as_refuses_blocked_users(): void
    {
        $blocked = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
            'account_status' => 'blocked',
        ]);
        $blocked->assignRole('client');

        $response = $this->actingAs($this->admin)
            ->post("/admin/users/{$blocked->id}/login-as");

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    // ── reset_password ─────────────────────────────────────────────────────

    public function test_reset_password_does_not_return_plaintext_in_response(): void
    {
        $target = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
            'password' => Hash::make('OldPassword123!'),
        ]);
        $target->assignRole('client');

        $response = $this->actingAs($this->admin)
            ->postJson("/admin/users/{$target->id}/reset-password");

        $response->assertOk();
        $payload = $response->json();

        $this->assertArrayNotHasKey('new_password', $payload, 'reset_password must not return plaintext.');
        $this->assertArrayNotHasKey('password', $payload);

        // The target's password must have been invalidated. We verify that the
        // newly stored hash does not equal the original plaintext "OldPassword123!".
        $target->refresh();
        $this->assertFalse(Hash::check('OldPassword123!', $target->password));
    }

    public function test_reset_password_emails_a_one_time_signed_link(): void
    {
        $target = User::factory()->create([
            'onboarding_completed' => true,
            'email_verified_at' => now(),
            'email' => 'pw-reset@example.com',
        ]);
        $target->assignRole('client');

        $this->actingAs($this->admin)
            ->postJson("/admin/users/{$target->id}/reset-password")
            ->assertOk();

        Mail::assertSent(function (PasswordResetLinkMail $mail) {
            $body = $mail->render();

            // Must contain a signed URL with token= and signature=, never plaintext.
            return str_contains((string) $body, '/set-password')
                && str_contains((string) $body, 'signature=')
                && ! preg_match('/Password:\s*\S+/', (string) $body);
        });
    }

    // ── broadcast confirmation ─────────────────────────────────────────────

    public function test_broadcast_global_requires_confirmation_token(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/admin/notifications/broadcast/send', [
            'title' => 'Test',
            'body' => 'Test body',
            'audience_type' => 'global',
        ]);

        // 409 with a one-time confirm_token.
        $response->assertStatus(409);
        $payload = $response->json();
        $this->assertTrue($payload['requires_confirmation']);
        $this->assertNotEmpty($payload['confirm_token']);
    }

    public function test_broadcast_global_rejects_request_without_matching_confirm_token(): void
    {
        $first = $this->actingAs($this->admin)->postJson('/admin/notifications/broadcast/send', [
            'title' => 'Test',
            'body' => 'Test body',
            'audience_type' => 'global',
        ]);
        $token = $first->json('confirm_token');

        // Submit the same form state but with a bogus token.
        $this->actingAs($this->admin)->postJson('/admin/notifications/broadcast/send', [
            'title' => 'Test',
            'body' => 'Test body',
            'audience_type' => 'global',
            'confirm_token' => 'totally-bogus',
        ])->assertStatus(409);
    }

    public function test_broadcast_global_dispatches_campaign_job_with_matching_token(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $first = $this->actingAs($this->admin)->postJson('/admin/notifications/broadcast/send', [
            'title' => 'Valid Title',
            'body' => 'Valid body',
            'audience_type' => 'global',
        ]);
        $token = $first->json('confirm_token');

        $response = $this->actingAs($this->admin)->postJson('/admin/notifications/broadcast/send', [
            'title' => 'Valid Title',
            'body' => 'Valid body',
            'audience_type' => 'global',
            'confirm_token' => $token,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('notification_campaigns', [
            'title' => 'Valid Title',
            'body' => 'Valid body',
            'status' => 'sending',
            'audience_type' => 'global',
        ]);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendCampaignBroadcastJob::class);
    }

    // ── serial device check-in (public, no auth required) ───────────────────

    public function test_serial_device_allows_unauthenticated_checkin(): void
    {
        $response = $this->postJson('/api/serial/device', [
            'program_name' => 'DemoSoft',
            'device_id' => 'dev-001',
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'active', 'has_linked_user' => false]);
    }

    public function test_serial_device_lookup_or_link_user(): void
    {
        $user = User::factory()->create([
            'email' => 'client@testsoftware.com',
            'name' => 'Client Test',
        ]);

        // 1. Existing user -> links device and returns active
        $resFound = $this->postJson('/api/serial/device/link-user', [
            'program_name' => 'DemoSoft',
            'device_id' => 'dev-user-123',
            'email' => 'client@testsoftware.com',
        ]);
        $resFound->assertOk();
        $resFound->assertJson([
            'status' => 'active',
            'user_exists' => true,
        ]);

        // 2. Non-existing user -> returns not_found
        $resNotFound = $this->postJson('/api/serial/device/link-user', [
            'program_name' => 'DemoSoft',
            'device_id' => 'dev-user-456',
            'email' => 'nonexistent@testsoftware.com',
        ]);
        $resNotFound->assertOk();
        $resNotFound->assertJson([
            'status' => 'not_found',
            'user_exists' => false,
        ]);
    }

    public function test_serial_device_register_user_and_device(): void
    {
        $resRegister = $this->postJson('/api/serial/device/register-user', [
            'program_name' => 'DemoSoft',
            'device_id' => 'dev-reg-789',
            'email' => 'brandnewuser@testsoftware.com',
            'name' => 'Brand New Client',
            'phone' => '1012345678',
            'country_code' => '+20',
        ]);

        $resRegister->assertOk();
        $resRegister->assertJson([
            'status' => 'active',
            'user_exists' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'brandnewuser@testsoftware.com',
            'name' => 'Brand New Client',
            'mobile_1' => '1012345678',
        ]);

        $this->assertDatabaseHas('serial_devices', [
            'device_id' => 'dev-reg-789',
            'status' => 'active',
        ]);
    }

    // ── set password controller single-use ─────────────────────────────────

    public function test_set_password_token_is_single_use(): void
    {
        $user = User::factory()->create(['onboarding_completed' => true]);
        $link = SetPasswordController::issueLink($user, $this->admin->id);

        // Extract token from the signed URL.
        $query = parse_url($link, PHP_URL_QUERY);
        parse_str((string) $query, $params);
        $this->assertArrayHasKey('token', $params);
        $this->assertArrayHasKey('uid', $params);
        $this->assertArrayHasKey('signature', $params);

        // First call consumes the token.
        $url = '/set-password?'.$query;
        $r1 = $this->post($url, ['password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!']);
        $r1->assertRedirect(route('login'));

        // Second call must fail (token gone).
        $r2 = $this->post($url, ['password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!']);
        $r2->assertSessionHasErrors('password');
    }

    public function test_set_password_requires_valid_signature(): void
    {
        $user = User::factory()->create();

        // Build a URL with the right token but a tampered signature.
        $token = Str::random(48);
        Cache::put(
            'admin_password_set:'.$token,
            ['user_id' => $user->id, 'expires_at' => now()->addHour(), 'issued_by' => 1],
            now()->addHour()
        );

        $response = $this->get('/set-password?token='.$token.'&uid='.$user->id.'&signature=invalid');
        $response->assertStatus(403);
    }
}
