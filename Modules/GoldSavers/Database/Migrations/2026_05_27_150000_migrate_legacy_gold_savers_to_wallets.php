<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Modules\GoldSavers\Models\GoldWallet;
use Modules\GoldSavers\Models\GoldTransaction;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('gold_savers')) {
            $oldSavers = DB::table('gold_savers')->get();

            // Group by user_id
            $groupedByUser = $oldSavers->groupBy('user_id');

            foreach ($groupedByUser as $userId => $items) {
                // Determine tenant_id from user if possible, but fallback to null if tenant logic is absent
                $user = DB::table('users')->where('id', $userId)->first();
                $tenantId = $user ? ($user->tenant_id ?? null) : null;

                // Create a Legacy Wallet for the user
                $walletId = DB::table('gold_wallets')->insertGetId([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'name' => 'Legacy Wallet',
                    'goal_type' => 'Investment',
                    'target_grams' => 0,
                    'target_amount' => 0,
                    'balance_grams' => 0,
                    'balance_amount' => 0,
                    'currency' => 'EGP', // Assuming default currency based on legacy data
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $totalGrams = 0;
                $totalAmount = 0;

                foreach ($items as $item) {
                    $fees = ($item->tax ?? 0) + ($item->additional_price ?? 0);
                    $totalTxAmount = ($item->grams * $item->gram_price) + $fees;

                    DB::table('gold_transactions')->insert([
                        'wallet_id' => $walletId,
                        'type' => 'buy',
                        'grams' => $item->grams,
                        'karat' => $item->carat,
                        'price_per_gram' => $item->gram_price,
                        'total_amount' => $totalTxAmount,
                        'fees' => $fees,
                        'currency_id' => 1, // Assuming default currency_id
                        'transaction_date' => $item->bought_date ?? now()->toDateString(),
                        'created_at' => $item->created_at ?? now(),
                        'updated_at' => $item->updated_at ?? now(),
                        'notes' => 'Migrated from legacy system',
                    ]);

                    $totalGrams += $item->grams;
                    $totalAmount += $totalTxAmount;
                }

                // Update wallet balance
                DB::table('gold_wallets')->where('id', $walletId)->update([
                    'balance_grams' => $totalGrams,
                    'balance_amount' => $totalAmount,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration
    }
};
