<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\User;
use Modules\Freelance\Models\FreelanceProfile;

it('allows an authenticated user to view the edit profile page', function () {
    $this->withoutMiddleware([\App\Http\Middleware\EnsureOnboardingCompleted::class, \App\Http\Middleware\SubscriptionMiddleware::class]);

    $user = User::factory()->create();

    $this->withoutExceptionHandling();
    $response = $this->actingAs($user)->get('/freelance/profile');

    $response->assertStatus(200);
});

it('allows an authenticated user to update their profile', function () {
    $this->withoutMiddleware([\App\Http\Middleware\EnsureOnboardingCompleted::class, \App\Http\Middleware\SubscriptionMiddleware::class]);

    $user = User::factory()->create();

    $payload = [
        'title' => 'Senior Laravel Developer',
        'bio' => 'I write clean code.',
        'hourly_rate' => 50,
    ];

    $response = $this->actingAs($user)->put('/freelance/profile', $payload);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $this->assertDatabaseHas('freelance_profiles', [
        'user_id' => $user->id,
        'title' => 'Senior Laravel Developer',
        'bio' => 'I write clean code.',
        'hourly_rate' => 50,
    ]);
});
