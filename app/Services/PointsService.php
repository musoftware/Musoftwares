<?php

namespace App\Services;

use App\Models\PointTransaction;
use App\Models\User;
use Exception;

/**
 * Prepaid points (users.points_balance). Every change is written to the points ledger
 * (point_transactions), never to the money wallet ledger (transactions).
 */
class PointsService extends BaseService
{
    /**
     * Get available points balance for the user.
     */
    public function getBalance(User $user): float
    {
        return (float) ($user->points_balance ?? 0);
    }

    /**
     * Debit points from the user. The balance check runs on a locked row, so two
     * parallel debits can never spend the same points.
     */
    public function debit(User $user, float $amount, string $reasonType, string $description): void
    {
        $this->executeInTransaction(function () use ($user, $amount, $reasonType, $description) {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($this->getBalance($locked) < $amount) {
                throw new Exception('Insufficient points balance.');
            }

            $this->applyChange($locked, -$amount, 'used', $reasonType, $description);
            $user->points_balance = $locked->points_balance;
        });
    }

    /**
     * Credit points to the user.
     */
    public function credit(User $user, float $amount, string $reasonType, string $description): void
    {
        $this->executeInTransaction(function () use ($user, $amount, $reasonType, $description) {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            $this->applyChange($locked, $amount, 'earned', $reasonType, $description);
            $user->points_balance = $locked->points_balance;
        });
    }

    private function applyChange(User $lockedUser, float $signedAmount, string $type, string $reasonType, string $description): void
    {
        $lockedUser->increment('points_balance', $signedAmount);

        PointTransaction::create([
            'user_id' => $lockedUser->id,
            'type' => $type,
            'points' => $signedAmount,
            'description' => "[{$reasonType}] {$description}",
        ]);
    }
}
