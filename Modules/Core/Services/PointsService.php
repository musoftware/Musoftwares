<?php

namespace Modules\Core\Services;

use App\Models\User;
use Modules\Core\Models\PointTransaction;
use Illuminate\Support\Facades\DB;
use Exception;

class PointsService
{
    public function credit(User $user, int $points, string $type, ?string $reference = null)
    {
        return DB::transaction(function () use ($user, $points, $type, $reference) {
            $user = User::lockForUpdate()->find($user->id);

            $transaction = PointTransaction::create([
                'user_id' => $user->id,
                'type' => 'credit',
                'points' => $points,
                'reference_type' => $type,
                'reference_id' => $reference,
            ]);

            $user->points_balance = ($user->points_balance ?? 0) + $points;
            $user->save();

            return $transaction;
        });
    }

    public function debit(User $user, int $points, string $type, ?string $reference = null)
    {
        return DB::transaction(function () use ($user, $points, $type, $reference) {
            $user = User::lockForUpdate()->find($user->id);

            if (($user->points_balance ?? 0) < $points) {
                throw new Exception("Insufficient points.");
            }

            $transaction = PointTransaction::create([
                'user_id' => $user->id,
                'type' => 'debit',
                'points' => $points,
                'reference_type' => $type,
                'reference_id' => $reference,
            ]);

            $user->points_balance -= $points;
            $user->save();

            return $transaction;
        });
    }

    public function getBalance(User $user): int
    {
        return (int) $user->points_balance;
    }
}
