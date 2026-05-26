<?php

namespace App\Console\Commands;

use App\Helpers\BalancesHelper;
use App\Models\Earning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcessEarningsClearing
 *
 * Processes matured referral earnings and moves them into user wallet balances.
 * Runs every minute via the scheduler (routes/console.php).
 *
 * A "matured" earning is one where:
 *   - transaction_id IS NULL (not yet cleared)
 *   - convert_to_balance_on < NOW() (clearing date has passed)
 *   - amount > 0
 *
 * Processes in batches of 10 to avoid memory spikes.
 */
class ProcessEarningsClearing extends Command
{
    protected $signature = 'earnings:clear {--limit=10 : Number of earnings to process per run}';

    protected $description = 'Process matured referral earnings and credit user wallets';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $earnings = Earning::whereNull('transaction_id')
            ->where('convert_to_balance_on', '<', DB::raw('NOW()'))
            ->where('amount', '>', 0)
            ->limit($limit)
            ->get();

        if ($earnings->isEmpty()) {
            return Command::SUCCESS;
        }

        $processed = 0;

        foreach ($earnings as $earn) {
            try {
                DB::transaction(function () use ($earn) {
                    $description = 'Affiliate Commission';

                    // If it's a direct referred-invoice commission
                    if ($earn->referred_invoice_id) {
                        $description = 'Referral Commission — Invoice #' . $earn->referred_invoice_id;
                    }

                    // Move earning to user wallet
                    $txId = $earn->user->add_balance(
                        $earn->amount,
                        $description,
                        'earned',
                        $earn->currency_id
                    );

                    $earn->transaction_id = $txId;
                    $earn->save();

                    // Sync cached balance fields
                    if (class_exists(BalancesHelper::class)) {
                        BalancesHelper::UpdateBalance($earn->user, null);
                    }
                });

                $processed++;
            } catch (\Throwable $e) {
                Log::error('ProcessEarningsClearing: Failed to process earning #' . $earn->id, [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Processed {$processed} earning(s).");

        return Command::SUCCESS;
    }
}
