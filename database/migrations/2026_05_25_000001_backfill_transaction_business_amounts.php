<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Transaction;
use App\Models\CostTransaction;
use App\Models\CurrenciesExchange;
use App\Models\AdminSettings;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $businessCurrencyId = AdminSettings::business_currency();

        // Backfill transactions table
        Transaction::chunk(100, function ($transactions) use ($businessCurrencyId) {
            foreach ($transactions as $t) {
                // If business_amount is 0 or business_calculated is false, calculate it.
                if (!$t->business_calculated || $t->business_amount == 0) {
                    $currency = $t->currency_id ?? $businessCurrencyId;
                    $t->business_amount = CurrenciesExchange::RateTodayNoRound($t->amount, $currency, $businessCurrencyId);
                    $t->business_calculated = true;
                    $t->save();
                }
            }
        });

        // Backfill cost_transactions table
        CostTransaction::chunk(100, function ($costTransactions) use ($businessCurrencyId) {
            foreach ($costTransactions as $ct) {
                // If business_amount is 0 or business_calculated is false, calculate it.
                if (!$ct->business_calculated || $ct->business_amount == 0) {
                    $currency = $ct->currency_id ?? $businessCurrencyId;
                    $ct->business_amount = CurrenciesExchange::RateTodayNoRound($ct->amount, $currency, $businessCurrencyId);
                    $ct->business_calculated = true;
                    $ct->save();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to revert
    }
};
