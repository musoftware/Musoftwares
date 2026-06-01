<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\GoldSavers\Models\GoldWallet;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class, DatabaseTransactions::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $currency = Currency::factory()->create();
    DB::table('admin_settings')->insertOrIgnore(['setting_key' => 'business_currency', 'setting_value' => $currency->id]);
});

it('redirects to pricing without gold-saver addon', function () {
    $response = $this->actingAs($this->user)->get('/isaas/gold-savers');
    $response->assertRedirect(route('subscriptions.plans'));
});

it('allows access with gold-saver addon', function () {
    DB::table('user_subscriptions')->insert([
        'user_id' => $this->user->id,
        'object' => 'gold-saver',
        'status' => 'active',
        'expires_at' => now()->addDays(30),
    ]);

    $response = $this->actingAs($this->user)->get('/isaas/gold-savers');
    $response->assertStatus(200);
});
