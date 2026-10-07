<?php

namespace App\Console\Commands;

use App\Helpers\BalancesHelper;
use App\Models\Earning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ProcessEarningsClearing
 *
 * Processes matured referral earnings and moves them into user wallet balances.
 * Runs every minute via the scheduler (routes/console.php).
 *
 * A "matured" earning is one where:
 *   - transaction_id IS NULL (not yet cleared)
 *   - convert_to_balance_on < now (clearing date has passed)
 *   - amount > 0
 *
 * Each earning is re-read under a row lock inside its own transaction and
 * skipped if it was already cleared, so a second run never credits it twice.
 */
class ProcessEarningsClearing extends Command
{
    protected $signature = 'earnings:clear {--limit=10 : Number of earnings to process per run}';

    protected $description = 'Process matured referral earnings and credit user wallets';

    public function handle(): int
    {
        $earningIds = Earning::whereNull('transaction_id')
            ->where('convert_to_balance_on', '<', now())
            ->where('amount', '>', 0)
            ->orderBy('id')
            ->limit((int) $this->option('limit'))
            ->pluck('id');

        $processed = 0;
        foreach ($earningIds as $earningId) {
            $processed += $this->clearEarning((int) $earningId) ? 1 : 0;
        }

        $this->info("Processed {$processed} earning(s).");

        return Command::SUCCESS;
    }

    private function clearEarning(int $earningId): bool
    {
        try {
            return DB::transaction(fn () => $this->creditLockedEarning($earningId));
        } catch (Throwable $e) {
            Log::error('ProcessEarningsClearing: failed to process earning', [
                'earning_id' => $earningId,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Must run inside a transaction: locks the earning row first.
     */
    private function creditLockedEarning(int $earningId): bool
    {
        $earn = Earning::whereKey($earningId)->lockForUpdate()->first();

        if (! $earn || $earn->transaction_id !== null) {
            return false;
        }

        $user = $earn->user;
        if (! $user) {
            Log::warning('ProcessEarningsClearing: earning has no user, skipped', ['earning_id' => $earningId]);

            return false;
        }

        $description = $earn->referred_invoice_id
            ? 'Referral Commission — Invoice #'.$earn->referred_invoice_id
            : 'Affiliate Commission';

        $earn->transaction_id = $user->add_balance($earn->amount, $description, 'earned', $earn->currency_id);
        $earn->save();

        BalancesHelper::UpdateBalance($user, null);

        return true;
    }
}
