<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class, DatabaseTransactions::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('redirects to pricing without gold-saver addon', function () {
    $response = $this->actingAs($this->user)->get('/isaas/gold-savers/analytics');
    $response->assertRedirect(route('subscriptions.plans'));
});

it('allows access to analytics index with gold-saver addon', function () {
    DB::table('user_subscriptions')->insert([
        'user_id' => $this->user->id,
        'object' => 'gold-saver',
        'status' => 'active',
        'expires_at' => now()->addDays(30),
    ]);

    $response = $this->actingAs($this->user)->get('/isaas/gold-savers/analytics');
    $response->assertStatus(200);
});
