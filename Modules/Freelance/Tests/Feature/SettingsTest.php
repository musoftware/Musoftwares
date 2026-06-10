<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\User;
use Modules\Freelance\Models\FreelanceProfile;
use Carbon\Carbon;

it('allows an authenticated user to view the notifications settings page', function () {
    $this->withoutMiddleware([\App\Http\Middleware\EnsureOnboardingCompleted::class, \App\Http\Middleware\SubscriptionMiddleware::class]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/freelance/settings/notifications');

    $response->assertStatus(200);
});

it('allows an authenticated user to update their notifications settings and mute duration', function () {
    $this->withoutMiddleware([\App\Http\Middleware\EnsureOnboardingCompleted::class, \App\Http\Middleware\SubscriptionMiddleware::class]);

    $user = User::factory()->create();

    $payload = [
        'receive_job_notifications' => true,
        'mute_duration' => '1_week',
    ];

    $response = $this->actingAs($user)->put('/freelance/settings/notifications', $payload);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $profile = FreelanceProfile::where('user_id', $user->id)->first();
    
    $this->assertEquals(1, $profile->receive_job_notifications);
    $this->assertNotNull($profile->notifications_muted_until);
    $this->assertTrue(Carbon::parse($profile->notifications_muted_until)->isFuture());
});
