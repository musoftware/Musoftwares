<?php

namespace Modules\Marketplace\Services;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Exception;

class FinancialTransactionService
{
    /**
     * Process a general financial transaction.
     */
    public function processTransaction(User $user, float $amount, string $type, string $description): void
    {
        DB::transaction(function () use ($user, $amount, $type, $description) {
            Transaction::create([
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount,
                'currency' => $user->currency_name() ?? 'EGP',
                'description' => $description,
            ]);
        });
    }

    /**
     * Process payment from user.
     */
    public function processPayment(User $user, float $amount, string $description): void
    {
        if ($user->available_balance() < $amount) {
            throw new Exception(__('marketplace.insufficient_funds'));
        }

        DB::transaction(function () use ($user, $amount, $description) {
            $user->add_balance(-$amount, $description, 'used', $user->currency_id);

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'payment',
                'amount' => $amount,
                'currency' => $user->currency_name() ?? 'EGP',
                'description' => $description,
            ]);
        });
    }

    /**
     * Credit funds to user.
     */
    public function processCredit(User $user, float $amount, string $description): void
    {
        DB::transaction(function () use ($user, $amount, $description) {
            $user->add_balance($amount, $description, 'earned', $user->currency_id);

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'credit',
                'amount' => $amount,
                'currency' => $user->currency_name() ?? 'EGP',
                'description' => $description,
            ]);
        });
    }
}
