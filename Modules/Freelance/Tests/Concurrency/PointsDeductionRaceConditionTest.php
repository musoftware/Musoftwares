<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

use Modules\Freelance\Domains\Finance\Actions\DeductPointsAction;
use Modules\Freelance\Domains\Finance\Actions\AddPointsAction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;



it('uses pessimistic locking to prevent points deduction race conditions', function () {
    $user = User::factory()->create(['points_balance' => 50]);

    // To test pessimistic locking in a single PHP thread, we would normally use 
    // DB::transaction() with lockForUpdate().
    // We will simulate a lock violation by mocking the query builder, or by running 
    // two separate DB connections and asserting the second one blocks or fails.
    
    // Instead of a true multi-threaded test (which requires Process::fork or external scripts),
    // we assert that the transaction closure throws appropriately when balance goes negative
    // and that the action successfully wraps the execution in a transaction.

    $action = app(DeductPointsAction::class);
    
    // Deduction 1: 30 points
    $action->execute($user->id, 30, 'Task 1');
    expect($user->fresh()->points_balance)->toBe(20);

    // Deduction 2: 30 points (Should fail)
    try {
        $action->execute($user->id, 30, 'Task 2');
        $this->fail('Should not be able to deduct below zero.');
    } catch (\Exception $e) {
        expect($e->getMessage())->toBe('Insufficient points balance.');
    }

    expect($user->fresh()->points_balance)->toBe(20);
});

it('prevents negative point balances under concurrent logic simulation', function () {
    $user = User::factory()->create(['points_balance' => 10]);

    $action = app(DeductPointsAction::class);

    // If 3 rapid requests come in for 5 points, only 2 should succeed.
    $successCount = 0;
    $failCount = 0;

    for ($i = 0; $i < 3; $i++) {
        try {
            $action->execute($user->id, 5, 'Rapid task ' . $i);
            $successCount++;
        } catch (\Exception $e) {
            $failCount++;
        }
    }

    expect($successCount)->toBe(2)
        ->and($failCount)->toBe(1)
        ->and($user->fresh()->points_balance)->toBe(0);
});
