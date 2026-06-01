<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

use Modules\Freelance\Domains\Finance\Actions\AddPointsAction;
use App\Models\User;
use App\Models\PointTransaction;
use Illuminate\Foundation\Testing\DatabaseTransactions;



it('adds points and creates a transaction log', function () {
    $user = User::factory()->create(['points_balance' => 10]);

    $action = app(AddPointsAction::class);
    $action->execute($user->id, 15, 'Refund', 'proposal_refund', '456');

    $user->refresh();

    expect($user->points_balance)->toBe(25);

    $transaction = PointTransaction::where('user_id', $user->id)->first();
    
    expect($transaction)->not->toBeNull()
        ->and($transaction->points)->toBe(15)
        ->and($transaction->type)->toBe('earned')
        ->and($transaction->description)->toBe('Refund')
        ->and($transaction->reference_type)->toBe('proposal_refund')
        ->and($transaction->reference_id)->toBe('456');
});

it('throws exception if addition amount is zero or negative', function () {
    $user = User::factory()->create(['points_balance' => 10]);

    $action = app(AddPointsAction::class);
    $action->execute($user->id, -5, 'Invalid addition');
})->throws(\InvalidArgumentException::class, 'Amount must be greater than zero.');
