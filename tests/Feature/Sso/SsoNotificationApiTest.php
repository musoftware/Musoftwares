<?php

namespace Tests\Feature\Sso;

use App\Models\User;
use App\Notifications\GoldSystemNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SsoNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.goldsaversys.shared_secret' => 'test-secret-key-123']);
    }

    public function test_it_rejects_requests_without_headers(): void
    {
        $response = $this->postJson('/api/sso/notify', [
            'email' => 'test@example.com',
            'title' => 'Test',
            'message' => 'Hello',
            'channels' => ['mail'],
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'missing_signature_headers']);
    }

    public function test_it_rejects_requests_with_invalid_signature(): void
    {
        $timestamp = (string) now()->timestamp;
        $response = $this->withHeaders([
            'X-GoldSaver-Signature' => 'invalid-sig',
            'X-GoldSaver-Timestamp' => $timestamp,
            'X-GoldSaver-System' => 'goldsaversys',
        ])->postJson('/api/sso/notify', [
            'email' => 'test@example.com',
            'title' => 'Test',
            'message' => 'Hello',
            'channels' => ['mail'],
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'invalid_signature']);
    }

    public function test_it_sends_notification_to_user_when_valid_signature_provided(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'client@example.com']);
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.sso-notify', 'test-secret-key-123');

        $response = $this->withHeaders([
            'X-GoldSaver-Signature' => $signature,
            'X-GoldSaver-Timestamp' => $timestamp,
            'X-GoldSaver-System' => 'goldsaversys',
        ])->postJson('/api/sso/notify', [
            'monolith_user_id' => $user->id,
            'title' => 'Your Gold Report',
            'message' => 'Your portfolio profit is up!',
            'channels' => ['mail', 'whatsapp'],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        Notification::assertSentTo(
            $user,
            GoldSystemNotification::class,
            function ($notification, $channels) {
                return $notification->title === 'Your Gold Report'
                    && $notification->messageContent === 'Your portfolio profit is up!'
                    && in_array('mail', $channels)
                    && in_array('whatsapp', $channels);
            }
        );
    }
}
