<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Exception;

class PointsService
{
    /**
     * Get available points balance for the user.
     */
    public function getBalance(User $user): float
    {
        return (float) ($user->points_balance ?? 0);
    }

    /**
     * Debit points from the user.
     */
    public function debit(User $user, float $amount, string $reasonType, string $description): void
    {
        if ($this->getBalance($user) < $amount) {
            throw new Exception("Insufficient points balance.");
        }

        DB::transaction(function () use ($user, $amount, $reasonType, $description) {
            $user->points_balance -= $amount;
            $user->save();

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'used',
                'amount' => $amount,
                'currency' => 'points',
                'description' => $description,
            ]);
        });
    }

    /**
     * Credit points to the user.
     */
    public function credit(User $user, float $amount, string $reasonType, string $description): void
    {
        DB::transaction(function () use ($user, $amount, $reasonType, $description) {
            $user->points_balance += $amount;
            $user->save();

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'earned',
                'amount' => $amount,
                'currency' => 'points',
                'description' => $description,
            ]);
        });
    }
}
