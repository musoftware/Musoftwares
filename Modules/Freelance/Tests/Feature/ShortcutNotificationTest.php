<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

it('generates a new sanctum token for the iOS shortcut via web route', function () {
    $this->withoutMiddleware([\App\Http\Middleware\EnsureOnboardingCompleted::class, \App\Http\Middleware\SubscriptionMiddleware::class]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/freelance/settings/notifications/shortcut-token');

    $response->assertSessionHas('success', __('freelance.ios_shortcut_token_generated'));
    $response->assertSessionHas('ios_shortcut_token');
    $response->assertRedirect();

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
        'name' => 'ios-shortcut',
    ]);
});

it('fetches unread notifications via api route for iOS shortcut', function () {
    $user = User::factory()->create();
    $token = $user->createToken('ios-shortcut')->plainTextToken;

    // Create a mock notification manually in DB since we can't easily trigger a real one without complex setup here
    $notificationId = \Illuminate\Support\Str::uuid()->toString();
    \DB::table('notifications')->insert([
        'id' => $notificationId,
        'type' => 'App\Notifications\Freelance\NewProposalNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => json_encode(['title' => 'Test Notification', 'message' => 'This is a test notification.']),
        'read_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
    ])->getJson('/api/freelance/shortcut/notifications');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'notifications' => [
            '*' => [
                'id',
                'title',
                'body',
                'url',
            ]
        ]
    ]);
    
    $response->assertJsonFragment([
        'success' => true,
        'title' => 'Test Notification',
        'body' => 'This is a test notification.',
    ]);
    
    // Check if last_shortcut_sync_at was updated
    $user->refresh();
    $this->assertNotNull($user->last_shortcut_sync_at);
});
