<?php

use Modules\GoldSavers\Services\GoldWalletService;
use Modules\GoldSavers\Models\GoldWallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new GoldWalletService();
    $this->user = User::factory()->create();
});

it('creates a gold wallet correctly', function () {
    $wallet = $this->service->createWallet($this->user->id, 'My Savings', 'retirement', 500, 0, 1);

    expect($wallet->name)->toBe('My Savings')
        ->and($wallet->user_id)->toBe($this->user->id)
        ->and($wallet->balance_grams)->toBe(0.0)
        ->and($wallet->balance_amount)->toBe(0.0);
});

it('adds a transaction and recalculates balance accurately', function () {
    $wallet = GoldWallet::factory()->create(['user_id' => $this->user->id]);

    // Buy 10 grams at 4000
    $tx1 = $this->service->addTransaction($wallet, [
        'type' => 'buy',
        'grams' => 10,
        'karat' => 24,
        'price_per_gram' => 4000,
        'total_amount' => 40000,
        'fees' => 100,
        'currency_id' => 2,
    ]);

    expect($wallet->fresh()->balance_grams)->toBe(10.0)
        ->and($wallet->fresh()->balance_amount)->toBe(40000.0);

    // Sell 5 grams at 4200
    $tx2 = $this->service->addTransaction($wallet, [
        'type' => 'sell',
        'grams' => 5,
        'karat' => 24,
        'price_per_gram' => 4200,
        'total_amount' => 21000,
        'fees' => 50,
        'currency_id' => 2,
    ]);

    expect($wallet->fresh()->balance_grams)->toBe(5.0)
        ->and($wallet->fresh()->balance_amount)->toBe(19000.0); // 40000 - 21000
});

it('rolls back transaction if an error occurs during add', function () {
    $wallet = GoldWallet::factory()->create(['user_id' => $this->user->id]);

    try {
        $this->service->addTransaction($wallet, [
            'type' => 'buy',
            'grams' => null, // This will throw DB constraint error
            'karat' => 24,
            'price_per_gram' => 4000,
            'total_amount' => 40000,
            'fees' => 100,
        ]);
    } catch (\Exception $e) {
        // Expected
    }

    // Verify nothing was saved
    expect($wallet->transactions()->count())->toBe(0)
        ->and($wallet->fresh()->balance_grams)->toBe(0.0);
});


