<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\User;
use App\Models\Currency;

it('allows an authenticated user to view the freelance dashboard', function () {
    $this->withoutMiddleware([\App\Http\Middleware\EnsureOnboardingCompleted::class, \App\Http\Middleware\SubscriptionMiddleware::class]);

    $currency = Currency::firstOrCreate(
        ['currency' => 'USD'],
        ['symbol' => '$', 'string_format' => '$ %s', 'exchange_rate' => 1]
    );

    $user = User::factory()->create([
        'currency_id' => $currency->id
    ]);

    $response = $this->actingAs($user)->get('/freelance/dashboard');

    $response->assertStatus(200);
});
