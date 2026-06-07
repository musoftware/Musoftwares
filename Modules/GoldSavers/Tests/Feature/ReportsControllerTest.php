<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('redirects to pricing without gold-saver addon', function () {
    $response = $this->actingAs($this->user)->get('/isaas/gold-savers/reports');
    $response->assertRedirect(route('subscriptions.plans'));
});

it('allows access to reports index with gold-saver addon', function () {
    DB::table('user_subscriptions')->insert([
        'user_id' => $this->user->id,
        'object' => 'gold-saver',
        'status' => 'active',
        'expires_at' => now()->addDays(30),
    ]);

    $response = $this->actingAs($this->user)->get('/isaas/gold-savers/reports');
    $response->assertStatus(200);
});

it('blocks downloading pdf without gold-investment-reports addon', function () {
    DB::table('user_subscriptions')->insert([
        'user_id' => $this->user->id,
        'object' => 'gold-saver',
        'status' => 'active',
        'expires_at' => now()->addDays(30),
    ]);

    $response = $this->actingAs($this->user)->get('/isaas/gold-savers/reports/download?wallet_id=all&period=all_time');
    
    $response->assertSessionHas('error');
    $response->assertRedirect();
});
