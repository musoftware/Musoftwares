<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

use Modules\Freelance\Domains\Finance\Actions\DeductPointsAction;
use App\Models\User;
use App\Models\PointTransaction;
use Illuminate\Foundation\Testing\DatabaseTransactions;



it('deducts points and creates a transaction log', function () {
    $user = User::factory()->create(['points_balance' => 100]);

    $action = app(DeductPointsAction::class);
    $action->execute($user->id, 20, 'Test deduction', 'job', '123');

    $user->refresh();

    expect($user->points_balance)->toBe(80);

    $transaction = PointTransaction::where('user_id', $user->id)->first();
    
    expect($transaction)->not->toBeNull()
        ->and($transaction->points)->toBe(20)
        ->and($transaction->type)->toBe('spent')
        ->and($transaction->description)->toBe('Test deduction')
        ->and($transaction->reference_type)->toBe('job')
        ->and($transaction->reference_id)->toBe('123');
});

it('throws exception if points balance is insufficient', function () {
    $user = User::factory()->create(['points_balance' => 10]);

    $action = app(DeductPointsAction::class);
    $action->execute($user->id, 20, 'Test deduction');
})->throws(\Exception::class, 'Insufficient points balance.');

it('throws exception if deduction amount is zero or negative', function () {
    $user = User::factory()->create(['points_balance' => 100]);

    $action = app(DeductPointsAction::class);
    $action->execute($user->id, -5, 'Invalid deduction');
})->throws(\InvalidArgumentException::class, 'Deduction amount must be greater than zero.');

it('locks row and ensures atomicity', function () {
    // This mostly asserts that the logic completes successfully in isolation.
    // Real parallel testing happens in Concurrency tests.
    $user = User::factory()->create(['points_balance' => 100]);
    $action = app(DeductPointsAction::class);
    $action->execute($user->id, 50, 'Safe deduction');
    expect($user->fresh()->points_balance)->toBe(50);
});
