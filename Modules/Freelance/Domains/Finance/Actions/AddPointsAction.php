<?php

namespace Modules\Freelance\Domains\Finance\Actions;

use App\Models\User;
use App\Models\PointTransaction;
use Illuminate\Support\Facades\DB;

class AddPointsAction
{
    public function execute(int $userId, int $amount, string $description, string $referenceType = null, string $referenceId = null): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }

        DB::transaction(function () use ($userId, $amount, $description, $referenceType, $referenceId) {
            // Lock the user row for update to prevent race conditions
            $user = User::where('id', $userId)->lockForUpdate()->firstOrFail();

            // Add points safely
            $user->points_balance += $amount;
            $user->save();

            // Record transaction
            PointTransaction::create([
                'user_id' => $user->id,
                'points' => $amount,
                'type' => 'earned', // or 'credit' based on legacy usage
                'description' => $description,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
        });
    }
}
