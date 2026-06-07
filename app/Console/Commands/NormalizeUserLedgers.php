<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\CurrenciesExchange;
use Illuminate\Support\Facades\Schema;

class NormalizeUserLedgers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:normalize-user-ledgers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalize historical user private ledgers (transactions, earnings, withdraws) to ensure they are stored in the user\'s currency.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting ledger normalization process...');

        $this->normalizeTransactions();
        $this->normalizeEarnings();
        $this->normalizeWithdraws();

        $this->info('Normalization complete.');
    }

    private function normalizeTransactions()
    {
        $this->info('Normalizing transactions...');
        $count = 0;
        
        DB::table('transactions')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->chunk(100, function ($transactions) use (&$count) {
                foreach ($transactions as $transaction) {
                    $user = User::find($transaction->user_id);
                    if (!$user) continue;

                    $userCurrencyId = $user->currency_id ?? \App\Models\AdminSettings::business_currency();
                    
                    // Transactions table uses 'currency' column (though model fillable says currency_id)
                    $currentCurrencyId = $transaction->currency ?? $transaction->currency_id ?? \App\Models\AdminSettings::business_currency();

                    if ($currentCurrencyId != $userCurrencyId) {
                        $date = $transaction->created_at ?? now();
                        
                        $convertedAmount = CurrenciesExchange::RateByDate(
                            $date,
                            $transaction->amount,
                            $currentCurrencyId,
                            $userCurrencyId
                        );

                        // Also recalculate business amount if it exists
                        $businessCurrencyId = \App\Models\AdminSettings::business_currency();
                        $businessAmount = CurrenciesExchange::RateByDate(
                            $date,
                            $convertedAmount,
                            $userCurrencyId,
                            $businessCurrencyId
                        );

                        DB::table('transactions')
                            ->where('id', $transaction->id)
                            ->update([
                                'amount' => $convertedAmount,
                                'currency' => $userCurrencyId,
                                'business_amount' => $businessAmount,
                            ]);
                            
                        $count++;
                    }
                }
            });
            
        $this->info("Updated {$count} transactions.");
    }

    private function normalizeEarnings()
    {
        $this->info('Normalizing earnings...');
        $count = 0;
        
        DB::table('earnings')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->chunk(100, function ($earnings) use (&$count) {
                foreach ($earnings as $earning) {
                    $user = User::find($earning->user_id);
                    if (!$user) continue;

                    $userCurrencyId = $user->currency_id ?? \App\Models\AdminSettings::business_currency();
                    $currentCurrencyId = $earning->currency ?? $earning->currency_id ?? \App\Models\AdminSettings::business_currency();

                    if ($currentCurrencyId != $userCurrencyId) {
                        $date = $earning->created_at ?? now();
                        
                        $convertedAmount = CurrenciesExchange::RateByDate(
                            $date,
                            $earning->amount,
                            $currentCurrencyId,
                            $userCurrencyId
                        );

                        DB::table('earnings')
                            ->where('id', $earning->id)
                            ->update([
                                'amount' => $convertedAmount,
                                'currency' => $userCurrencyId,
                            ]);
                            
                        $count++;
                    }
                }
            });
            
        $this->info("Updated {$count} earnings.");
    }

    private function normalizeWithdraws()
    {
        $this->info('Normalizing user referral request withdraws...');
        
        if (!Schema::hasTable('user_referral_request_withdraws')) {
            $this->info('Table user_referral_request_withdraws does not exist, skipping.');
            return;
        }

        $count = 0;
        
        DB::table('user_referral_request_withdraws')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->chunk(100, function ($withdraws) use (&$count) {
                foreach ($withdraws as $withdraw) {
                    $user = User::find($withdraw->user_id);
                    if (!$user) continue;

                    $userCurrencyId = $user->currency_id ?? \App\Models\AdminSettings::business_currency();
                    $currentCurrencyId = $withdraw->currency_id ?? $withdraw->currency ?? \App\Models\AdminSettings::business_currency();

                    if ($currentCurrencyId != $userCurrencyId) {
                        $date = $withdraw->created_at ?? now();
                        
                        $convertedAmount = CurrenciesExchange::RateByDate(
                            $date,
                            $withdraw->amount,
                            $currentCurrencyId,
                            $userCurrencyId
                        );

                        DB::table('user_referral_request_withdraws')
                            ->where('id', $withdraw->id)
                            ->update([
                                'amount' => $convertedAmount,
                                'currency_id' => $userCurrencyId,
                            ]);
                            
                        $count++;
                    }
                }
            });
            
        $this->info("Updated {$count} user referral request withdraws.");
    }
}
