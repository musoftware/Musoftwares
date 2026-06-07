<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\GoldSavers\Models\GoldWallet;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('redirects to pricing without gold-saver addon', function () {
    $response = $this->actingAs($this->user)->get('/isaas/gold-savers/wallets');
    $response->assertRedirect(route('subscriptions.plans'));
});

it('allows access to wallets index with gold-saver addon', function () {
    DB::table('user_subscriptions')->insert([
        'user_id' => $this->user->id,
        'object' => 'gold-saver',
        'status' => 'active',
        'expires_at' => now()->addDays(30),
    ]);

    $response = $this->actingAs($this->user)->get('/isaas/gold-savers/wallets');
    $response->assertStatus(200);
});

it('blocks creating a second wallet without gold-multi-wallets addon', function () {
    DB::table('user_subscriptions')->insert([
        'user_id' => $this->user->id,
        'object' => 'gold-saver',
        'status' => 'active',
        'expires_at' => now()->addDays(30),
    ]);

    GoldWallet::factory()->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)->post('/isaas/gold-savers/wallets', [
        'name' => 'Second Wallet',
    ]);
    
    $response->assertStatus(403);
});

it('allows creating multiple wallets with gold-multi-wallets addon', function () {
    DB::table('user_subscriptions')->insert([
        ['user_id' => $this->user->id, 'object' => 'gold-saver', 'status' => 'active', 'expires_at' => now()->addDays(30)],
        ['user_id' => $this->user->id, 'object' => 'gold-multi-wallets', 'status' => 'active', 'expires_at' => now()->addDays(30)],
    ]);

    GoldWallet::factory()->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)->post('/isaas/gold-savers/wallets', [
        'name' => 'Second Wallet',
    ]);
    
    $response->assertRedirect();
});
