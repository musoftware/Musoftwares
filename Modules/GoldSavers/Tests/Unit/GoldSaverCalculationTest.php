<?php

use Modules\GoldSavers\Models\GoldSaver;

uses(Tests\TestCase::class);

it('calculates the buyer price correctly', function () {
    $saver = new GoldSaver([
        'gram_price' => 3000,
        'additional_price' => 50, // e.g., workmanship fees per gram
        'grams' => 10,
        'tax' => 100, // Flat tax
    ]);

    // (3000 + 50) * 10 + 100 = 3050 * 10 + 100 = 30500 + 100 = 30600
    expect($saver->buyer_price())->toBe(30600.0);
});

it('calculates seller price correctly for 24k gold', function () {
    $saver = new GoldSaver([
        'carat' => 24,
        'grams' => 10,
    ]);

    $latestPrice = [
        'price_24k' => 4000,
        'price_21k' => 3500,
        'price_18k' => 3000,
    ];

    expect($saver->seller_price($latestPrice))->toBe(40000.0);
});

it('calculates seller price correctly for 21k gold', function () {
    $saver = new GoldSaver([
        'carat' => 21,
        'grams' => 5,
    ]);

    $latestPrice = [
        'price_24k' => 4000,
        'price_21k' => 3500,
        'price_18k' => 3000,
    ];

    // 5 * 3500 = 17500
    expect($saver->seller_price($latestPrice))->toBe(17500.0);
});

it('returns 0 seller price for unknown carats', function () {
    $saver = new GoldSaver([
        'carat' => 99, // Unknown
        'grams' => 10,
    ]);

    $latestPrice = [
        'price_24k' => 4000,
        'price_21k' => 3500,
    ];

    expect($saver->seller_price($latestPrice))->toBe(0.0);
});
